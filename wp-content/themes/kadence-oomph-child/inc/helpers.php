<?php
/**
 * Shared helper functions for the Oomph child theme.
 *
 * @package OomphChild
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fetch an ACF field with a fallback for when ACF Pro is absent
 * or the value is empty. Treats null, '', and empty array as empty;
 * returns the raw value otherwise — including boolean false and
 * integer zero — so callers can distinguish "user said no" from
 * "field is unset".
 *
 * @param string $name     ACF field name.
 * @param mixed  $fallback Returned when ACF is absent or value is empty.
 * @return mixed
 */
function oomph_acf_field( $name, $fallback = '' ) {
    if ( ! function_exists( 'get_field' ) ) {
        return $fallback;
    }
    $value = get_field( $name );
    if ( $value === null || $value === '' || ( is_array( $value ) && empty( $value ) ) ) {
        return $fallback;
    }
    return $value;
}

/**
 * The advisor's display name, from the WordPress user record.
 *
 * Delegates to the oomph-travel-core plugin so the byline, the Person node in
 * the JSON-LD @graph, and the /about copy can never drift apart. The fallback
 * below only runs if the plugin is deactivated, and still reads the user
 * record rather than reintroducing a hardcoded name.
 *
 * @return string
 */
function oomph_advisor_name(): string {
    if ( class_exists( '\\OomphTravel\\Core\\Advisor' ) ) {
        return \OomphTravel\Core\Advisor::name();
    }

    $admins = get_users(
        array(
            'role'    => 'administrator',
            'orderby' => 'ID',
            'order'   => 'ASC',
            'number'  => 1,
        )
    );
    $name = $admins ? trim( (string) $admins[0]->display_name ) : '';

    return $name !== '' ? $name : 'Eric Hempel';
}

/**
 * The advisor's Biographical Info, from the WordPress user record.
 *
 * Returns '' when the plugin is inactive or the profile field is empty —
 * callers must treat '' as "render nothing", never as "render an empty block".
 *
 * @return string
 */
function oomph_advisor_bio(): string {
    if ( class_exists( '\\OomphTravel\\Core\\Advisor' ) ) {
        return \OomphTravel\Core\Advisor::bio();
    }

    return '';
}

/**
 * Responsive-variant widths per image folder.
 *
 * MUST stay in step with WIDTHS in scripts/images/optimize.mjs — that script
 * writes the files, this reads them. A width listed here with no file on disk
 * is simply skipped, so the two drifting apart degrades to "fewer candidates",
 * never to a broken image.
 *
 * @return array<string, int[]>
 */
function oomph_image_widths(): array {
    return array(
        'heroes'  => array( 768, 1200, 1600 ),
        'cards'   => array( 400, 600, 900 ),
        'journal' => array( 400, 800, 1200 ),
        '.'       => array( 480, 768, 1000 ),
    );
}

/**
 * Default `sizes` per folder — how wide the image actually renders.
 *
 * Heroes are full-bleed. Cards sit in a 3-up grid inside a 1280px container
 * that collapses to 2-up then 1-up, so the widest a card image ever renders is
 * roughly a third of the container.
 *
 * @return array<string, string>
 */
function oomph_image_sizes(): array {
    return array(
        'heroes'  => '100vw',
        'cards'   => '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw',
        'journal' => '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw',
        // The images/ root holds the full-bleed homepage hero alongside the
        // portrait and guide cover, which render at roughly half a column.
        // 100vw is the safe default (over-fetching a half-width image beats
        // under-fetching the LCP hero); the two column images pass their own.
        '.'       => '100vw',
    );
}

/**
 * Build the WebP srcset for a bundled theme image.
 *
 * Returns an empty string when no variants exist on disk — callers then fall
 * back to a plain <img> on the original, which is why a missing variant is
 * never a broken image.
 *
 * @param string $rel Path relative to assets/images/, e.g. 'heroes/cruise-hero.jpg'.
 * @return string Comma-separated srcset, or '' if no variants are present.
 */
function oomph_image_srcset( string $rel ): string {
    $folder = dirname( $rel );
    $widths = oomph_image_widths()[ $folder ] ?? array();
    if ( ! $widths ) {
        return '';
    }

    $base_rel = preg_replace( '/\.(jpe?g|png|webp)$/i', '', $rel );
    $dir      = get_stylesheet_directory() . '/assets/images/';
    $uri      = get_stylesheet_directory_uri() . '/assets/images/';

    $candidates = array();
    $largest    = 0;
    foreach ( $widths as $w ) {
        // optimize.mjs discards any variant that came out larger than its
        // source, so absence here is a deliberate signal, not an oversight.
        if ( file_exists( $dir . $base_rel . '-' . $w . '.webp' ) ) {
            $candidates[] = esc_url( $uri . $base_rel . '-' . $w . '.webp' ) . ' ' . $w . 'w';
            $largest      = max( $largest, $w );
        }
    }

    // When the source is itself WebP the srcset goes on the <img>, and a
    // `w`-descriptor srcset makes the browser ignore `src` entirely. If every
    // variant is narrower than the original — the portrait is 735px wide and
    // only a 480 variant exists, since the rest would be upscales — the
    // original has to be offered explicitly or desktop gets a blurry upscale.
    if ( $candidates && preg_match( '/\.webp$/i', $rel ) ) {
        $size = @getimagesize( $dir . $rel );
        if ( $size && $size[0] > $largest ) {
            $candidates[] = esc_url( $uri . $rel ) . ' ' . (int) $size[0] . 'w';
        }
    }

    return implode( ', ', $candidates );
}

