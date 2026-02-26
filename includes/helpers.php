<?php
/**
 * Helper / Utility Functions
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

// ─── Default global settings ──────────────────────────────────────────────────
function ppulse_default_settings() {
    return [
        'disable_on_mobile'    => false,
        'disable_on_tablet'    => false,
        'cookie_prefix'        => 'ppulse_',
        'respect_dnt'          => false,
        'load_fontawesome'     => true,
    ];
}

// ─── Get global settings ──────────────────────────────────────────────────────
function ppulse_get_settings() {
    $defaults = ppulse_default_settings();
    $saved    = get_option( 'ppulse_settings', [] );
    return wp_parse_args( $saved, $defaults );
}

// ─── Get all popup meta (with defaults) ──────────────────────────────────────
function ppulse_get_popup_meta( $post_id ) {
    $defaults = ppulse_meta_defaults();
    $meta     = [];

    foreach ( $defaults as $key => $default ) {
        $value        = get_post_meta( $post_id, '_ppulse_' . $key, true );
        $meta[ $key ] = ( $value !== '' && $value !== false ) ? $value : $default;
    }

    // Cast booleans stored as strings
    $bool_fields = [
        'enabled', 'show_close_btn', 'overlay_click_close',
        'auto_close_enabled', 'exit_intent', 'is_template',
        'show_on_mobile', 'show_on_tablet', 'show_on_desktop',
        'full_width_image', 'animate_in',
    ];

    foreach ( $bool_fields as $field ) {
        if ( isset( $meta[ $field ] ) ) {
            $meta[ $field ] = filter_var( $meta[ $field ], FILTER_VALIDATE_BOOLEAN );
        }
    }

    return $meta;
}

// ─── Save popup meta ──────────────────────────────────────────────────────────
function ppulse_save_popup_meta( $post_id, $data ) {
    foreach ( $data as $key => $value ) {
        if ( is_array( $value ) ) {
            $value = array_map( 'sanitize_text_field', $value );
        } elseif ( is_bool( $value ) ) {
            $value = $value ? '1' : '0';
        } else {
            $value = sanitize_text_field( $value );
        }
        update_post_meta( $post_id, '_ppulse_' . $key, $value );
    }
}

// ─── Fetch active (published) popups ─────────────────────────────────────────
// FIX: Use OR meta query so that popups whose _ppulse_enabled meta has never been
// explicitly written (e.g. created via Gutenberg REST API before the classic
// meta-box iframe fires) are still returned — the default is enabled = '1'.
// Popups explicitly disabled will have the meta row set to '0' and will be
// correctly excluded.
function ppulse_get_active_popups() {
    $query = new WP_Query( [
        'post_type'      => PPULSE_POST_TYPE,
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'   => '_ppulse_enabled',
                'value' => '1',
            ],
            [
                'key'     => '_ppulse_enabled',
                'compare' => 'NOT EXISTS',
            ],
        ],
        'no_found_rows'  => true,
    ] );

    return $query->posts;
}

// ─── Get pre-built template list ──────────────────────────────────────────────
function ppulse_get_builtin_templates() {
    return ppulse_builtin_templates();
}

// ─── Get user-saved templates ─────────────────────────────────────────────────
function ppulse_get_saved_templates() {
    $query = new WP_Query( [
        'post_type'      => PPULSE_POST_TYPE,
        'post_status'    => [ 'publish', 'draft' ],
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'   => '_ppulse_is_template',
                'value' => '1',
            ],
        ],
        'no_found_rows'  => true,
    ] );

    return $query->posts;
}

// ─── Cookie / frequency helpers ───────────────────────────────────────────────
function ppulse_cookie_name( $popup_id ) {
    $settings = ppulse_get_settings();
    return $settings['cookie_prefix'] . $popup_id;
}

// ─── Sanitise meta input ──────────────────────────────────────────────────────
function ppulse_sanitize_meta_input( $raw ) {
    $defaults  = ppulse_meta_defaults();
    $sanitized = [];

    $int_fields = [
        'trigger_delay', 'trigger_scroll_pct', 'auto_close_seconds',
        'frequency_limit', 'frequency_days', 'position_offset_y', 'position_offset_x',
        'popup_max_width',
    ];
    $bool_fields = [
        'enabled', 'show_close_btn', 'overlay_click_close', 'auto_close_enabled',
        'exit_intent', 'is_template', 'show_on_mobile', 'show_on_tablet',
        'show_on_desktop', 'full_width_image', 'animate_in',
    ];

    foreach ( $defaults as $key => $default ) {
        if ( ! isset( $raw[ $key ] ) ) {
            $sanitized[ $key ] = in_array( $key, $bool_fields, true ) ? '0' : $default;
            continue;
        }

        $val = $raw[ $key ];

        if ( in_array( $key, $int_fields, true ) ) {
            $sanitized[ $key ] = absint( $val );
        } elseif ( in_array( $key, $bool_fields, true ) ) {
            $sanitized[ $key ] = ( $val === '1' || $val === 'on' || $val === true ) ? '1' : '0';
        } elseif ( $key === 'trigger_click_selector' ) {
            $sanitized[ $key ] = sanitize_text_field( $val );
        } elseif ( $key === 'display_pages' ) {
            // Value is either 'all', 'front', or a comma-separated list of IDs
            if ( $val === 'all' || $val === 'front' ) {
                $sanitized[ $key ] = $val;
            } else {
                // Clean up comma-separated IDs: remove zeros and non-numeric
                $ids = array_filter( array_map( 'absint', explode( ',', $val ) ) );
                $sanitized[ $key ] = ! empty( $ids ) ? implode( ',', $ids ) : 'all';
            }
        } elseif ( $key === 'overlay_color' || $key === 'popup_bg_color' ) {
            $sanitized[ $key ] = sanitize_hex_color( $val ) ?: $default;
        } elseif ( $key === 'full_width_image_url' ) {
            $sanitized[ $key ] = esc_url_raw( $val );
        } else {
            $sanitized[ $key ] = sanitize_text_field( $val );
        }
    }

    return $sanitized;
}

// ─── Enqueue utility ──────────────────────────────────────────────────────────
function ppulse_asset_url( $path ) {
    return PPULSE_URL . ltrim( $path, '/' );
}

function ppulse_asset_ver( $path ) {
    $file = PPULSE_DIR . ltrim( $path, '/' );
    return file_exists( $file ) ? filemtime( $file ) : PPULSE_VERSION;
}