<?php
/**
 * Frontend Popup Rendering & Asset Loading
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'ppulse_enqueue_frontend_assets' );
add_action( 'wp_footer',          'ppulse_render_frontend_popups', 99 );

// ─── Enqueue frontend assets ──────────────────────────────────────────────────
function ppulse_enqueue_frontend_assets() {
    // Only load if there are active popups
    if ( ! ppulse_has_active_popups() ) {
        return;
    }

    wp_enqueue_style(
        'ppulse-frontend',
        ppulse_asset_url( 'public/assets/css/frontend.css' ),
        [],
        ppulse_asset_ver( 'public/assets/css/frontend.css' )
    );

    wp_enqueue_script(
        'ppulse-frontend',
        ppulse_asset_url( 'public/assets/js/frontend.js' ),
        [],
        ppulse_asset_ver( 'public/assets/js/frontend.js' ),
        true
    );

    wp_localize_script( 'ppulse-frontend', 'ppulseFE', ppulse_frontend_js_data() );
}

// ─── Check if any active popups exist (cached) ───────────────────────────────
function ppulse_has_active_popups() {
    $cached = get_transient( 'ppulse_active_popups' );
    if ( $cached !== false ) {
        return (bool) $cached;
    }

    $popups = ppulse_get_active_popups();
    $has    = ! empty( $popups );
    set_transient( 'ppulse_active_popups', $has ? 1 : 0, 5 * MINUTE_IN_SECONDS );
    return $has;
}

// ─── Frontend JS config ───────────────────────────────────────────────────────
function ppulse_frontend_js_data() {
    $settings = ppulse_get_settings();

    return [
        'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
        'cookiePrefix' => $settings['cookie_prefix'],
        'respectDnt'   => $settings['respect_dnt'],
        'popups'       => ppulse_get_frontend_popup_configs(),
    ];
}

// ─── Build popup config array for JS ─────────────────────────────────────────
function ppulse_get_frontend_popup_configs() {
    $popups  = ppulse_get_active_popups();
    $configs = [];

    foreach ( $popups as $popup ) {
        $meta = ppulse_get_popup_meta( $popup->ID );

        // Check schedule
        if ( ! ppulse_popup_is_in_schedule( $meta ) ) {
            continue;
        }

        // Device check
        if ( ! ppulse_popup_passes_device_check( $meta ) ) {
            continue;
        }

        // Display pages check
        if ( ! ppulse_popup_shows_on_current_page( $meta ) ) {
            continue;
        }

        $configs[] = [
            'id'                    => $popup->ID,
            'nonce'                 => wp_create_nonce( 'ppulse_frontend_' . $popup->ID ),
            'triggerType'           => $meta['trigger_type'],
            'triggerDelay'          => (int) $meta['trigger_delay'] * 1000, // ms
            'triggerScrollPct'      => (int) $meta['trigger_scroll_pct'],
            'triggerClickSelector'  => $meta['trigger_click_selector'],
            'exitIntent'            => (bool) $meta['exit_intent'],
            'frequencyType'         => $meta['frequency_type'],
            'frequencyLimit'        => (int) $meta['frequency_limit'],
            'frequencyDays'         => (int) $meta['frequency_days'],
            'autoCloseEnabled'      => (bool) $meta['auto_close_enabled'],
            'autoCloseSeconds'      => (int) $meta['auto_close_seconds'] * 1000,
            'showCloseBtn'          => (bool) $meta['show_close_btn'],
            'overlayClickClose'     => (bool) $meta['overlay_click_close'],
            'position'              => $meta['position'],
            'animateIn'             => (bool) $meta['animate_in'],
            'animationStyle'        => $meta['animation_style'],
            'fullWidthImage'        => (bool) $meta['full_width_image'],
            'fullWidthImageUrl'     => $meta['full_width_image_url'],
            'overlayOpacity'        => (float) $meta['overlay_opacity'],
        ];
    }

    return $configs;
}

// ─── Schedule check ───────────────────────────────────────────────────────────
function ppulse_popup_is_in_schedule( $meta ) {
    $today = current_time( 'Y-m-d' );

    if ( $meta['schedule_start'] && $today < $meta['schedule_start'] ) {
        return false;
    }
    if ( $meta['schedule_end'] && $today > $meta['schedule_end'] ) {
        return false;
    }

    return true;
}

// ─── Device check (server-side UA sniff, client-side also handles this) ───────
function ppulse_popup_passes_device_check( $meta ) {
    $settings = ppulse_get_settings();
    $ua       = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';

    $is_mobile = wp_is_mobile();
    $is_tablet = ( strpos( $ua, 'ipad' ) !== false ) ||
                 ( strpos( $ua, 'tablet' ) !== false ) ||
                 ( $is_mobile && strpos( $ua, 'android' ) !== false && strpos( $ua, 'mobile' ) === false );

    if ( $is_mobile && ! $is_tablet ) {
        if ( $settings['disable_on_mobile'] ) return false;
        if ( ! $meta['show_on_mobile'] )      return false;
    } elseif ( $is_tablet ) {
        if ( $settings['disable_on_tablet'] ) return false;
        if ( ! $meta['show_on_tablet'] )      return false;
    } else {
        if ( ! $meta['show_on_desktop'] ) return false;
    }

    return true;
}

// ─── Page display check ───────────────────────────────────────────────────────
function ppulse_popup_shows_on_current_page( $meta ) {
    $pages = $meta['display_pages'];

    if ( $pages === 'all' || $pages === '' ) {
        return true;
    }

    if ( $pages === 'front' ) {
        return is_front_page() || is_home();
    }

    // Specific post/page IDs
    $ids         = array_map( 'absint', explode( ',', $pages ) );
    $current_id  = get_queried_object_id();

    return in_array( $current_id, $ids, true );
}

// ─── Render popup HTML in footer ──────────────────────────────────────────────
function ppulse_render_frontend_popups() {
    $popups = ppulse_get_active_popups();
    if ( empty( $popups ) ) {
        return;
    }

    foreach ( $popups as $popup ) {
        $meta = ppulse_get_popup_meta( $popup->ID );

        if ( ! ppulse_popup_is_in_schedule( $meta ) )        continue;
        if ( ! ppulse_popup_passes_device_check( $meta ) )   continue;
        if ( ! ppulse_popup_shows_on_current_page( $meta ) ) continue;

        ppulse_render_single_popup( $popup, $meta );
    }
}

// ─── Render a single popup ────────────────────────────────────────────────────
function ppulse_render_single_popup( $popup, $meta ) {
    $popup_id       = $popup->ID;
    $position       = $meta['position'];
    $is_bar         = in_array( $position, [ 'top-bar', 'bottom-bar' ], true );
    $animate_class  = $meta['animate_in'] ? 'ppulse-popup--anim ppulse-popup--anim-' . esc_attr( $meta['animation_style'] ) : '';
    $bg_style       = 'background-color:' . esc_attr( $meta['popup_bg_color'] ) . ';';

    $max_width = (int) $meta['popup_max_width'];
    if ( $max_width && $max_width < 9000 ) {
        $bg_style .= 'max-width:' . $max_width . 'px;';
    }

    // Full-width image cover
    if ( $meta['full_width_image'] && $meta['full_width_image_url'] ) {
        $bg_style .= 'background-image:url(' . esc_url( $meta['full_width_image_url'] ) . ');background-size:cover;background-position:center;';
    }

    $overlay_style = 'background:' . esc_attr( $meta['overlay_color'] ) . ';opacity:' . esc_attr( $meta['overlay_opacity'] ) . ';';

    $wrapper_class = 'ppulse-popup-wrapper ppulse-pos-' . esc_attr( $position );
    if ( $is_bar ) {
        $wrapper_class .= ' ppulse-popup-wrapper--bar';
    }
    ?>
    <div id="ppulse-<?php echo esc_attr( $popup_id ); ?>"
         class="<?php echo esc_attr( $wrapper_class ); ?>"
         role="dialog"
         aria-modal="true"
         aria-label="<?php echo esc_attr( $popup->post_title ); ?>"
         data-ppulse-id="<?php echo esc_attr( $popup_id ); ?>"
         style="display:none;"
         hidden>

        <?php if ( ! $is_bar ) : ?>
        <!-- Overlay -->
        <div class="ppulse-overlay" style="<?php echo esc_attr( $overlay_style ); ?>"></div>
        <?php endif; ?>

        <!-- Popup box -->
        <div class="ppulse-popup <?php echo esc_attr( $animate_class ); ?>"
             style="<?php echo esc_attr( $bg_style ); ?>"
             role="document">

            <?php if ( $meta['show_close_btn'] ) : ?>
            <!-- Close button -->
            <button type="button"
                    class="ppulse-close"
                    aria-label="<?php esc_attr_e( 'Close popup', 'ppulse' ); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
            <?php endif; ?>

            <?php if ( $meta['auto_close_enabled'] ) : ?>
            <!-- Auto-close progress bar -->
            <div class="ppulse-autoclose-bar">
                <div class="ppulse-autoclose-bar__fill"
                     style="animation-duration:<?php echo esc_attr( $meta['auto_close_seconds'] ); ?>s"></div>
            </div>
            <?php endif; ?>

            <!-- Content -->
            <div class="ppulse-popup__content entry-content">
                <?php echo apply_filters( 'the_content', $popup->post_content ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
            </div>
        </div>
    </div>
    <?php
}
