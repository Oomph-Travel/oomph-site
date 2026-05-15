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
