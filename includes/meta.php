<?php
/**
 * Popup Meta Fields — Registration & Defaults
 *
 * All meta keys are prefixed with _ppulse_
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init',                   'ppulse_register_meta_fields' );
add_action( 'save_post',              'ppulse_handle_save_post', 10, 2 );
add_action( 'rest_api_init',          'ppulse_register_rest_meta' );
add_action( 'transition_post_status', 'ppulse_set_default_meta_on_publish', 10, 3 );

// ─── Canonical defaults ───────────────────────────────────────────────────────
function ppulse_meta_defaults() {
    return [
        // General
        'enabled'               => '1',
        'is_template'           => '0',

        // Trigger
        'trigger_type'          => 'delay',     // delay | scroll | click | exit | pageload
        'trigger_delay'         => 3,            // seconds
        'trigger_scroll_pct'    => 50,           // % page scrolled
        'trigger_click_selector' => '',           // CSS selector

        // Exit Intent
        'exit_intent'           => '0',

        // Frequency / repetition
        'frequency_type'        => 'once',       // always | once | limited | per_session | per_page
        'frequency_limit'       => 1,            // used with 'limited'
        'frequency_days'        => 30,           // cookie lifetime (days)

        // Auto-close
        'auto_close_enabled'    => '0',
        'auto_close_seconds'    => 5,

        // Close button
        'show_close_btn'        => '1',
        'overlay_click_close'   => '1',

        // Layout / style
        'position'              => 'center',     // center | top-left | top-right | bottom-left | bottom-right | top-bar | bottom-bar
        'position_offset_y'     => 0,
        'position_offset_x'     => 0,
        'popup_max_width'       => 640,
        'popup_bg_color'        => '#ffffff',
        'overlay_color'         => '#000000',
        'overlay_opacity'       => '0.6',
        'animate_in'            => '1',
        'animation_style'       => 'fade',       // fade | slide-up | slide-down | zoom | bounce

        // Full-width image cover
        'full_width_image'      => '0',
        'full_width_image_url'  => '',
        'full_width_image_id'   => 0,

        // Device targeting
        'show_on_desktop'       => '1',
        'show_on_tablet'        => '1',
        'show_on_mobile'        => '1',

        // Display conditions (comma-separated post IDs, or "all" / "front")
        'display_pages'         => 'all',

        // Display schedule
        'schedule_start'        => '',           // Y-m-d
        'schedule_end'          => '',           // Y-m-d

        // Template reference (if created from a template)
        'template_id'           => 0,
    ];
}

// ─── Register meta with REST support ─────────────────────────────────────────
function ppulse_register_meta_fields() {
    $defaults = ppulse_meta_defaults();

    foreach ( $defaults as $key => $default ) {
        $type = is_int( $default ) ? 'integer' : ( is_float( $default ) ? 'number' : 'string' );
        register_post_meta( PPULSE_POST_TYPE, '_ppulse_' . $key, [
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => $type,
            'default'       => $default,
            'auth_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
        ] );
    }
}

// ─── REST API: expose all fields in the custom namespace ─────────────────────
function ppulse_register_rest_meta() {
    // Already handled by register_post_meta with show_in_rest = true
}

// ─── Set default meta when a popup is first published ─────────────────────────
// This handles Gutenberg REST API saves where the classic meta-box form data
// may not be present, ensuring _ppulse_enabled and other defaults are written
// to the database so ppulse_get_active_popups() can find the popup.
function ppulse_set_default_meta_on_publish( $new_status, $old_status, $post ) {
    if ( $new_status !== 'publish' ) {
        return;
    }
    if ( $post->post_type !== PPULSE_POST_TYPE ) {
        return;
    }
    // Only run when transitioning TO publish for the first time, i.e. the
    // sentinel meta key does not exist yet.
    if ( metadata_exists( 'post', $post->ID, '_ppulse_enabled' ) ) {
        return;
    }

    // Write all canonical defaults so every meta key has a row in wp_postmeta.
    $defaults = ppulse_meta_defaults();
    ppulse_save_popup_meta( $post->ID, $defaults );

    // Bust the active-popup transient so the new popup is served immediately.
    delete_transient( 'ppulse_active_popups' );
}

// ─── Save post hook ───────────────────────────────────────────────────────────
function ppulse_handle_save_post( $post_id, $post ) {
    // Skip auto-saves, revisions, and wrong post type
    if (
        defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ||
        wp_is_post_revision( $post_id ) ||
        $post->post_type !== PPULSE_POST_TYPE
    ) {
        return;
    }

    // Verify nonce — only present when the classic meta-box iframe submits data
    if ( ! isset( $_POST['ppulse_settings_nonce'] ) ||
        ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ppulse_settings_nonce'] ) ), 'ppulse_save_settings_' . $post_id )
    ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Gather raw input from our form fields
    $raw    = [];
    $fields = array_keys( ppulse_meta_defaults() );

    foreach ( $fields as $field ) {
        $key = 'ppulse_' . $field;
        if ( isset( $_POST[ $key ] ) ) {
            $raw[ $field ] = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        }
    }

    // FIX: When the "Specific page IDs" radio option is selected the radio
    // posts the string 'specific', but the actual IDs live in a separate field
    // (ppulse_display_pages_ids).  Merge them so display_pages is stored as
    // the real comma-separated list of IDs (or falls back to 'all').
    if ( isset( $raw['display_pages'] ) && $raw['display_pages'] === 'specific' ) {
        $ids_raw = isset( $_POST['ppulse_display_pages_ids'] )
            ? sanitize_text_field( wp_unslash( $_POST['ppulse_display_pages_ids'] ) )
            : '';
        // Strip to clean integer list; fall back to 'all' if nothing valid provided
        $ids = array_filter( array_map( 'absint', explode( ',', $ids_raw ) ) );
        $raw['display_pages'] = ! empty( $ids ) ? implode( ',', $ids ) : 'all';
    }

    $sanitized = ppulse_sanitize_meta_input( $raw );

    ppulse_save_popup_meta( $post_id, $sanitized );

    // Bust transient so active popup list is refreshed
    delete_transient( 'ppulse_active_popups' );
}