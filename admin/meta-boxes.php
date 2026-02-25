<?php
/**
 * Admin Meta Boxes — Popup Settings Panel
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'add_meta_boxes',         'ppulse_register_meta_boxes' );
add_action( 'admin_enqueue_scripts',  'ppulse_enqueue_admin_assets' );
add_action( 'enqueue_block_editor_assets', 'ppulse_enqueue_block_editor_assets' );

// ─── Register meta boxes ──────────────────────────────────────────────────────
function ppulse_register_meta_boxes() {
    add_meta_box(
        'ppulse_settings',
        __( 'Popup Settings', 'ppulse' ),
        'ppulse_render_settings_meta_box',
        PPULSE_POST_TYPE,
        'normal',
        'high'
    );
    add_meta_box(
        'ppulse_preview',
        __( 'Live Preview', 'ppulse' ),
        'ppulse_render_preview_meta_box',
        PPULSE_POST_TYPE,
        'side',
        'high'
    );
    add_meta_box(
        'ppulse_templates',
        __( 'Layout Templates', 'ppulse' ),
        'ppulse_render_templates_meta_box',
        PPULSE_POST_TYPE,
        'side',
        'default'
    );
}

// ─── Enqueue admin assets ─────────────────────────────────────────────────────
function ppulse_enqueue_admin_assets( $hook ) {
    global $post;

    // List table styles
    if ( $hook === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === PPULSE_POST_TYPE ) {
        wp_enqueue_style(
            'ppulse-admin',
            ppulse_asset_url( 'admin/assets/css/admin.css' ),
            [],
            ppulse_asset_ver( 'admin/assets/css/admin.css' )
        );
        wp_enqueue_script(
            'ppulse-admin',
            ppulse_asset_url( 'admin/assets/js/admin.js' ),
            [ 'jquery' ],
            ppulse_asset_ver( 'admin/assets/js/admin.js' ),
            true
        );
        wp_localize_script( 'ppulse-admin', 'ppulseAdmin', ppulse_admin_js_data() );
        return;
    }

    // Settings page
    if ( $hook === 'ppulse_popup_page_ppulse-settings' ) {
        wp_enqueue_style( 'ppulse-admin', ppulse_asset_url( 'admin/assets/css/admin.css' ), [], ppulse_asset_ver( 'admin/assets/css/admin.css' ) );
        wp_enqueue_script( 'ppulse-admin', ppulse_asset_url( 'admin/assets/js/admin.js' ), [ 'jquery' ], ppulse_asset_ver( 'admin/assets/js/admin.js' ), true );
        wp_localize_script( 'ppulse-admin', 'ppulseAdmin', ppulse_admin_js_data() );
        return;
    }

    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    if ( ! $post || $post->post_type !== PPULSE_POST_TYPE ) {
        return;
    }

    wp_enqueue_media();

    wp_enqueue_style(
        'ppulse-admin',
        ppulse_asset_url( 'admin/assets/css/admin.css' ),
        [ 'wp-components' ],
        ppulse_asset_ver( 'admin/assets/css/admin.css' )
    );
    wp_enqueue_script(
        'ppulse-admin',
        ppulse_asset_url( 'admin/assets/js/admin.js' ),
        [ 'jquery', 'wp-i18n', 'jquery-ui-tabs' ],
        ppulse_asset_ver( 'admin/assets/js/admin.js' ),
        true
    );
    wp_localize_script( 'ppulse-admin', 'ppulseAdmin', ppulse_admin_js_data( $post->ID ) );
}

function ppulse_enqueue_block_editor_assets() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->post_type !== PPULSE_POST_TYPE ) {
        return;
    }
    wp_enqueue_style(
        'ppulse-editor',
        ppulse_asset_url( 'admin/assets/css/admin.css' ),
        [ 'wp-edit-blocks' ],
        ppulse_asset_ver( 'admin/assets/css/admin.css' )
    );
}

// ─── JS data ──────────────────────────────────────────────────────────────────
function ppulse_admin_js_data( $post_id = 0 ) {
    $data = [
        'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'ppulse_admin_nonce' ),
        'postId'     => $post_id,
        'siteUrl'    => site_url(),
        'pluginUrl'  => PPULSE_URL,
        'strings'    => [
            'confirmDelete'    => __( 'Are you sure you want to delete this popup? This cannot be undone.', 'ppulse' ),
            'confirmApply'     => __( 'Applying this template will replace the current popup content. Continue?', 'ppulse' ),
            'saving'           => __( 'Saving…', 'ppulse' ),
            'saved'            => __( 'Saved!', 'ppulse' ),
            'error'            => __( 'An error occurred. Please try again.', 'ppulse' ),
            'templateName'     => __( 'Enter a name for this template:', 'ppulse' ),
            'templateSaved'    => __( 'Template saved!', 'ppulse' ),
            'active'           => __( 'Active', 'ppulse' ),
            'disabled'         => __( 'Disabled', 'ppulse' ),
        ],
        'templates'  => array_map( function ( $id, $tpl ) {
            return [ 'id' => $id, 'label' => $tpl['label'], 'description' => $tpl['description'], 'icon' => $tpl['icon'], 'bg' => $tpl['preview_bg'] ];
        }, array_keys( ppulse_builtin_templates() ), ppulse_builtin_templates() ),
    ];
    if ( $post_id ) {
        $data['meta'] = ppulse_get_popup_meta( $post_id );
    }
    return $data;
}

// ─── Main Settings Meta Box ───────────────────────────────────────────────────
function ppulse_render_settings_meta_box( $post ) {
    $meta = ppulse_get_popup_meta( $post->ID );
    wp_nonce_field( 'ppulse_save_settings_' . $post->ID, 'ppulse_settings_nonce' );

    $tabs = [
        'trigger'   => [ 'icon' => '⚡', 'label' => __( 'Trigger',   'ppulse' ) ],
        'behavior'  => [ 'icon' => '🔁', 'label' => __( 'Behavior',  'ppulse' ) ],
        'display'   => [ 'icon' => '📍', 'label' => __( 'Display',   'ppulse' ) ],
        'style'     => [ 'icon' => '🎨', 'label' => __( 'Style',     'ppulse' ) ],
        'targeting' => [ 'icon' => '🎯', 'label' => __( 'Targeting', 'ppulse' ) ],
    ];
    ?>
    <div class="ppulse-meta-wrap">
        <!-- Status toggle -->
        <div class="ppulse-status-row">
            <label class="ppulse-toggle-switch">
                <input type="checkbox" name="ppulse_enabled" id="ppulse_enabled"
                    value="1" <?php checked( $meta['enabled'] ); ?> />
                <span class="ppulse-toggle-slider"></span>
            </label>
            <span class="ppulse-status-label">
                <strong><?php echo $meta['enabled'] ? esc_html__( 'Active', 'ppulse' ) : esc_html__( 'Disabled', 'ppulse' ); ?></strong>
                &nbsp;—&nbsp;
                <span class="description"><?php esc_html_e( 'Enable or disable this popup on the frontend.', 'ppulse' ); ?></span>
            </span>
            <label class="ppulse-template-check">
                <input type="checkbox" name="ppulse_is_template" value="1" <?php checked( $meta['is_template'] ); ?> />
                &#9733; <?php esc_html_e( 'Mark as template', 'ppulse' ); ?>
            </label>
        </div>

        <!-- Tabs nav -->
        <div class="ppulse-tabs">
            <ul class="ppulse-tabs__nav" role="tablist">
                <?php foreach ( $tabs as $id => $tab ) : ?>
                <li class="ppulse-tabs__item" role="presentation">
                    <a href="#ppulse-tab-<?php echo esc_attr( $id ); ?>"
                       class="ppulse-tabs__link"
                       role="tab"
                       data-tab="<?php echo esc_attr( $id ); ?>"
                       aria-selected="false">
                        <span class="ppulse-tabs__icon"><?php echo esc_html( $tab['icon'] ); ?></span>
                        <?php echo esc_html( $tab['label'] ); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- TAB: Trigger -->
            <div id="ppulse-tab-trigger" class="ppulse-tabs__panel" role="tabpanel">
                <?php ppulse_field_select( 'trigger_type', __( 'Trigger Type', 'ppulse' ), $meta['trigger_type'], ppulse_trigger_labels(), __( 'When should this popup appear?', 'ppulse' ) ); ?>

                <div class="ppulse-conditional" data-show-when="trigger_type=delay,pageload">
                    <?php ppulse_field_number( 'trigger_delay', __( 'Delay (seconds)', 'ppulse' ), $meta['trigger_delay'], 0, 9999, __( 'Seconds to wait before showing the popup.', 'ppulse' ) ); ?>
                </div>

                <div class="ppulse-conditional" data-show-when="trigger_type=scroll">
                    <?php ppulse_field_range( 'trigger_scroll_pct', __( 'Scroll Percentage', 'ppulse' ), $meta['trigger_scroll_pct'], 1, 99, 1, '%', __( 'Show popup after the user scrolls this far down the page.', 'ppulse' ) ); ?>
                </div>

                <div class="ppulse-conditional" data-show-when="trigger_type=click">
                    <?php ppulse_field_text( 'trigger_click_selector', __( 'CSS Selector', 'ppulse' ), $meta['trigger_click_selector'], __( 'e.g. .my-button or #open-popup — clicking this element triggers the popup.', 'ppulse' ) ); ?>
                </div>

                <div class="ppulse-field ppulse-field--inline">
                    <label class="ppulse-toggle-switch">
                        <input type="checkbox" name="ppulse_exit_intent" value="1" <?php checked( $meta['exit_intent'] ); ?> />
                        <span class="ppulse-toggle-slider ppulse-toggle-slider--sm"></span>
                    </label>
                    <div class="ppulse-field__meta">
                        <strong><?php esc_html_e( 'Exit Intent', 'ppulse' ); ?></strong>
                        <p class="description"><?php esc_html_e( 'Also trigger when the user moves their cursor toward the browser top (desktop only).', 'ppulse' ); ?></p>
                    </div>
                </div>

                <?php ppulse_field_text_pair( 'schedule_start', 'schedule_end',
                    __( 'Schedule', 'ppulse' ),
                    $meta['schedule_start'], $meta['schedule_end'],
                    __( 'Start date (leave empty for no start limit)', 'ppulse' ),
                    __( 'End date (leave empty for no end limit)', 'ppulse' ),
                    'date'
                ); ?>
            </div>

            <!-- TAB: Behavior -->
            <div id="ppulse-tab-behavior" class="ppulse-tabs__panel" role="tabpanel">
                <?php ppulse_field_select( 'frequency_type', __( 'Show Frequency', 'ppulse' ), $meta['frequency_type'], ppulse_frequency_labels(), __( 'How often should this popup appear to the same user?', 'ppulse' ) ); ?>

                <div class="ppulse-conditional" data-show-when="frequency_type=limited">
                    <?php ppulse_field_number( 'frequency_limit', __( 'Max Impressions', 'ppulse' ), $meta['frequency_limit'], 1, 999, __( 'Maximum number of times to show the popup to a single user.', 'ppulse' ) ); ?>
                </div>

                <div class="ppulse-conditional" data-show-when="frequency_type=once,limited">
                    <?php ppulse_field_number( 'frequency_days', __( 'Cookie Duration (days)', 'ppulse' ), $meta['frequency_days'], 1, 365, __( 'How long the impression cookie lasts in the user\'s browser.', 'ppulse' ) ); ?>
                </div>

                <hr class="ppulse-sep"/>

                <div class="ppulse-field ppulse-field--inline">
                    <label class="ppulse-toggle-switch">
                        <input type="checkbox" name="ppulse_show_close_btn" value="1" <?php checked( $meta['show_close_btn'] ); ?> />
                        <span class="ppulse-toggle-slider ppulse-toggle-slider--sm"></span>
                    </label>
                    <div class="ppulse-field__meta">
                        <strong><?php esc_html_e( 'Show Close Button', 'ppulse' ); ?></strong>
                    </div>
                </div>

                <div class="ppulse-field ppulse-field--inline">
                    <label class="ppulse-toggle-switch">
                        <input type="checkbox" name="ppulse_overlay_click_close" value="1" <?php checked( $meta['overlay_click_close'] ); ?> />
                        <span class="ppulse-toggle-slider ppulse-toggle-slider--sm"></span>
                    </label>
                    <div class="ppulse-field__meta">
                        <strong><?php esc_html_e( 'Close on Overlay Click', 'ppulse' ); ?></strong>
                    </div>
                </div>

                <hr class="ppulse-sep"/>

                <div class="ppulse-field ppulse-field--inline">
                    <label class="ppulse-toggle-switch">
                        <input type="checkbox" name="ppulse_auto_close_enabled" id="ppulse_auto_close_enabled"
                            value="1" <?php checked( $meta['auto_close_enabled'] ); ?> />
                        <span class="ppulse-toggle-slider ppulse-toggle-slider--sm"></span>
                    </label>
                    <div class="ppulse-field__meta">
                        <strong><?php esc_html_e( 'Auto-Close After Delay', 'ppulse' ); ?></strong>
                        <p class="description"><?php esc_html_e( 'Automatically close the popup after a set time.', 'ppulse' ); ?></p>
                    </div>
                </div>

                <div class="ppulse-conditional" data-show-when="auto_close_enabled=1">
                    <?php ppulse_field_number( 'auto_close_seconds', __( 'Auto-close delay (seconds)', 'ppulse' ), $meta['auto_close_seconds'], 1, 300, '' ); ?>
                </div>
            </div>

            <!-- TAB: Display -->
            <div id="ppulse-tab-display" class="ppulse-tabs__panel" role="tabpanel">
                <?php ppulse_field_select( 'position', __( 'Position', 'ppulse' ), $meta['position'], ppulse_position_labels(), __( 'Where on screen should the popup appear?', 'ppulse' ) ); ?>

                <div class="ppulse-field ppulse-two-col">
                    <?php ppulse_field_number( 'position_offset_y', __( 'Vertical offset (px)', 'ppulse' ), $meta['position_offset_y'], -999, 999, '' ); ?>
                    <?php ppulse_field_number( 'position_offset_x', __( 'Horizontal offset (px)', 'ppulse' ), $meta['position_offset_x'], -999, 999, '' ); ?>
                </div>

                <hr class="ppulse-sep"/>
                <h4><?php esc_html_e( 'Display Pages', 'ppulse' ); ?></h4>
                <div class="ppulse-field">
                    <label>
                        <input type="radio" name="ppulse_display_pages" value="all" <?php checked( $meta['display_pages'], 'all' ); ?> />
                        <?php esc_html_e( 'All pages & posts', 'ppulse' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="ppulse_display_pages" value="front" <?php checked( $meta['display_pages'], 'front' ); ?> />
                        <?php esc_html_e( 'Front page only', 'ppulse' ); ?>
                    </label>
                    <label>
                        <input type="radio" name="ppulse_display_pages" value="specific" <?php checked( ! in_array( $meta['display_pages'], [ 'all', 'front' ], true ) && $meta['display_pages'] !== '' ? 'specific' : '', 'specific' ); ?> />
                        <?php esc_html_e( 'Specific post/page IDs (comma-separated)', 'ppulse' ); ?>
                    </label>
                    <div class="ppulse-conditional" data-show-when="display_pages=specific">
                        <input type="text" name="ppulse_display_pages_ids"
                            class="widefat"
                            placeholder="1, 42, 55"
                            value="<?php echo esc_attr( ! in_array( $meta['display_pages'], [ 'all', 'front' ], true ) ? $meta['display_pages'] : '' ); ?>" />
                        <p class="description"><?php esc_html_e( 'Enter the IDs of the pages or posts where this popup should appear.', 'ppulse' ); ?></p>
                    </div>
                </div>
            </div>

            <!-- TAB: Style -->
            <div id="ppulse-tab-style" class="ppulse-tabs__panel" role="tabpanel">
                <?php ppulse_field_number( 'popup_max_width', __( 'Max Width (px)', 'ppulse' ), $meta['popup_max_width'], 200, 9999, __( 'Maximum popup width. Use 9999 for full-width.', 'ppulse' ) ); ?>

                <div class="ppulse-two-col">
                    <div class="ppulse-field">
                        <label><?php esc_html_e( 'Popup Background', 'ppulse' ); ?></label>
                        <div class="ppulse-color-wrap">
                            <input type="text" name="ppulse_popup_bg_color" class="ppulse-color-picker"
                                value="<?php echo esc_attr( $meta['popup_bg_color'] ); ?>" />
                        </div>
                    </div>
                    <div class="ppulse-field">
                        <label><?php esc_html_e( 'Overlay Color', 'ppulse' ); ?></label>
                        <div class="ppulse-color-wrap">
                            <input type="text" name="ppulse_overlay_color" class="ppulse-color-picker"
                                value="<?php echo esc_attr( $meta['overlay_color'] ); ?>" />
                        </div>
                    </div>
                </div>

                <?php ppulse_field_range( 'overlay_opacity', __( 'Overlay Opacity', 'ppulse' ), $meta['overlay_opacity'], 0, 1, 0.05, '', __( 'Set to 0 to hide the overlay entirely.', 'ppulse' ) ); ?>

                <hr class="ppulse-sep"/>

                <div class="ppulse-field ppulse-field--inline">
                    <label class="ppulse-toggle-switch">
                        <input type="checkbox" name="ppulse_animate_in" value="1" <?php checked( $meta['animate_in'] ); ?> />
                        <span class="ppulse-toggle-slider ppulse-toggle-slider--sm"></span>
                    </label>
                    <div class="ppulse-field__meta">
                        <strong><?php esc_html_e( 'Animate popup in', 'ppulse' ); ?></strong>
                    </div>
                </div>

                <?php ppulse_field_select( 'animation_style', __( 'Animation Style', 'ppulse' ), $meta['animation_style'], [
                    'fade'       => __( 'Fade', 'ppulse' ),
                    'slide-up'   => __( 'Slide Up', 'ppulse' ),
                    'slide-down' => __( 'Slide Down', 'ppulse' ),
                    'zoom'       => __( 'Zoom In', 'ppulse' ),
                    'bounce'     => __( 'Bounce', 'ppulse' ),
                ], '' ); ?>

                <hr class="ppulse-sep"/>
                <h4><?php esc_html_e( 'Full-Width Image Cover', 'ppulse' ); ?></h4>
                <p class="description"><?php esc_html_e( 'Display a full-width background image behind the popup content.', 'ppulse' ); ?></p>

                <div class="ppulse-field ppulse-field--inline">
                    <label class="ppulse-toggle-switch">
                        <input type="checkbox" name="ppulse_full_width_image" id="ppulse_full_width_image"
                            value="1" <?php checked( $meta['full_width_image'] ); ?> />
                        <span class="ppulse-toggle-slider ppulse-toggle-slider--sm"></span>
                    </label>
                    <div class="ppulse-field__meta">
                        <strong><?php esc_html_e( 'Enable Image Cover', 'ppulse' ); ?></strong>
                    </div>
                </div>

                <div class="ppulse-conditional" data-show-when="full_width_image=1">
                    <div class="ppulse-field">
                        <label><?php esc_html_e( 'Cover Image', 'ppulse' ); ?></label>
                        <div class="ppulse-image-uploader">
                            <div class="ppulse-image-preview <?php echo $meta['full_width_image_url'] ? 'has-image' : ''; ?>">
                                <?php if ( $meta['full_width_image_url'] ) : ?>
                                <img src="<?php echo esc_url( $meta['full_width_image_url'] ); ?>" alt="" />
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="ppulse_full_width_image_url" id="ppulse_full_width_image_url"
                                value="<?php echo esc_attr( $meta['full_width_image_url'] ); ?>" />
                            <input type="hidden" name="ppulse_full_width_image_id" id="ppulse_full_width_image_id"
                                value="<?php echo esc_attr( $meta['full_width_image_id'] ); ?>" />
                            <div class="ppulse-image-btns">
                                <button type="button" class="button ppulse-upload-image"
                                    data-target="ppulse_full_width_image_url"
                                    data-id-target="ppulse_full_width_image_id"
                                    data-preview=".ppulse-image-preview">
                                    <?php esc_html_e( 'Choose Image', 'ppulse' ); ?>
                                </button>
                                <?php if ( $meta['full_width_image_url'] ) : ?>
                                <button type="button" class="button ppulse-remove-image">
                                    <?php esc_html_e( 'Remove', 'ppulse' ); ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Targeting -->
            <div id="ppulse-tab-targeting" class="ppulse-tabs__panel" role="tabpanel">
                <h4><?php esc_html_e( 'Device Targeting', 'ppulse' ); ?></h4>
                <div class="ppulse-device-grid">
                    <?php foreach ( [
                        [ 'show_on_desktop', '🖥️', __( 'Desktop', 'ppulse' ) ],
                        [ 'show_on_tablet',  '📱', __( 'Tablet', 'ppulse' ) ],
                        [ 'show_on_mobile',  '📲', __( 'Mobile', 'ppulse' ) ],
                    ] as $device ) : ?>
                    <label class="ppulse-device-card <?php echo $meta[ $device[0] ] ? 'is-checked' : ''; ?>">
                        <input type="checkbox" name="ppulse_<?php echo esc_attr( $device[0] ); ?>"
                            value="1" <?php checked( $meta[ $device[0] ] ); ?> />
                        <span class="ppulse-device-icon"><?php echo esc_html( $device[1] ); ?></span>
                        <span><?php echo esc_html( $device[2] ); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div><!-- .ppulse-tabs -->
    </div><!-- .ppulse-meta-wrap -->
    <?php
}

// ─── Preview Meta Box ──────────────────────────────────────────────────────────
function ppulse_render_preview_meta_box( $post ) {
    ?>
    <div class="ppulse-preview-box">
        <p class="description"><?php esc_html_e( 'See how this popup will look to visitors.', 'ppulse' ); ?></p>
        <button type="button" class="button button-secondary ppulse-btn-preview" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
            <?php esc_html_e( '👁️ Preview Popup', 'ppulse' ); ?>
        </button>
        <button type="button" class="button ppulse-btn-save-template" data-post-id="<?php echo esc_attr( $post->ID ); ?>">
            <?php esc_html_e( '⭐ Save as Template', 'ppulse' ); ?>
        </button>
    </div>
    <?php
}

// ─── Templates Meta Box ────────────────────────────────────────────────────────
function ppulse_render_templates_meta_box( $post ) {
    $builtin = ppulse_builtin_templates();
    $saved   = ppulse_get_saved_templates();
    ?>
    <div class="ppulse-tpl-panel">
        <p class="description"><?php esc_html_e( 'Apply a pre-built or saved template to quickly design your popup.', 'ppulse' ); ?></p>

        <?php if ( $builtin ) : ?>
        <h4><?php esc_html_e( 'Pre-built Templates', 'ppulse' ); ?></h4>
        <div class="ppulse-tpl-grid">
            <?php foreach ( $builtin as $id => $tpl ) : ?>
            <div class="ppulse-tpl-card" data-tpl-id="<?php echo esc_attr( $id ); ?>"
                 style="--tpl-bg:<?php echo esc_attr( $tpl['preview_bg'] ); ?>">
                <span class="ppulse-tpl-card__icon dashicons <?php echo esc_attr( $tpl['icon'] ); ?>"></span>
                <span class="ppulse-tpl-card__name"><?php echo esc_html( $tpl['label'] ); ?></span>
                <span class="ppulse-tpl-card__desc"><?php echo esc_html( $tpl['description'] ); ?></span>
                <button type="button" class="button ppulse-apply-template"
                    data-tpl-id="<?php echo esc_attr( $id ); ?>"
                    data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                    <?php esc_html_e( 'Apply', 'ppulse' ); ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $saved ) : ?>
        <h4><?php esc_html_e( 'My Templates', 'ppulse' ); ?></h4>
        <div class="ppulse-tpl-grid">
            <?php foreach ( $saved as $tpl_post ) : ?>
            <?php if ( (int) $tpl_post->ID === (int) $post->ID ) continue; ?>
            <div class="ppulse-tpl-card ppulse-tpl-card--user">
                <span class="ppulse-tpl-card__icon">⭐</span>
                <span class="ppulse-tpl-card__name"><?php echo esc_html( $tpl_post->post_title ); ?></span>
                <button type="button" class="button ppulse-apply-template"
                    data-tpl-id="<?php echo esc_attr( $tpl_post->ID ); ?>"
                    data-post-id="<?php echo esc_attr( $post->ID ); ?>">
                    <?php esc_html_e( 'Apply', 'ppulse' ); ?>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ─── Field helpers ─────────────────────────────────────────────────────────────
function ppulse_field_select( $name, $label, $value, $options, $desc = '' ) {
    ?>
    <div class="ppulse-field">
        <label for="ppulse_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
        <select name="ppulse_<?php echo esc_attr( $name ); ?>" id="ppulse_<?php echo esc_attr( $name ); ?>">
            <?php foreach ( $options as $opt_val => $opt_label ) : ?>
            <option value="<?php echo esc_attr( $opt_val ); ?>" <?php selected( $value, $opt_val ); ?>>
                <?php echo esc_html( $opt_label ); ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if ( $desc ) : ?><p class="description"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
    </div>
    <?php
}

function ppulse_field_number( $name, $label, $value, $min, $max, $desc = '' ) {
    ?>
    <div class="ppulse-field">
        <label for="ppulse_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
        <input type="number" name="ppulse_<?php echo esc_attr( $name ); ?>"
            id="ppulse_<?php echo esc_attr( $name ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            min="<?php echo esc_attr( $min ); ?>"
            max="<?php echo esc_attr( $max ); ?>"
            class="small-text" />
        <?php if ( $desc ) : ?><p class="description"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
    </div>
    <?php
}

function ppulse_field_range( $name, $label, $value, $min, $max, $step, $unit = '', $desc = '' ) {
    ?>
    <div class="ppulse-field ppulse-field--range">
        <label for="ppulse_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
        <div class="ppulse-range-wrap">
            <input type="range" name="ppulse_<?php echo esc_attr( $name ); ?>"
                id="ppulse_<?php echo esc_attr( $name ); ?>"
                value="<?php echo esc_attr( $value ); ?>"
                min="<?php echo esc_attr( $min ); ?>"
                max="<?php echo esc_attr( $max ); ?>"
                step="<?php echo esc_attr( $step ); ?>"
                class="ppulse-range" />
            <span class="ppulse-range-val"><?php echo esc_html( $value ); ?></span><?php echo esc_html( $unit ); ?>
        </div>
        <?php if ( $desc ) : ?><p class="description"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
    </div>
    <?php
}

function ppulse_field_text( $name, $label, $value, $desc = '' ) {
    ?>
    <div class="ppulse-field">
        <label for="ppulse_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
        <input type="text" name="ppulse_<?php echo esc_attr( $name ); ?>"
            id="ppulse_<?php echo esc_attr( $name ); ?>"
            value="<?php echo esc_attr( $value ); ?>"
            class="widefat" />
        <?php if ( $desc ) : ?><p class="description"><?php echo esc_html( $desc ); ?></p><?php endif; ?>
    </div>
    <?php
}

function ppulse_field_text_pair( $name1, $name2, $label, $val1, $val2, $ph1, $ph2, $type = 'text' ) {
    ?>
    <div class="ppulse-field ppulse-two-col">
        <div>
            <label for="ppulse_<?php echo esc_attr( $name1 ); ?>"><?php echo esc_html( $label ) . ' — ' . esc_html( $ph1 ); ?></label>
            <input type="<?php echo esc_attr( $type ); ?>"
                name="ppulse_<?php echo esc_attr( $name1 ); ?>"
                id="ppulse_<?php echo esc_attr( $name1 ); ?>"
                value="<?php echo esc_attr( $val1 ); ?>"
                class="widefat" />
        </div>
        <div>
            <label for="ppulse_<?php echo esc_attr( $name2 ); ?>"><?php echo esc_html( $ph2 ); ?></label>
            <input type="<?php echo esc_attr( $type ); ?>"
                name="ppulse_<?php echo esc_attr( $name2 ); ?>"
                id="ppulse_<?php echo esc_attr( $name2 ); ?>"
                value="<?php echo esc_attr( $val2 ); ?>"
                class="widefat" />
        </div>
    </div>
    <?php
}

// ─── Position labels ──────────────────────────────────────────────────────────
function ppulse_position_labels() {
    return [
        'center'       => __( 'Center (modal)', 'ppulse' ),
        'top-left'     => __( 'Top Left', 'ppulse' ),
        'top-right'    => __( 'Top Right', 'ppulse' ),
        'bottom-left'  => __( 'Bottom Left', 'ppulse' ),
        'bottom-right' => __( 'Bottom Right', 'ppulse' ),
        'top-bar'      => __( 'Top Bar (full width)', 'ppulse' ),
        'bottom-bar'   => __( 'Bottom Bar (full width)', 'ppulse' ),
    ];
}
