<?php
/**
 * The advisor identity — one source of truth for who wrote the site.
 *
 * Everything that names Eric on the front end (the journal byline, the Person
 * node in the JSON-LD @graph, the bio block on /about) reads from here, and
 * this reads from the WordPress user record. Editing the profile screen is
 * therefore enough to change the name and bio everywhere; no deploy needed.
 *
 * Before 2026-08 the name lived as a literal 'Eric Hempel' in
 * Schema::person() and in the theme's single.php, which is why updating the
 * user profile changed nothing on the front end.
 *
 * Which user? In order:
 *   1. Whatever `oomph_advisor_user_id` returns, if anything.
 *   2. The lowest-numbered administrator — the site owner on a solo site.
 *
 * Deliberately NOT the current post's author: the BlogPosting node points its
 * `author` at the single `/about/#advisor` @id, so a byline that tracked
 * post_author could disagree with the schema and split the author entity in
 * search — the exact thing this class exists to prevent. A genuine second
 * author would need its own Person node, not a different display name on the
 * same @id.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Advisor {

	/**
	 * Last-resort name, used only if the user record is missing or its
	 * display_name is blank. Never the normal path — if this renders, the
	 * lookup below failed.
	 */
	private const FALLBACK_NAME = 'Eric Hempel';

	/**
	 * Resolved user ID for this request. 0 = looked up, found nothing.
	 * null = not looked up yet.
	 */
	private static ?int $user_id = null;

	/**
	 * @return int 0 when no advisor user could be resolved.
	 */
	public static function user_id(): int {
		if ( null !== self::$user_id ) {
			return self::$user_id;
		}

		$id = (int) apply_filters( 'oomph_advisor_user_id', 0 );

		if ( $id <= 0 ) {
			$admins = get_users(
				array(
					'role'    => 'administrator',
					'orderby' => 'ID',
					'order'   => 'ASC',
					'number'  => 1,
					'fields'  => 'ID',
				)
			);
			$id = $admins ? (int) $admins[0] : 0;
		}

		self::$user_id = $id;

		return $id;
	}

	public static function user(): ?\WP_User {
		$id = self::user_id();
		if ( $id <= 0 ) {
			return null;
		}
		$user = get_userdata( $id );

		return $user instanceof \WP_User ? $user : null;
	}

	/**
	 * Display name from the profile screen — currently "Dr. Eric Hempel, DO".
	 */
	public static function name(): string {
		$user = self::user();
		$name = $user ? trim( (string) $user->display_name ) : '';

		if ( '' === $name ) {
			$name = self::FALLBACK_NAME;
		}

		return (string) apply_filters( 'oomph_advisor_name', $name, $user );
	}

	/**
	 * Biographical Info from the profile screen.
	 *
	 * Plain text as WordPress stores it. Callers that print it are responsible
	 * for escaping; callers that put it in JSON-LD should pass it through
	 * wp_strip_all_tags() in case the field ever picks up markup.
	 *
	 * @return string '' when the field is empty — callers must not emit an
	 *                empty `description` or an empty bio block.
	 */
	public static function bio(): string {
		$user = self::user();
		$bio  = $user ? trim( (string) get_the_author_meta( 'description', $user->ID ) ) : '';

		return (string) apply_filters( 'oomph_advisor_bio', $bio, $user );
	}

	/**
	 * jobTitle for the Person node. Both roles, because both are real and both
	 * are visible on /about.
	 *
	 * WordPress has no job-title field on the profile screen, so this is a
	 * filter rather than user meta. Change it in one place:
	 *   add_filter( 'oomph_advisor_job_title', fn() => array( '…' ) );
	 *
	 * @return string[]
	 */
	public static function job_title(): array {
		$titles = apply_filters(
			'oomph_advisor_job_title',
			array( 'Luxury Travel Advisor', 'Physician' )
		);

		return array_values( array_filter( array_map( 'strval', (array) $titles ) ) );
	}

	/**
	 * knowsAbout for the Person node — subject-matter authority signals.
	 *
	 * @return string[]
	 */
	public static function knows_about(): array {
		$topics = apply_filters(
			'oomph_advisor_knows_about',
			array(
				'Luxury cruising',
				'Expedition cruising',
				'Silversea Cruises',
				'Italy travel',
				'United Kingdom travel',
				'Multi-generational travel',
				'Travel health',
			)
		);

		return array_values( array_filter( array_map( 'strval', (array) $topics ) ) );
	}
}
