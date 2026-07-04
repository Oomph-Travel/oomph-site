<?php
/**
 * wp-admin screen: Group Cruises -> Import Sailings.
 *
 * A no-terminal way to run the monthly sailings update: upload the TLN "All
 * Sailings" file (.xlsx or .csv), Preview to see what will change, then Import.
 * Reuses OomphTravel\Core\Importer::run() so the admin screen and the WP-CLI
 * command share one engine.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Admin_Import {

	private const CAP    = 'edit_others_posts';
	private const SLUG   = 'oomph-import-sailings';
	private const SHEET  = 'All Sailings';
	private const NONCE  = 'oomph_import';
	private const MAX    = 26214400; // 25 MB.

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			'edit.php?post_type=' . CPT_Cruise::POST_TYPE,
			'Import Sailings',
			'Import Sailings',
			self::CAP,
			self::SLUG,
			array( __CLASS__, 'render' )
		);
	}

	public static function render(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( 'You do not have permission to import sailings.' );
		}
		self::cleanup_old_temp();

		echo '<div class="wrap"><h1>Import Sailings</h1>';
		echo '<p style="max-width:70ch">Upload the Travel Leaders Network <strong>All Sailings</strong> file (the <code>.xlsx</code> works directly — no need to save it as CSV). Click <strong>Preview</strong> first to see what will change, then <strong>Import</strong>. New sailings are created as <strong>drafts</strong> for you to write copy and publish; existing ones are updated in place without touching your copy.</p>';

		$step = isset( $_POST['oomph_step'] ) ? sanitize_key( wp_unslash( $_POST['oomph_step'] ) ) : '';

		if ( 'preview' === $step || 'import' === $step ) {
			check_admin_referer( self::NONCE );
			$dry     = ( 'preview' === $step );
			$retire  = ! empty( $_POST['retire_past'] );

			$csv = ( 'preview' === $step ) ? self::handle_upload() : self::resolve_token( isset( $_POST['oomph_token'] ) ? sanitize_file_name( wp_unslash( $_POST['oomph_token'] ) ) : '' );

			if ( is_wp_error( $csv ) ) {
				printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( $csv->get_error_message() ) );
			} else {
				$res = ( new Importer() )->run( $csv['path'], array( 'dry_run' => $dry, 'retire_past' => $retire ) );
				self::render_results( $res, $dry, $csv['token'], $retire );
				if ( ! $dry ) {
					wp_delete_file( $csv['path'] );
				}
			}
		}

		self::render_form();
		echo '</div>';
	}

	// --- upload / temp handling --------------------------------------------

	/**
	 * Validate + store the uploaded file, converting .xlsx to CSV.
	 *
	 * @return array{path:string,token:string}|\WP_Error
	 */
	private static function handle_upload() {
		if ( empty( $_FILES['sailings_file'] ) || ! isset( $_FILES['sailings_file']['error'] ) ) {
			return new \WP_Error( 'no_file', 'No file was uploaded.' );
		}
		$file = $_FILES['sailings_file']; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated below.
		if ( UPLOAD_ERR_OK !== (int) $file['error'] ) {
			return new \WP_Error( 'upload_err', 'The file did not upload correctly. Try again.' );
		}
		if ( (int) $file['size'] > self::MAX || (int) $file['size'] <= 0 ) {
			return new \WP_Error( 'too_big', 'That file is too large (or empty).' );
		}
		if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'bad_upload', 'Upload could not be verified.' );
		}

		$name = sanitize_file_name( (string) $file['name'] );
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		if ( ! in_array( $ext, array( 'xlsx', 'csv' ), true ) ) {
			return new \WP_Error( 'bad_ext', 'Please upload a .xlsx or .csv file.' );
		}

		$dir = self::temp_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		$stamp = (string) getmypid() . '-' . substr( md5( uniqid( (string) mt_rand(), true ) ), 0, 12 );

		if ( 'xlsx' === $ext ) {
			$xlsx_path = $dir . '/up-' . $stamp . '.xlsx';
			if ( ! move_uploaded_file( $file['tmp_name'], $xlsx_path ) ) {
				return new \WP_Error( 'move_fail', 'Could not save the uploaded file.' );
			}
			$csv_path = $dir . '/csv-' . $stamp . '.csv';
			$conv     = XLSX::to_csv( $xlsx_path, self::SHEET, $csv_path );
			wp_delete_file( $xlsx_path );
			if ( is_wp_error( $conv ) ) {
				return $conv;
			}
		} else {
			$csv_path = $dir . '/csv-' . $stamp . '.csv';
			if ( ! move_uploaded_file( $file['tmp_name'], $csv_path ) ) {
				return new \WP_Error( 'move_fail', 'Could not save the uploaded file.' );
			}
		}

		return array( 'path' => $csv_path, 'token' => basename( $csv_path ) );
	}

	/**
	 * Resolve a token from the preview step back to its temp CSV path.
	 *
	 * @return array{path:string,token:string}|\WP_Error
	 */
	private static function resolve_token( string $token ) {
		$token = basename( $token );
		if ( ! preg_match( '/^csv-[0-9a-f\-]+\.csv$/', $token ) ) {
			return new \WP_Error( 'bad_token', 'The upload session expired. Please upload the file again.' );
		}
		$dir = self::temp_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		$path = $dir . '/' . $token;
		if ( ! is_file( $path ) ) {
			return new \WP_Error( 'gone', 'The uploaded file is no longer available. Please upload it again.' );
		}
		return array( 'path' => $path, 'token' => $token );
	}

	/** Protected temp dir under uploads. Returns path or WP_Error. */
	private static function temp_dir() {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'oomph-import-tmp';
		if ( ! wp_mkdir_p( $dir ) ) {
			return new \WP_Error( 'no_dir', 'Could not create a temporary folder for the upload.' );
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php // Silence.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}
		return $dir;
	}

	/** Remove temp files older than an hour. */
	private static function cleanup_old_temp(): void {
		$up  = wp_upload_dir();
		$dir = trailingslashit( $up['basedir'] ) . 'oomph-import-tmp';
		if ( ! is_dir( $dir ) ) {
			return;
		}
		foreach ( glob( $dir . '/{up,csv}-*.{xlsx,csv}', GLOB_BRACE ) ?: array() as $f ) {
			if ( is_file( $f ) && ( time() - (int) filemtime( $f ) ) > HOUR_IN_SECONDS ) {
				wp_delete_file( $f );
			}
		}
	}

	// --- rendering ----------------------------------------------------------

	private static function render_form(): void {
		?>
		<hr>
		<form method="post" enctype="multipart/form-data" style="max-width:70ch">
			<?php wp_nonce_field( self::NONCE ); ?>
			<input type="hidden" name="oomph_step" value="preview">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sailings_file">Sailings file</label></th>
					<td>
						<input type="file" id="sailings_file" name="sailings_file" accept=".xlsx,.csv" required>
						<p class="description">The TLN "All Sailings" export (.xlsx or .csv), up to 25&nbsp;MB.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">Past sailings</th>
					<td>
						<label><input type="checkbox" name="retire_past" value="1" checked> Move sailings that have already departed back to draft</label>
					</td>
				</tr>
			</table>
			<?php submit_button( 'Preview', 'secondary', 'submit', false ); ?>
			<p class="description" style="margin-top:8px">Preview never changes anything — it just shows what an import would do.</p>
		</form>
		<?php
	}

	/**
	 * @param array<string,mixed> $res
	 */
	private static function render_results( array $res, bool $dry, string $token, bool $retire ): void {
		if ( ! empty( $res['error'] ) ) {
			printf( '<div class="notice notice-error"><p>%s</p></div>', esc_html( (string) $res['error'] ) );
			return;
		}

		$counts = $res['counts'];
		$order  = $dry ? array( 'would-create' => 'New sailings', 'would-update' => 'Updated', 'skipped' => 'Skipped' )
						: array( 'created' => 'New sailings', 'updated' => 'Updated', 'skipped' => 'Skipped' );

		echo '<div class="notice ' . ( $dry ? 'notice-info' : 'notice-success' ) . '"><p><strong>';
		echo $dry ? 'Preview only — nothing was changed.' : 'Import complete.';
		echo '</strong></p></div>';

		echo '<table class="widefat striped" style="max-width:640px;margin-bottom:16px"><tbody>';
		foreach ( $order as $key => $label ) {
			printf( '<tr><td style="width:60%%">%s</td><td><strong>%d</strong></td></tr>', esc_html( $label ), (int) ( $counts[ $key ] ?? 0 ) );
		}
		if ( $retire ) {
			printf( '<tr><td>Past sailings %s to draft</td><td><strong>%d</strong></td></tr>', $dry ? esc_html( 'that would be moved' ) : esc_html( 'moved' ), (int) $res['retired'] );
		}
		printf( '<tr><td>Rows read from the file</td><td>%d</td></tr>', (int) $res['total_rows'] );
		echo '</tbody></table>';

		if ( ! empty( $res['missing_headers'] ) ) {
			printf(
				'<div class="notice notice-warning"><p>Some expected columns were not found and were treated as empty: %s. If this looks wrong, double-check you exported the <strong>%s</strong> sheet.</p></div>',
				esc_html( implode( ', ', array_map( 'strval', $res['missing_headers'] ) ) ),
				esc_html( self::SHEET )
			);
		}

		// A sample of the rows so the user can sanity-check.
		$rows = $res['results'];
		if ( $rows ) {
			$sample = array_slice( $rows, 0, 25 );
			echo '<h2>Sample</h2><table class="widefat striped"><thead><tr><th>What</th><th>Sailing</th><th>Sail date</th><th>Notes</th></tr></thead><tbody>';
			foreach ( $sample as $r ) {
				printf(
					'<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
					esc_html( (string) $r['action'] ),
					esc_html( (string) $r['title'] ),
					esc_html( (string) $r['sail'] ),
					esc_html( (string) $r['reason'] )
				);
			}
			echo '</tbody></table>';
			if ( count( $rows ) > 25 ) {
				printf( '<p class="description">Showing 25 of %d.</p>', count( $rows ) );
			}
		}

		// After a preview, offer the real import of the same file.
		if ( $dry ) {
			?>
			<hr>
			<form method="post" style="margin-top:8px">
				<?php wp_nonce_field( self::NONCE ); ?>
				<input type="hidden" name="oomph_step" value="import">
				<input type="hidden" name="oomph_token" value="<?php echo esc_attr( $token ); ?>">
				<input type="hidden" name="retire_past" value="<?php echo $retire ? '1' : '0'; ?>">
				<p><strong>Looks right?</strong> Run the real import of this same file:</p>
				<?php submit_button( 'Import for real', 'primary', 'submit', false ); ?>
			</form>
			<?php
		} else {
			echo '<div class="notice notice-info" style="margin-top:16px"><p><strong>Next:</strong> new sailings were added as <strong>drafts</strong>. Go to <a href="' . esc_url( admin_url( 'edit.php?post_type=' . CPT_Cruise::POST_TYPE . '&post_status=draft' ) ) . '">Group Cruises &rarr; Drafts</a> to write each one\'s short "Why this sailing" line and publish it.</p></div>';
		}
	}
}
