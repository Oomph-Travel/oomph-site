<?php
/**
 * Group Cruise CPT — hosted-cruise landing pages.
 *
 * Slug: oomph_cruise · public rewrite: /group-cruises/[slug]/
 * Event schema injection lives in class-schema.php.
 *
 * @package OomphTravel\Core
 */

declare( strict_types=1 );

namespace OomphTravel\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CPT_Cruise {

	public const POST_TYPE = 'oomph_cruise';

	/**
	 * How far ahead a departure must be before its page may be published.
	 *
	 * Cruise final payment falls roughly 90–120 days before sailing and group
	 * space is released around the same point, so a nearer departure is not
	 * realistically sellable. Its page is held (or returned) to draft rather
	 * than shown to someone who cannot book it.
	 */
	public const PUBLISH_LEAD_MONTHS = 3;

	/** Daily cron hook that returns unbookable sailings to draft. */
	public const RETIRE_HOOK = 'oomph_retire_unbookable_sailings';

	/**
	 * Earliest departure date a sailing may currently be published for (Y-m-d).
	 * Filter `oomph_sailing_publish_lead_months` to change the window.
	 */
	public static function earliest_publish_date(): string {
		$months = (int) apply_filters( 'oomph_sailing_publish_lead_months', self::PUBLISH_LEAD_MONTHS );
		return gmdate( 'Y-m-d', strtotime( '+' . max( 0, $months ) . ' months' ) );
	}

	/**
	 * Register non-CPT hooks for this post type.
	 *
	 * A cruise post is a structured data-entry form (40 ACF fields) with one
	 * short editorial paragraph in the content. The block editor always renders
	 * ACF field groups below the block canvas, which buries the form; the classic
	 * editor honours the group's `acf_after_title` position, putting the fields
	 * directly under the title where they belong. REST stays enabled
	 * (show_in_rest => true) — this only swaps the editing surface.
	 */
	public static function init(): void {
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'use_classic_editor' ), 10, 2 );

		// Daily sweep: a sailing that crosses the lead threshold between monthly
		// imports must come off the site on its own, not wait for the next upload.
		add_action( self::RETIRE_HOOK, array( Importer::class, 'retire_unbookable' ) );
		add_action( 'init', array( __CLASS__, 'schedule_retire_sweep' ) );
	}

	public static function use_classic_editor( bool $use_block_editor, string $post_type ): bool {
		return self::POST_TYPE === $post_type ? false : $use_block_editor;
	}

	/** Ensure the daily retire sweep is on the schedule. */
	public static function schedule_retire_sweep(): void {
		if ( ! wp_next_scheduled( self::RETIRE_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::RETIRE_HOOK );
		}
	}

	public static function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'          => __( 'Group Cruises', 'oomph-travel-core' ),
					'singular_name' => __( 'Group Cruise', 'oomph-travel-core' ),
				),
				'public'        => true,
				'show_in_rest'  => true,
				'has_archive'   => true,
				'menu_position' => 23,
				'menu_icon'     => 'dashicons-palmtree',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' ),
				'rewrite'       => array(
					'slug'       => 'group-cruises',
					'with_front' => false,
				),
				'capability_type' => 'post',
			)
		);
	}
}