/**
 * Render a bundled theme image as <picture> with WebP variants.
 *
 * The 2026-07 audit measured 250–400KB hero JPEGs being served at full size to
 * phones; SG Optimizer converts Media Library uploads to WebP but never touches
 * files served straight out of the theme directory. This is the theme-side
 * equivalent: WebP <source> with a real srcset, original JPEG as the <img>
 * fallback for browsers without WebP support.
 *
 * Always pass width/height — they're what keep CLS at the 0.007 the audit
 * measured. Set 'lcp' => true for the one image above the fold per page: that
 * emits fetchpriority="high" and suppresses lazy-loading (R3), and must never
 * be set on more than one image.
 *
 * @param string $rel  Path relative to assets/images/, e.g. 'heroes/cruise-hero.jpg'.
 * @param array  $args alt, width, height, sizes, class, lcp.
 * @return string Escaped HTML, or '' when the source file is missing.
 */
function oomph_picture( string $rel, array $args = array() ): string {
    $rel = ltrim( $rel, '/' );
    if ( ! file_exists( get_stylesheet_directory() . '/assets/images/' . $rel ) ) {
        return '';
    }

    $folder = dirname( $rel );
    $args   = array_merge(
        array(
            'alt'    => '',
            'width'  => '',
            'height' => '',
            'sizes'  => oomph_image_sizes()[ $folder ] ?? '100vw',
            'class'  => '',
            'lcp'    => false,
        ),
        $args
    );

    $src    = get_stylesheet_directory_uri() . '/assets/images/' . $rel;
    $srcset = oomph_image_srcset( $rel );

    $attrs = sprintf(
        'src="%s" alt="%s" decoding="async"',
        esc_url( $src ),
        esc_attr( $args['alt'] )
    );
    if ( $args['width'] ) {
        $attrs .= sprintf( ' width="%s"', esc_attr( (string) $args['width'] ) );
    }
    if ( $args['height'] ) {
        $attrs .= sprintf( ' height="%s"', esc_attr( (string) $args['height'] ) );
    }
    // R3: the LCP image is never lazy-loaded and carries fetchpriority="high".
    $attrs .= $args['lcp'] ? ' fetchpriority="high"' : ' loading="lazy"';

    $class = $args['class'] ? sprintf( ' class="%s"', esc_attr( $args['class'] ) ) : '';

    if ( ! $srcset ) {
        return sprintf( '<picture%s><img %s></picture>', $class, $attrs );
    }

    // A WebP source needs no <source> element — there's no format upgrade to
    // negotiate, only sizes. Put the srcset straight on the <img> so browsers
    // without WebP still get the original rather than nothing. The <picture>
    // wrapper stays either way; the layout CSS hangs off it.
    if ( preg_match( '/\.webp$/i', $rel ) ) {
        return sprintf(
            '<picture%s><img %s srcset="%s" sizes="%s"></picture>',
            $class,
            $attrs,
            esc_attr( $srcset ),
            esc_attr( $args['sizes'] )
        );
    }

    return sprintf(
        '<picture%s><source type="image/webp" srcset="%s" sizes="%s"><img %s></picture>',
        $class,
        esc_attr( $srcset ),
        esc_attr( $args['sizes'] ),
        $attrs
    );
}

/**
 * Preload the page's LCP hero so it starts downloading from <head>.
 *
 * Without this the hero isn't discovered until the combined stylesheet has
 * parsed — 4.5–5.3s into a throttled mobile load, as measured 2026-07-25.
 *
 * The imagesrcset/imagesizes MUST match what oomph_picture() emits for the
 * same image, or the browser treats the preload as a separate resource and
 * downloads the hero twice. Both read from the same two helpers above, which
 * is what keeps them in step.
 *
 * Call from a template *before* get_header() — after that, wp_head has fired.
 *
 * @param string $rel Path relative to assets/images/.
 * @return void
 */
function oomph_preload_hero( string $rel ): void {
    $rel = ltrim( $rel, '/' );
    if ( ! file_exists( get_stylesheet_directory() . '/assets/images/' . $rel ) ) {
        return;
    }

    add_action(
        'wp_head',
        static function () use ( $rel ) {
            $srcset = oomph_image_srcset( $rel );
            $sizes  = oomph_image_sizes()[ dirname( $rel ) ] ?? '100vw';
            $src    = get_stylesheet_directory_uri() . '/assets/images/' . $rel;

            if ( $srcset ) {
                printf(
                    '<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="%s" fetchpriority="high">' . "\n",
                    esc_url( $src ),
                    esc_attr( $srcset ),
                    esc_attr( $sizes )
                );
            } else {
                printf(
                    '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n",
                    esc_url( $src )
                );
            }
        },
        2
    );
}
