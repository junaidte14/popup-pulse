<?php
/**
 * Admin Pages — Dashboard & Global Settings
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'ppulse_register_admin_pages' );
add_action( 'admin_notices', 'ppulse_maybe_show_welcome_notice' );
add_action( 'admin_init', 'ppulse_dismiss_welcome_notice' );

// ─── Register sub-pages ───────────────────────────────────────────────────────
function ppulse_register_admin_pages() {
    add_submenu_page(
        'edit.php?post_type=' . PPULSE_POST_TYPE,
        __( 'Popup Pulse Settings', 'ppulse' ),
        __( 'Settings', 'ppulse' ),
        'manage_options',
        'ppulse-settings',
        'ppulse_render_settings_page'
    );
}

// ─── Settings Page ─────────────────────────────────────────────────────────────
function ppulse_render_settings_page() {
    $settings = ppulse_get_settings();
    ?>
    <div class="wrap ppulse-settings-wrap">
        <div class="ppulse-page-header">
            <div class="ppulse-page-header__logo">
                <span class="dashicons dashicons-admin-site-alt3"></span>
            </div>
            <div>
                <h1><?php esc_html_e( 'Popup Pulse — Settings', 'ppulse' ); ?></h1>
                <p><?php esc_html_e( 'Global configuration for all popups on this site.', 'ppulse' ); ?></p>
            </div>
        </div>

        <div class="ppulse-card">
            <form id="ppulse-settings-form" method="post">
                <?php wp_nonce_field( 'ppulse_admin_nonce', 'nonce' ); ?>
                <input type="hidden" name="action" value="ppulse_save_settings" />

                <h2><?php esc_html_e( 'Device Rules', 'ppulse' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Disable all popups on mobile', 'ppulse' ); ?></th>
                        <td>
                            <label class="ppulse-toggle-switch">
                                <input type="checkbox" name="disable_on_mobile" value="1"
                                    <?php checked( $settings['disable_on_mobile'] ); ?> />
                                <span class="ppulse-toggle-slider"></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'Overrides per-popup device targeting. Useful for performance.', 'ppulse' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Disable all popups on tablet', 'ppulse' ); ?></th>
                        <td>
                            <label class="ppulse-toggle-switch">
                                <input type="checkbox" name="disable_on_tablet" value="1"
                                    <?php checked( $settings['disable_on_tablet'] ); ?> />
                                <span class="ppulse-toggle-slider"></span>
                            </label>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Cookie Settings', 'ppulse' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><label for="ppulse-cookie-prefix"><?php esc_html_e( 'Cookie prefix', 'ppulse' ); ?></label></th>
                        <td>
                            <input type="text" id="ppulse-cookie-prefix" name="cookie_prefix"
                                value="<?php echo esc_attr( $settings['cookie_prefix'] ); ?>"
                                class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Prefix for all impression-tracking cookies. Change only if you have conflicts.', 'ppulse' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Respect Do Not Track', 'ppulse' ); ?></th>
                        <td>
                            <label class="ppulse-toggle-switch">
                                <input type="checkbox" name="respect_dnt" value="1"
                                    <?php checked( $settings['respect_dnt'] ); ?> />
                                <span class="ppulse-toggle-slider"></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'When enabled, popups with "once" or "limited" frequency will always show to users with DNT set (cookie can\'t be saved).', 'ppulse' ); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e( 'Asset Loading', 'ppulse' ); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'Load Font Awesome', 'ppulse' ); ?></th>
                        <td>
                            <label class="ppulse-toggle-switch">
                                <input type="checkbox" name="load_fontawesome" value="1"
                                    <?php checked( $settings['load_fontawesome'] ); ?> />
                                <span class="ppulse-toggle-slider"></span>
                            </label>
                            <p class="description"><?php esc_html_e( 'Uncheck if your theme already loads Font Awesome to avoid duplicate loading.', 'ppulse' ); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary ppulse-save-settings-btn">
                        <?php esc_html_e( 'Save Settings', 'ppulse' ); ?>
                    </button>
                    <span class="ppulse-save-feedback" style="display:none; margin-left:12px; color:#00a32a;">
                        ✓ <?php esc_html_e( 'Saved!', 'ppulse' ); ?>
                    </span>
                </p>
            </form>
        </div>

        <!-- Stats card -->
        <div class="ppulse-card ppulse-card--stats">
            <h2><?php esc_html_e( 'Quick Stats', 'ppulse' ); ?></h2>
            <?php
            $active = new WP_Query( [
                'post_type'      => PPULSE_POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => [ [ 'key' => '_ppulse_enabled', 'value' => '1' ] ],
                'no_found_rows'  => false,
                'fields'         => 'ids',
            ] );
            $total = wp_count_posts( PPULSE_POST_TYPE );
            $templates_q = new WP_Query( [
                'post_type'      => PPULSE_POST_TYPE,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'meta_query'     => [ [ 'key' => '_ppulse_is_template', 'value' => '1' ] ],
                'fields'         => 'ids',
                'no_found_rows'  => false,
            ] );
            ?>
            <div class="ppulse-stats-grid">
                <div class="ppulse-stat">
                    <span class="ppulse-stat__num"><?php echo (int) ( $total->publish + $total->draft ); ?></span>
                    <span class="ppulse-stat__label"><?php esc_html_e( 'Total Popups', 'ppulse' ); ?></span>
                </div>
                <div class="ppulse-stat ppulse-stat--green">
                    <span class="ppulse-stat__num"><?php echo (int) $active->found_posts; ?></span>
                    <span class="ppulse-stat__label"><?php esc_html_e( 'Active', 'ppulse' ); ?></span>
                </div>
                <div class="ppulse-stat ppulse-stat--gold">
                    <span class="ppulse-stat__num"><?php echo (int) $templates_q->found_posts; ?></span>
                    <span class="ppulse-stat__label"><?php esc_html_e( 'Templates', 'ppulse' ); ?></span>
                </div>
                <div class="ppulse-stat ppulse-stat--blue">
                    <span class="ppulse-stat__num"><?php echo count( ppulse_builtin_templates() ); ?></span>
                    <span class="ppulse-stat__label"><?php esc_html_e( 'Built-in Templates', 'ppulse' ); ?></span>
                </div>
            </div>
            <p>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . PPULSE_POST_TYPE ) ); ?>" class="button">
                    <?php esc_html_e( 'Manage All Popups →', 'ppulse' ); ?>
                </a>
            </p>
        </div>
    </div>
    <?php
}

// ─── Welcome notice on first activation ──────────────────────────────────────
function ppulse_maybe_show_welcome_notice() {
    $screen = get_current_screen();
    if ( ! $screen ) {
        return;
    }
    if ( get_option( 'ppulse_welcome_dismissed' ) ) {
        return;
    }
    if ( strpos( $screen->id, PPULSE_POST_TYPE ) === false && $screen->id !== 'dashboard' ) {
        return;
    }
    ?>
    <div class="notice notice-info ppulse-welcome-notice is-dismissible">
        <div style="display:flex; align-items:center; gap:16px; padding:8px 0;">
            <span style="font-size:32px;">🎉</span>
            <div>
                <p><strong><?php esc_html_e( 'Welcome to Popup Pulse!', 'ppulse' ); ?></strong>
                <?php esc_html_e( 'You\'re all set. Create your first popup using the Gutenberg editor, choose a pre-built template to get started fast, and configure triggers and display rules from the Popup Settings panel.', 'ppulse' ); ?></p>
                <p>
                    <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . PPULSE_POST_TYPE ) ); ?>">
                        <?php esc_html_e( '+ Create First Popup', 'ppulse' ); ?>
                    </a>
                    &nbsp;
                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ppulse_dismiss_welcome', '1' ), 'ppulse_dismiss_welcome' ) ); ?>">
                        <?php esc_html_e( 'Dismiss', 'ppulse' ); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
    <?php
}

function ppulse_dismiss_welcome_notice() {
    if ( isset( $_GET['ppulse_dismiss_welcome'] ) &&
        wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'ppulse_dismiss_welcome' )
    ) {
        update_option( 'ppulse_welcome_dismissed', '1' );
    }
}
