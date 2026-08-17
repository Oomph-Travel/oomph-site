<?php
/**
 * Journal integration for hub pages.
 *
 * Hub pages (the service templates) surface Journal posts two ways: the
 * "Recent writing" card grid, and contextual in-sentence links inside the
 * prose. Both live here so a hub page's topic cluster is defined in one
 * place rather than being spread across templates.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Category slugs whose posts belong to a hub page's topic cluster.
 *
 * Keyed by page slug so the Service Page template can be reused across hubs
 * and each one surfaces its own posts. Filter `oomph_page_journal_categories`
 * to add a hub without editing this map.
 *
 * Slugs, not term IDs, on purpose: the category IDs differ per environment
 * (production carries Expedition/Pre & Post Cruise, staging does not), so an
 * ID hardcoded here would quietly query the wrong terms — or nothing at all —
 * outside production.
 *
 * @param WP_Post|null $page Defaults to the current post.
 * @return string[] Category slugs; empty means "no cluster configured".
 */
function oomph_page_journal_category_slugs( ?WP_Post $page = null ): array {
	$page = $page ?? get_post();
	$slug = $page ? $page->post_name : '';

	$map = array(
		'luxury-cruise-planning'             => array( 'cruise', 'expedition', 'pre-post-cruise' ),
		'custom-italy-travel'                => array( 'independent-travel', 'italy' ),
		'multi-generational-travel-planning' => array( 'multi-generational' ),
	);

	$slugs = apply_filters( 'oomph_page_journal_categories', $map[ $slug ] ?? array(), $page );

	return array_values( array_filter( array_map( 'strval', (array) $slugs ) ) );
}

/**
 * Journal posts for a hub page's card grid.
 *
 * Two deliberate fallbacks:
 *
 *   • A configured slug that doesn't exist in this environment is dropped
 *     rather than failing the query, so a hub still shows the categories it
 *     does have. A category with fewer posts than $limit simply returns
 *     fewer — the grid is sized by what came back, never padded.
 *   • A page with no cluster configured falls back to recent posts sitewide.
 *     That's the pre-existing behaviour, kept so an unmapped hub shows
 *     something rather than an empty section; map its slug above to make the
 *     section topical.
 *
 * @param string[] $category_slugs Cluster categories; empty for sitewide.
 * @param int      $limit          Maximum posts to return.
 * @return WP_Post[]
 */
function oomph_get_journal_posts( array $category_slugs, int $limit = 6 ): array {
	$args = array(
		'numberposts' => $limit,
		'post_status' => 'publish',
		'orderby'     => 'date',
		'order'       => 'DESC',
	);

	$ids = array();
	foreach ( $category_slugs as $slug ) {
		$term = get_category_by_slug( $slug );
		if ( $term ) {
			$ids[] = (int) $term->term_id;
		}
	}

	if ( $ids ) {
		$args['category__in'] = $ids;
	}

	return get_posts( $args );
}

/**
 * A contextual in-body link to a Journal post, resolved by slug.
 *
 * Returns '' when the post doesn't exist or isn't published yet, so a hub page
 * can reference posts that are still drafts without ever shipping a link that
 * 404s. Callers treat '' as "leave this out" rather than rendering bare text —
 * see oomph_journal_sentence().
 *
 * Resolved by slug rather than ID because these are editorial references
 * written alongside the copy, and slugs survive a re-import; the lookup is
 * memoised per request so repeating a slug costs one query, not several.
 *
 * @param string $slug   Post slug, e.g. 'first-premium-cruise'.
 * @param string $anchor Descriptive anchor text — never "click here" (R18).
 * @return string Anchor HTML, or '' when the post isn't live.
 */
function oomph_journal_link( string $slug, string $anchor ): string {
	static $cache = array();

	if ( ! array_key_exists( $slug, $cache ) ) {
		$found = get_posts(
			array(
				'name'        => $slug,
				'post_type'   => 'post',
				'post_status' => 'publish',
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		$cache[ $slug ] = $found ? (int) $found[0] : 0;
	}

	if ( ! $cache[ $slug ] ) {
		return '';
	}

	return sprintf(
		'<a href="%s">%s</a>',
		esc_url( (string) get_permalink( $cache[ $slug ] ) ),
		esc_html( $anchor )
	);
}

/**
 * A whole sentence built around a Journal link, or '' if the post isn't live.
 *
 * Gating the sentence rather than just the link matters: copy like "I've
 * written about X" reads as a broken promise when X isn't published. Dropping
 * the sentence entirely keeps the paragraph honest either way.
 *
 * @param string $slug     Post slug.
 * @param string $anchor   Anchor text.
 * @param string $sentence Sentence template with a single %s for the link.
 * @return string
 */
function oomph_journal_sentence( string $slug, string $anchor, string $sentence ): string {
	$link = oomph_journal_link( $slug, $anchor );

	return $link ? sprintf( $sentence, $link ) : '';
}
