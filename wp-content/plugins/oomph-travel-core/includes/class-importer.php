<?php
/**
 * WP-CLI command: wp oomph import-sailings <file.csv>
 *
 * Ingests a CSV export of the Travel Leaders Network "All Sailings" sheet and
 * creates / updates oomph_cruise posts for the Distinctive Voyages and Amenity
 * Departure lanes. Columns are mapped strictly by header name (never index) so a
 * reordered export still imports correctly; missing optional headers warn and
 * continue rather than fatal.
 *
 * Dedupe is by Voyage # (when present and not "Not Applicable"), else by
 * Ship + Sail Date. On a match the existing post's fields are updated in place
 * and post_content — the editorial "Why this sailing" copy — is never touched.
 *
 * The three booking references (Voyage #, US Group #, CA Group #) are stored as
 * meta but never rendered publicly; see class-schema.php and the single template.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

final class Importer {

	/**
	 * Field name => ACF field key. Writing by key is more robust than by name
	 * (survives a field being renamed) and forces ACF to format/validate.
	 */
	private const FIELD_KEYS = array(
		'sailing_type'       => 'field_oomph_sailing_type',
		'cruise_theme'       => 'field_oomph_cruise_theme',
		'cruise_ship_name'   => 'field_oomph_cruise_ship_name',
		'cruise_line'        => 'field_oomph_cruise_line',
		'cruise_region'      => 'field_oomph_cruise_region',
		'cruise_dates_start' => 'field_oomph_cruise_dates_start',
		'cruise_dates_end'   => 'field_oomph_cruise_dates_end',
		'embark_city'        => 'field_oomph_embark_city',
		'disembark_city'     => 'field_oomph_disembark_city',
		'amenity_summary'    => 'field_oomph_amenity_summary',
		'amenity_details'    => 'field_oomph_amenity_details',
		'voyage_number'      => 'field_oomph_voyage_number',
		'us_group_number'    => 'field_oomph_us_group_number',
		'ca_group_number'    => 'field_oomph_ca_group_number',
	);

	/** Placeholder body written to NEW posts only; the template renders nothing while this is present. */
	private const WHY_PLACEHOLDER = '<!-- WHY THIS SAILING: Eric writes 2–3 sentences here before publishing -->';

	/** Header => in-row cache of which optional headers were missing (warned once each). */
	private array $missing_headers = array();

	/**
	 * Import Distinctive Voyages / Amenity sailings from a TLN CSV export.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the CSV export of the "All Sailings" sheet.
	 *
	 * [--type=<type>]
	 * : Which lane to import. dv = Distinctive Voyages, add = Amenity Departures, all = both.
	 * ---
	 * default: dv
	 * options:
	 *   - dv
	 *   - add
	 *   - all
	 * ---
	 *
	 * [--lines=<lines>]
	 * : Comma-separated cruise lines to include (matched against the "Cruise Line" column).
	 *
	 * [--after=<date>]
	 * : Only sailings on or after this date (Y-m-d). Defaults to today.
	 *
	 * [--before=<date>]
	 * : Only sailings on or before this date (Y-m-d). Defaults to today + 15 months.
	 *
	 * [--status=<status>]
	 * : Post status for newly created posts.
	 * ---
	 * default: draft
	 * options:
	 *   - draft
	 *   - publish
	 * ---
	 *
	 * [--limit=<n>]
	 * : Stop after this many rows have passed the filters.
	 *
	 * [--dry-run]
	 * : Parse and report what would happen without writing anything.
	 *
	 * [--retire-past]
	 * : Also set any published oomph_cruise with a past sail date back to draft.
	 *
	 * ## EXAMPLES
	 *
	 *     wp oomph import-sailings all-sailings.csv --dry-run
	 *     wp oomph import-sailings all-sailings.csv --type=all --status=draft
	 *
	 * @param array<int,string>    $args       Positional args ([0] = file path).
	 * @param array<string,string> $assoc_args Flags.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		if ( ! function_exists( 'update_field' ) ) {
			\WP_CLI::error( 'Advanced Custom Fields is not active; cannot write cruise fields.' );
		}

		$file = $args[0] ?? '';
		if ( $file === '' || ! is_readable( $file ) ) {
			\WP_CLI::error( "CSV file not readable: {$file}" );
		}

		$type    = (string) ( $assoc_args['type'] ?? 'dv' );
		$status  = (string) ( $assoc_args['status'] ?? 'draft' );
		$dry_run = isset( $assoc_args['dry-run'] );
		$limit   = isset( $assoc_args['limit'] ) ? max( 0, (int) $assoc_args['limit'] ) : 0;

		$lines_raw = (string) ( $assoc_args['lines'] ?? 'Silversea Cruises,Oceania Cruises,Explora Journeys,Celebrity Cruises,Regent Seven Seas Cruises,Cunard' );
		$lines     = array_filter( array_map( 'trim', explode( ',', $lines_raw ) ) );
		$lines_lc  = array_map( 'strtolower', $lines );

		$after  = $this->parse_date( (string) ( $assoc_args['after'] ?? '' ) ) ?? gmdate( 'Y-m-d' );
		$before = $this->parse_date( (string) ( $assoc_args['before'] ?? '' ) ) ?? gmdate( 'Y-m-d', strtotime( '+15 months' ) );

		$want = 'dv' === $type ? array( 'dv' ) : ( 'add' === $type ? array( 'amenity' ) : array( 'dv', 'amenity' ) );

		$rows = $this->read_csv( $file );
		if ( null === $rows ) {
			\WP_CLI::error( 'Could not read a header row from the CSV.' );
		}
		\WP_CLI::log( sprintf( 'Read %d data rows. Filters: type=%s, lines=[%s], %s .. %s%s', count( $rows ), $type, implode( ', ', $lines ), $after, $before, $dry_run ? ', DRY RUN' : '' ) );

		$results     = array();
		$record_seen = array(); // For the dry-run classifier diagnostic.
		$processed   = 0;

		foreach ( $rows as $i => $row ) {
			$line_no = $i + 2; // +1 header, +1 for 1-indexed.

			$ship = trim( $this->cell( $row, 'Ship' ) );
			$sail = $this->parse_date( $this->cell( $row, 'Sail Date' ) );
			$lane = $this->classify( $row );

			$rt = trim( $this->cell( $row, 'Sailing Record Type' ) );
			if ( '' !== $rt ) {
				$record_seen[ $rt ] = ( $record_seen[ $rt ] ?? 0 ) + 1;
			}

			// --- filters (skip with a reason so the summary explains itself) ---
			if ( ! in_array( $lane, $want, true ) ) {
				continue; // Off-lane rows are silently excluded (not "skipped" noise).
			}
			if ( '' === $ship ) {
				$results[] = $this->skip( $line_no, '(no ship)', $sail, $lane, 'missing Ship' );
				continue;
			}
			if ( null === $sail ) {
				$results[] = $this->skip( $line_no, $ship, null, $lane, 'unparseable Sail Date' );
				continue;
			}
			$cruise_line = trim( $this->cell( $row, 'Cruise Line' ) );
			if ( ! empty( $lines_lc ) && ! in_array( strtolower( $cruise_line ), $lines_lc, true ) ) {
				continue; // Off-list line, excluded quietly.
			}
			if ( $sail < $after || $sail > $before ) {
				continue; // Out of date window, excluded quietly.
			}
			if ( $limit > 0 && $processed >= $limit ) {
				break;
			}
			++$processed;

			$results[] = $this->handle_row( $row, $ship, $sail, $lane, $cruise_line, $status, $dry_run );
		}

		$this->render_summary( $results, $dry_run, $record_seen );

		if ( isset( $assoc_args['retire-past'] ) ) {
			$this->retire_past( $dry_run );
		}
	}

	/**
	 * Create or update a single sailing's post.
	 *
	 * @return array<string,string>
	 */
	private function handle_row( array $row, string $ship, string $sail, string $lane, string $cruise_line, string $status, bool $dry_run ): array {
		$itinerary = trim( $this->cell( $row, 'Itinerary' ) );
		$embark    = trim( $this->cell( $row, 'Embarkation City' ) );
		$disembark = trim( $this->cell( $row, 'Disembarkation City' ) );
		$voyage    = trim( $this->cell( $row, 'Voyage #' ) );

		$title_lead = '' !== $itinerary ? $itinerary : ( ( '' !== $embark && '' !== $disembark ) ? "{$embark} to {$disembark}" : $ship );
		$title      = sprintf( '%s — %s, %s', $title_lead, $ship, gmdate( 'F Y', strtotime( $sail ) ) );

		$existing = $this->find_existing( $voyage, $ship, $sail );

		if ( $dry_run ) {
			return array(
				'action' => $existing ? 'would-update' : 'would-create',
				'title'  => $title,
				'ship'   => $ship,
				'sail'   => $sail,
				'type'   => $this->sailing_type( $lane ),
				'reason' => $existing ? "#{$existing}" : '',
			);
		}

		if ( $existing ) {
			$post_id = $existing;
			// Never overwrite post_content on update — that's editorial copy.
		} else {
			$post_id = wp_insert_post(
				array(
					'post_type'    => CPT_Cruise::POST_TYPE,
					'post_status'  => in_array( $status, array( 'draft', 'publish' ), true ) ? $status : 'draft',
					'post_title'   => $title,
					'post_name'    => sanitize_title( $ship . '-' . $sail ),
					'post_content' => self::WHY_PLACEHOLDER,
				),
				true
			);
			if ( is_wp_error( $post_id ) ) {
				return $this->skip( 0, $title, $sail, $lane, 'insert failed: ' . $post_id->get_error_message() );
			}
		}

		$this->write_fields( (int) $post_id, $row, $ship, $sail, $lane, $cruise_line, $itinerary, $embark, $disembark, $voyage );

		return array(
			'action' => $existing ? 'updated' : 'created',
			'title'  => $title,
			'ship'   => $ship,
			'sail'   => $sail,
			'type'   => $this->sailing_type( $lane ),
			'reason' => "#{$post_id}",
		);
	}

	/**
	 * Write every mapped field onto the post. Empty source cells are stored as
	 * empty (never invented) so the templates can render-nothing on absence.
	 */
	private function write_fields( int $post_id, array $row, string $ship, string $sail, string $lane, string $cruise_line, string $itinerary, string $embark, string $disembark, string $voyage ): void {
		$set = function ( string $name, $value ) use ( $post_id ): void {
			update_field( self::FIELD_KEYS[ $name ], $value, $post_id );
		};

		$set( 'sailing_type', $this->sailing_type( $lane ) );
		$set( 'cruise_ship_name', $ship );
		$set( 'cruise_line', $cruise_line );
		$set( 'cruise_dates_start', $sail );
		$end = $this->parse_date( $this->cell( $row, 'Disembarkation Date' ) );
		if ( null !== $end ) {
			$set( 'cruise_dates_end', $end );
		}
		$set( 'embark_city', $embark );
		$set( 'disembark_city', $disembark );
		$set( 'cruise_theme', $this->map_theme( $this->cell( $row, 'Theme #1' ) ) );
		$set( 'amenity_summary', trim( $this->cell( $row, 'All Amenities' ) ) );
		$set( 'amenity_details', trim( $this->cell( $row, 'Additional Amenity Details' ) ) );

		// Internal booking references — stored, never displayed.
		$set( 'voyage_number', $voyage );
		$set( 'us_group_number', trim( $this->cell( $row, 'US Group #' ) ) );
		$set( 'ca_group_number', trim( $this->cell( $row, 'CA Group #' ) ) );

		// Region taxonomy — create the term if needed, set it on the object and the ACF field.
		$destination = trim( $this->cell( $row, 'Destination' ) );
		if ( '' !== $destination ) {
			$term = term_exists( $destination, Taxonomies::REGION );
			if ( ! $term ) {
				$term = wp_insert_term( $destination, Taxonomies::REGION );
			}
			if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
				$term_id = (int) $term['term_id'];
				wp_set_object_terms( $post_id, array( $term_id ), Taxonomies::REGION, false );
				update_field( self::FIELD_KEYS['cruise_region'], $term_id, $post_id );
			}
		}

		// Shore events 1–3 — only fields that are actually present.
		for ( $n = 1; $n <= 3; $n++ ) {
			$name = trim( $this->cell( $row, "Exclusive Shore Event #{$n}" ) );
			$date = $this->parse_date( $this->cell( $row, "Date of Shore Event #{$n}" ) );
			$time = trim( $this->cell( $row, "Time of Shore Event #{$n}" ) );
			$len  = trim( $this->cell( $row, "Shore Event Length Hrs #{$n}" ) );
			$loc  = trim( $this->cell( $row, "Location of Shore Event #{$n}" ) );
			$det  = trim( $this->cell( $row, "Additional Shore Event Details #{$n}" ) );

			if ( '' === $name && null === $date && '' === $time && '' === $len && '' === $loc && '' === $det ) {
				continue; // Nothing for this event slot.
			}
			update_field( "field_oomph_shore_event_{$n}_name", $name, $post_id );
			if ( null !== $date ) {
				update_field( "field_oomph_shore_event_{$n}_date", $date, $post_id );
			}
			update_field( "field_oomph_shore_event_{$n}_time", $time, $post_id );
			update_field( "field_oomph_shore_event_{$n}_length_hours", ( '' === $len ? '' : (float) $len ), $post_id );
			update_field( "field_oomph_shore_event_{$n}_location", $loc, $post_id );
			update_field( "field_oomph_shore_event_{$n}_details", $det, $post_id );
		}
	}

	/**
	 * Find an existing post: by Voyage # meta when it's a real reference,
	 * otherwise by Ship + Sail Date. Returns the post ID or 0.
	 */
	private function find_existing( string $voyage, string $ship, string $sail ): int {
		if ( '' !== $voyage && 0 !== strcasecmp( $voyage, 'Not Applicable' ) ) {
			$ids = get_posts(
				array(
					'post_type'      => CPT_Cruise::POST_TYPE,
					'post_status'    => 'any',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => 'voyage_number',
					'meta_value'     => $voyage,
				)
			);
			return $ids ? (int) $ids[0] : 0;
		}

		$ids = get_posts(
			array(
				'post_type'      => CPT_Cruise::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation' => 'AND',
					array( 'key' => 'cruise_ship_name', 'value' => $ship ),
					array( 'key' => 'cruise_dates_start', 'value' => $sail ),
				),
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Set published sailings whose sail date has passed back to draft.
	 */
	private function retire_past( bool $dry_run ): void {
		$today = gmdate( 'Y-m-d' );
		$ids   = get_posts(
			array(
				'post_type'      => CPT_Cruise::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => array(
					array( 'key' => 'cruise_dates_start', 'value' => $today, 'compare' => '<', 'type' => 'DATE' ),
				),
			)
		);
		if ( ! $ids ) {
			\WP_CLI::log( 'Retire-past: no published sailings with a past sail date.' );
			return;
		}
		foreach ( $ids as $id ) {
			if ( ! $dry_run ) {
				wp_update_post( array( 'ID' => (int) $id, 'post_status' => 'draft' ) );
			}
		}
		\WP_CLI::log( sprintf( 'Retire-past: %d past sailing(s) %s to draft.', count( $ids ), $dry_run ? 'would be set' : 'set' ) );
	}

	// --- CSV plumbing -------------------------------------------------------

	/**
	 * Read the CSV into an array of header-keyed rows. Returns null if no header.
	 *
	 * @return array<int,array<string,string>>|null
	 */
	private function read_csv( string $file ): ?array {
		$fh = fopen( $file, 'r' );
		if ( false === $fh ) {
			return null;
		}
		$header = fgetcsv( $fh );
		if ( false === $header || null === $header ) {
			fclose( $fh );
			return null;
		}
		// Strip a UTF-8 BOM from the first header cell and trim all headers.
		$header[0] = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $header[0] );
		$header    = array_map( static fn( $h ) => trim( (string) $h ), $header );

		$rows = array();
		while ( false !== ( $data = fgetcsv( $fh ) ) ) {
			if ( array( null ) === $data ) {
				continue; // Blank line.
			}
			$row = array();
			foreach ( $header as $col => $name ) {
				$row[ $name ] = isset( $data[ $col ] ) ? (string) $data[ $col ] : '';
			}
			$rows[] = $row;
		}
		fclose( $fh );
		return $rows;
	}

	/**
	 * Read a cell by header name. Missing optional headers warn once, then '' .
	 */
	private function cell( array $row, string $header ): string {
		if ( array_key_exists( $header, $row ) ) {
			return $row[ $header ];
		}
		if ( ! isset( $this->missing_headers[ $header ] ) ) {
			$this->missing_headers[ $header ] = true;
			\WP_CLI::warning( "Column not found in CSV: \"{$header}\" — treating as empty." );
		}
		return '';
	}

	// --- classifiers / parsers ---------------------------------------------

	/** dv | amenity | other, from the record-type / trip-type columns. */
	private function classify( array $row ): string {
		$hay = strtolower( $this->cell( $row, 'Sailing Record Type' ) . ' ' . $this->cell( $row, 'Trip Type' ) );
		if ( false !== strpos( $hay, 'distinctive' ) ) {
			return 'dv';
		}
		if ( false !== strpos( $hay, 'amenity' ) ) {
			return 'amenity';
		}
		return 'other';
	}

	private function sailing_type( string $lane ): string {
		return 'dv' === $lane ? 'distinctive_voyage' : 'amenity_departure';
	}

	/** none | culinary | car_and_driver from the "Theme #1" cell. */
	private function map_theme( string $raw ): string {
		$r = strtolower( trim( $raw ) );
		if ( '' === $r ) {
			return 'none';
		}
		if ( false !== strpos( $r, 'culinary' ) ) {
			return 'culinary';
		}
		if ( false !== strpos( $r, 'car' ) && false !== strpos( $r, 'driver' ) ) {
			return 'car_and_driver';
		}
		return 'none';
	}

	/**
	 * Parse a date defensively into Y-m-d, or null. Handles ISO (Y-m-d,
	 * with optional time) and US m/d/Y that both appear in TLN exports.
	 */
	private function parse_date( string $raw ): ?string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return null;
		}
		// Drop any time component.
		$raw = preg_replace( '/[T ]\d{1,2}:\d{2}.*$/', '', $raw );

		foreach ( array( 'Y-m-d', 'n/j/Y', 'm/d/Y', 'n/j/y', 'm/d/y' ) as $fmt ) {
			$dt = \DateTime::createFromFormat( '!' . $fmt, $raw );
			if ( $dt instanceof \DateTime && $dt->format( $fmt ) === $raw ) {
				return $dt->format( 'Y-m-d' );
			}
		}
		// Last resort — let strtotime try, but only accept a sane 4-digit year.
		$ts = strtotime( $raw );
		if ( false !== $ts && (int) gmdate( 'Y', $ts ) >= 2000 ) {
			return gmdate( 'Y-m-d', $ts );
		}
		return null;
	}

	// --- reporting ----------------------------------------------------------

	/**
	 * @return array<string,string>
	 */
	private function skip( int $line_no, string $title, ?string $sail, string $lane, string $reason ): array {
		return array(
			'action' => 'skipped',
			'title'  => $title,
			'ship'   => '',
			'sail'   => $sail ?? '',
			'type'   => $this->sailing_type( $lane ),
			'reason' => ( $line_no ? "row {$line_no}: " : '' ) . $reason,
		);
	}

	/**
	 * @param array<int,array<string,string>> $results
	 * @param array<string,int>               $record_seen
	 */
	private function render_summary( array $results, bool $dry_run, array $record_seen ): void {
		$counts = array();
		foreach ( $results as $r ) {
			$counts[ $r['action'] ] = ( $counts[ $r['action'] ] ?? 0 ) + 1;
		}

		if ( $dry_run && ! empty( $record_seen ) ) {
			\WP_CLI::log( "\nSailing Record Type values seen (all rows, pre-filter):" );
			arsort( $record_seen );
			foreach ( $record_seen as $val => $n ) {
				\WP_CLI::log( sprintf( '  %5d  %s', $n, $val ) );
			}
		}

		if ( empty( $results ) ) {
			\WP_CLI::success( 'No rows matched the filters.' );
			return;
		}

		\WP_CLI::log( '' );
		\WP_CLI\Utils\format_items( 'table', $results, array( 'action', 'title', 'ship', 'sail', 'type', 'reason' ) );

		$parts = array();
		foreach ( $counts as $action => $n ) {
			$parts[] = "{$action}: {$n}";
		}
		$summary = implode( ' · ', $parts );
		if ( $dry_run ) {
			\WP_CLI::success( "Dry run — no writes. {$summary}" );
		} else {
			\WP_CLI::success( $summary );
		}
	}
}

\WP_CLI::add_command( 'oomph import-sailings', Importer::class );
