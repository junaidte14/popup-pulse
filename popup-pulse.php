<?php
/**
 * Plugin Name:       Popup Pulse
 * Plugin URI:        https://example.com/popup-pulse
 * Description:       Design beautiful popups with the Gutenberg editor. Control triggers, repetitions, exit intent, auto-close, full-width covers, and pre-built templates — all from a clean, intuitive interface.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Popup Pulse
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ppulse
 * Domain Path:       /languages
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'PPULSE_VERSION',   '1.0.0' );
define( 'PPULSE_FILE',      __FILE__ );
define( 'PPULSE_DIR',       plugin_dir_path( __FILE__ ) );
define( 'PPULSE_URL',       plugin_dir_url( __FILE__ ) );
define( 'PPULSE_POST_TYPE', 'ppulse_popup' );
define( 'PPULSE_TAX',       'ppulse_category' );

// ─── Bootstrap ────────────────────────────────────────────────────────────────
require_once PPULSE_DIR . 'includes/helpers.php';
require_once PPULSE_DIR . 'includes/post-type.php';
require_once PPULSE_DIR . 'includes/meta.php';
require_once PPULSE_DIR . 'includes/templates.php';
require_once PPULSE_DIR . 'includes/ajax.php';
require_once PPULSE_DIR . 'public/render.php';

if ( is_admin() ) {
    require_once PPULSE_DIR . 'admin/meta-boxes.php';
    require_once PPULSE_DIR . 'admin/admin-page.php';
}

// ─── Activation / Deactivation ────────────────────────────────────────────────
register_activation_hook( PPULSE_FILE,   'ppulse_activate' );
register_deactivation_hook( PPULSE_FILE, 'ppulse_deactivate' );

function ppulse_activate() {
    ppulse_register_post_type();
    ppulse_register_taxonomy();
    flush_rewrite_rules();
    add_option( 'ppulse_version', PPULSE_VERSION );
    add_option( 'ppulse_settings', ppulse_default_settings() );
}

function ppulse_deactivate() {
    flush_rewrite_rules();
}

// ─── Load Text Domain ─────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function () {
    load_plugin_textdomain( 'ppulse', false, dirname( plugin_basename( PPULSE_FILE ) ) . '/languages' );
} );

// ─── Plugin row action links ──────────────────────────────────────────────────
add_filter( 'plugin_action_links_' . plugin_basename( PPULSE_FILE ), function ( $links ) {
    $url      = admin_url( 'edit.php?post_type=' . PPULSE_POST_TYPE );
    $settings = admin_url( 'edit.php?post_type=' . PPULSE_POST_TYPE . '&page=ppulse-settings' );
    array_unshift(
        $links,
        '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Popups', 'ppulse' ) . '</a>',
        '<a href="' . esc_url( $settings ) . '">' . esc_html__( 'Settings', 'ppulse' ) . '</a>'
    );
    return $links;
} );
