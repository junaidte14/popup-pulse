<?php
/**
 * AJAX Handlers
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

// ─── Admin AJAX hooks ─────────────────────────────────────────────────────────
add_action( 'wp_ajax_ppulse_apply_template',    'ppulse_ajax_apply_template' );
add_action( 'wp_ajax_ppulse_save_as_template',  'ppulse_ajax_save_as_template' );
add_action( 'wp_ajax_ppulse_toggle_status',     'ppulse_ajax_toggle_status' );
add_action( 'wp_ajax_ppulse_duplicate_popup',   'ppulse_ajax_duplicate_popup' );
add_action( 'wp_ajax_ppulse_delete_popup',      'ppulse_ajax_delete_popup' );
add_action( 'wp_ajax_ppulse_save_settings',     'ppulse_ajax_save_settings' );
add_action( 'wp_ajax_ppulse_get_popup_data',    'ppulse_ajax_get_popup_data' );

// ─── Frontend AJAX (logged in + out) ─────────────────────────────────────────
add_action( 'wp_ajax_ppulse_record_impression',        'ppulse_ajax_record_impression' );
add_action( 'wp_ajax_nopriv_ppulse_record_impression', 'ppulse_ajax_record_impression' );

// ─── Helpers ──────────────────────────────────────────────────────────────────
function ppulse_ajax_verify( $nonce_action ) {
    if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => __( 'Security check failed.', 'ppulse' ) ], 403 );
    }
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( [ 'message' => __( 'Permission denied.', 'ppulse' ) ], 403 );
    }
}

// ─── Apply a built-in template to a popup ────────────────────────────────────
function ppulse_ajax_apply_template() {
    ppulse_ajax_verify( 'ppulse_admin_nonce' );

    $post_id  = absint( $_POST['post_id'] ?? 0 );
    $tpl_id   = sanitize_key( $_POST['template_id'] ?? '' );

    if ( ! $post_id || ! $tpl_id ) {
        wp_send_json_error( [ 'message' => __( 'Invalid parameters.', 'ppulse' ) ] );
    }

    // Check for user-saved template (post ID)
    if ( is_numeric( $tpl_id ) ) {
        $tpl_post = get_post( (int) $tpl_id );
        if ( ! $tpl_post || $tpl_post->post_type !== PPULSE_POST_TYPE ) {
            wp_send_json_error( [ 'message' => __( 'Template not found.', 'ppulse' ) ] );
        }
        $content  = $tpl_post->post_content;
        $tpl_meta = ppulse_get_popup_meta( $tpl_post->ID );
        unset( $tpl_meta['is_template'], $tpl_meta['template_id'] );
    } else {
        $templates = ppulse_builtin_templates();
        if ( ! isset( $templates[ $tpl_id ] ) ) {
            wp_send_json_error( [ 'message' => __( 'Built-in template not found.', 'ppulse' ) ] );
        }
        $tpl      = $templates[ $tpl_id ];
        $content  = $tpl['content'];
        $tpl_meta = $tpl['meta'];
    }

    // Update post content
    wp_update_post( [
        'ID'           => $post_id,
        'post_content' => $content,
    ] );

    // Update meta
    ppulse_save_popup_meta( $post_id, ppulse_sanitize_meta_input( $tpl_meta ) );

    wp_send_json_success( [
        'message' => __( 'Template applied successfully.', 'ppulse' ),
        'content' => $content,
        'meta'    => ppulse_get_popup_meta( $post_id ),
    ] );
}

// ─── Save current popup as a user template ────────────────────────────────────
function ppulse_ajax_save_as_template() {
    ppulse_ajax_verify( 'ppulse_admin_nonce' );

    $post_id   = absint( $_POST['post_id'] ?? 0 );
    $tpl_name  = sanitize_text_field( $_POST['template_name'] ?? '' );

    if ( ! $post_id || ! $tpl_name ) {
        wp_send_json_error( [ 'message' => __( 'A template name is required.', 'ppulse' ) ] );
    }

    $source = get_post( $post_id );
    if ( ! $source ) {
        wp_send_json_error( [ 'message' => __( 'Popup not found.', 'ppulse' ) ] );
    }

    // Create new popup post flagged as template
    $new_id = wp_insert_post( [
        'post_type'    => PPULSE_POST_TYPE,
        'post_status'  => 'publish',
        'post_title'   => $tpl_name,
        'post_content' => $source->post_content,
    ] );

    if ( is_wp_error( $new_id ) ) {
        wp_send_json_error( [ 'message' => $new_id->get_error_message() ] );
    }

    // Copy meta
    $meta = ppulse_get_popup_meta( $post_id );
    $meta['is_template'] = '1';
    $meta['enabled']     = '0';
    ppulse_save_popup_meta( $new_id, $meta );

    wp_send_json_success( [
        'message'     => __( 'Saved as template.', 'ppulse' ),
        'template_id' => $new_id,
        'name'        => $tpl_name,
    ] );
}

// ─── Toggle enabled / disabled status ────────────────────────────────────────
function ppulse_ajax_toggle_status() {
    ppulse_ajax_verify( 'ppulse_admin_nonce' );

    $post_id = absint( $_POST['post_id'] ?? 0 );
    if ( ! $post_id ) {
        wp_send_json_error( [ 'message' => __( 'Invalid popup ID.', 'ppulse' ) ] );
    }

    $current = get_post_meta( $post_id, '_ppulse_enabled', true );
    $new     = ( $current === '1' ) ? '0' : '1';
    update_post_meta( $post_id, '_ppulse_enabled', $new );
    delete_transient( 'ppulse_active_popups' );

    wp_send_json_success( [
        'enabled' => $new === '1',
        'label'   => $new === '1' ? __( 'Active', 'ppulse' ) : __( 'Disabled', 'ppulse' ),
    ] );
}

// ─── Duplicate popup ──────────────────────────────────────────────────────────
function ppulse_ajax_duplicate_popup() {
    ppulse_ajax_verify( 'ppulse_admin_nonce' );

    $post_id = absint( $_POST['post_id'] ?? 0 );
    $source  = get_post( $post_id );

    if ( ! $source || $source->post_type !== PPULSE_POST_TYPE ) {
        wp_send_json_error( [ 'message' => __( 'Popup not found.', 'ppulse' ) ] );
    }

    $new_id = wp_insert_post( [
        'post_type'    => PPULSE_POST_TYPE,
        'post_status'  => 'draft',
        /* translators: %s: original popup title */
        'post_title'   => sprintf( __( '%s (Copy)', 'ppulse' ), $source->post_title ),
        'post_content' => $source->post_content,
    ] );

    if ( is_wp_error( $new_id ) ) {
        wp_send_json_error( [ 'message' => $new_id->get_error_message() ] );
    }

    $meta = ppulse_get_popup_meta( $post_id );
    $meta['enabled'] = '0';
    ppulse_save_popup_meta( $new_id, $meta );

    wp_send_json_success( [
        'message'  => __( 'Popup duplicated.', 'ppulse' ),
        'edit_url' => get_edit_post_link( $new_id, 'raw' ),
        'new_id'   => $new_id,
    ] );
}

