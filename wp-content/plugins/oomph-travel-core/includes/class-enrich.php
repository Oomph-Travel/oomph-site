<?php
/**
 * WP-CLI command: wp oomph enrich-sailing <post_id> --file=<payload.json>
 *
 * Thin CLI wrapper over Enrich_Engine (shared with the admin Enrichment Sync
 * screen). Payload shape and guarantees are documented in
 * class-enrich-engine.php: never publishes, never overwrites human-written
 * content without --force-why, --dry-run writes nothing.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

final class Enrich {

	/**
	 * Enrich an imported sailing from a JSON payload file.
	 *
	 * ## OPTIONS
	 *
	 * <post_id>
	 * : ID of the oomph_cruise post to enrich.
	 *
	 * --file=<path>
	 * : Path to the JSON payload.
	 *
	 * [--force-why]
	 * : Overwrite post_content even when Eric has already written it.
	 *
	 * [--dry-run]
	 * : Report what would change without writing anything.
	 *
	 * ## EXAMPLES
	 *
	 *     wp oomph enrich-sailing 1234 --file=enrich-1234.json --dry-run
	 *     wp oomph enrich-sailing 1234 --file=enrich-1234.json
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$post_id = (int) $args[0];

		$path = (string) ( $assoc_args['file'] ?? '' );
		if ( '' === $path || ! is_readable( $path ) ) {
			\WP_CLI::error( 'Payload file not found or unreadable: ' . $path );
		}

		$payload = json_decode( (string) file_get_contents( $path ), true );
		if ( ! is_array( $payload ) ) {
			\WP_CLI::error( 'Payload is not valid JSON: ' . json_last_error_msg() );
		}

		$dry = (bool) ( $assoc_args['dry-run'] ?? false );
		$res = Enrich_Engine::apply(
			$post_id,
			$payload,
			$dry,
			(bool) ( $assoc_args['force-why'] ?? false )
		);

		if ( null !== $res['error'] ) {
			\WP_CLI::error( $res['error'] );
		}
		foreach ( $res['warnings'] as $warning ) {
			\WP_CLI::warning( $warning . ' Use --force-why to overwrite.' );
		}
		if ( ! $res['changes'] ) {
			\WP_CLI::success( 'Nothing to do — payload contained no usable fields.' );
			return;
		}

		$post = get_post( $post_id );
		$verb = $dry ? 'Would write' : 'Wrote';
		\WP_CLI::success( sprintf( '%s to "%s" (#%d, status: %s): %s.', $verb, $post->post_title, $post_id, $post->post_status, implode( ', ', $res['changes'] ) ) );
	}
}

\WP_CLI::add_command( 'oomph enrich-sailing', Enrich::class );
