<?php
/**
 * Uninstall Popup Pulse
 * Runs only when the plugin is deleted via WP admin.
 *
 * @package PopupPulse
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove all popup posts and their meta
$popups = get_posts( [
    'post_type'      => 'ppulse_popup',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
] );

foreach ( $popups as $popup_id ) {
    wp_delete_post( $popup_id, true );
}

// Remove options
delete_option( 'ppulse_version' );
delete_option( 'ppulse_settings' );

// Remove transients
delete_transient( 'ppulse_active_popups' );

// Remove user meta (impression tracking)
global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '_ppulse_shown_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