// ─── Delete popup ─────────────────────────────────────────────────────────────
function ppulse_ajax_delete_popup() {
    ppulse_ajax_verify( 'ppulse_admin_nonce' );

    $post_id = absint( $_POST['post_id'] ?? 0 );
    if ( ! $post_id ) {
        wp_send_json_error( [ 'message' => __( 'Invalid popup ID.', 'ppulse' ) ] );
    }

    $result = wp_delete_post( $post_id, true );
    if ( ! $result ) {
        wp_send_json_error( [ 'message' => __( 'Failed to delete popup.', 'ppulse' ) ] );
    }

    delete_transient( 'ppulse_active_popups' );
    wp_send_json_success( [ 'message' => __( 'Popup deleted.', 'ppulse' ) ] );
}

// ─── Save global settings ─────────────────────────────────────────────────────
function ppulse_ajax_save_settings() {
    ppulse_ajax_verify( 'ppulse_admin_nonce' );

    $settings = [
        'disable_on_mobile'  => isset( $_POST['disable_on_mobile'] ) && $_POST['disable_on_mobile'] === '1',
        'disable_on_tablet'  => isset( $_POST['disable_on_tablet'] ) && $_POST['disable_on_tablet'] === '1',
        'cookie_prefix'      => sanitize_key( $_POST['cookie_prefix'] ?? 'ppulse_' ) ?: 'ppulse_',
        'respect_dnt'        => isset( $_POST['respect_dnt'] ) && $_POST['respect_dnt'] === '1',
        'load_fontawesome'   => isset( $_POST['load_fontawesome'] ) && $_POST['load_fontawesome'] === '1',
    ];

    update_option( 'ppulse_settings', $settings );
    wp_send_json_success( [ 'message' => __( 'Settings saved.', 'ppulse' ) ] );
}

// ─── Get popup data (for preview) ────────────────────────────────────────────
function ppulse_ajax_get_popup_data() {
    ppulse_ajax_verify( 'ppulse_admin_nonce' );

    $post_id = absint( $_POST['post_id'] ?? 0 );
    $post    = get_post( $post_id );

    if ( ! $post || $post->post_type !== PPULSE_POST_TYPE ) {
        wp_send_json_error( [ 'message' => __( 'Popup not found.', 'ppulse' ) ] );
    }

    $meta = ppulse_get_popup_meta( $post_id );

    wp_send_json_success( [
        'id'      => $post_id,
        'title'   => $post->post_title,
        'content' => apply_filters( 'the_content', $post->post_content ),
        'meta'    => $meta,
    ] );
}

// ─── Record impression (frontend) ────────────────────────────────────────────
function ppulse_ajax_record_impression() {
    // Basic rate limit – one impression per popup per request
    $popup_id = absint( $_POST['popup_id'] ?? 0 );
    if ( ! $popup_id ) {
        wp_send_json_error();
    }

    check_ajax_referer( 'ppulse_frontend_' . $popup_id, 'nonce' );

    // For logged-in users, store in user meta
    if ( is_user_logged_in() ) {
        $user_id  = get_current_user_id();
        $key      = '_ppulse_shown_' . $popup_id;
        $count    = (int) get_user_meta( $user_id, $key, true );
        update_user_meta( $user_id, $key, $count + 1 );
    }

    wp_send_json_success();
}
