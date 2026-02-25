<?php
/**
 * Pre-built Layout Templates
 * Block content + default meta for each template.
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

// ─── Template Registry ────────────────────────────────────────────────────────
function ppulse_builtin_templates() {
    return [
        'newsletter'    => ppulse_tpl_newsletter(),
        'announcement'  => ppulse_tpl_announcement(),
        'coupon'        => ppulse_tpl_coupon(),
        'exit_offer'    => ppulse_tpl_exit_offer(),
        'image_cover'   => ppulse_tpl_image_cover(),
        'cookie_notice' => ppulse_tpl_cookie_notice(),
        'contact_cta'   => ppulse_tpl_contact_cta(),
        'video_popup'   => ppulse_tpl_video_popup(),
    ];
}

// ─── Template: Newsletter Signup ──────────────────────────────────────────────
function ppulse_tpl_newsletter() {
    return [
        'label'       => __( 'Newsletter Signup', 'ppulse' ),
        'description' => __( 'Clean email opt-in with headline and sub-text.', 'ppulse' ),
        'icon'        => 'dashicons-email-alt',
        'preview_bg'  => '#f0f7ff',
        'meta'        => [
            'trigger_type'       => 'delay',
            'trigger_delay'      => 5,
            'frequency_type'     => 'once',
            'frequency_days'     => 30,
            'popup_max_width'    => 520,
            'popup_bg_color'     => '#ffffff',
            'animate_in'         => '1',
            'animation_style'    => 'slide-up',
            'show_close_btn'     => '1',
            'overlay_click_close'=> '0',
        ],
        'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"40px","right":"40px"}}},"backgroundColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-background-color has-background" style="padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px">
<!-- wp:image {"align":"center","width":64,"height":64,"sizeSlug":"full","style":{"color":{}}} -->
<figure class="wp-block-image aligncenter size-full is-resized"><img src="' . esc_url( PPULSE_URL . 'admin/assets/img/email-icon.svg' ) . '" alt="" width="64" height="64"/></figure>
<!-- /wp:image -->
<!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"28px","fontWeight":"700"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:28px;font-weight:700">' . esc_html__( 'Stay in the Loop 🎉', 'ppulse' ) . '</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#555555"}}} -->
<p class="has-text-align-center" style="color:#555555">' . esc_html__( 'Subscribe to get our latest posts, special offers, and exclusive content delivered straight to your inbox.', 'ppulse' ) . '</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"vivid-cyan-blue","style":{"border":{"radius":"6px"},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button" style="border-radius:6px;padding-top:14px;padding-right:32px;padding-bottom:14px;padding-left:32px">' . esc_html__( 'Subscribe Now', 'ppulse' ) . '</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"12px"},"color":{"text":"#999999"}}} -->
<p class="has-text-align-center" style="color:#999999;font-size:12px">' . esc_html__( 'No spam. Unsubscribe anytime.', 'ppulse' ) . '</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
    ];
}

// ─── Template: Announcement ───────────────────────────────────────────────────
function ppulse_tpl_announcement() {
    return [
        'label'       => __( 'Announcement Banner', 'ppulse' ),
        'description' => __( 'Bold top or bottom bar announcement.', 'ppulse' ),
        'icon'        => 'dashicons-megaphone',
        'preview_bg'  => '#fff9e6',
        'meta'        => [
            'trigger_type'       => 'pageload',
            'frequency_type'     => 'per_session',
            'popup_max_width'    => 9999,
            'position'           => 'top-bar',
            'popup_bg_color'     => '#1a1a2e',
            'overlay_color'      => '#000000',
            'overlay_opacity'    => '0',
            'animate_in'         => '1',
            'animation_style'    => 'slide-down',
            'show_close_btn'     => '1',
            'overlay_click_close'=> '0',
        ],
        'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"14px","bottom":"14px","left":"24px","right":"24px"}}},"backgroundColor":"contrast","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
<div class="wp-block-group has-contrast-background-color has-background" style="padding-top:14px;padding-right:24px;padding-bottom:14px;padding-left:24px">
<!-- wp:paragraph {"textColor":"base","style":{"typography":{"fontWeight":"600","fontSize":"16px"}}} -->
<p class="has-base-color has-text-color" style="font-weight:600;font-size:16px">🎉 ' . esc_html__( 'Big news! We just launched something amazing.', 'ppulse' ) . ' <a href="#">' . esc_html__( 'Learn more →', 'ppulse' ) . '</a></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->',
    ];
}

// ─── Template: Coupon / Offer ─────────────────────────────────────────────────
function ppulse_tpl_coupon() {
    return [
        'label'       => __( 'Coupon / Special Offer', 'ppulse' ),
        'description' => __( 'Eye-catching discount popup with coupon code.', 'ppulse' ),
        'icon'        => 'dashicons-tag',
        'preview_bg'  => '#fff0f0',
        'meta'        => [
            'trigger_type'       => 'delay',
            'trigger_delay'      => 8,
            'frequency_type'     => 'once',
            'frequency_days'     => 7,
            'popup_max_width'    => 580,
            'popup_bg_color'     => '#ff4757',
            'animate_in'         => '1',
            'animation_style'    => 'zoom',
            'show_close_btn'     => '1',
            'overlay_click_close'=> '1',
        ],
        'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"40px","right":"40px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:48px;padding-right:40px;padding-bottom:48px;padding-left:40px">
<!-- wp:heading {"textAlign":"center","level":2,"textColor":"white","style":{"typography":{"fontSize":"44px","fontWeight":"800","letterSpacing":"-1px"}}} -->
<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="font-size:44px;font-weight:800;letter-spacing:-1px">20% OFF</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"fontSize":"18px"}}} -->
<p class="has-text-align-center has-white-color has-text-color" style="font-size:18px">' . esc_html__( 'Your entire first order. Use code:', 'ppulse' ) . '</p>
<!-- /wp:paragraph -->
<!-- wp:group {"style":{"spacing":{"padding":{"top":"12px","bottom":"12px","left":"24px","right":"24px"}},"border":{"width":"2px","style":"dashed"},"color":{"background":"rgba(255,255,255,0.2)"}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-group" style="border-style:dashed;border-width:2px;padding-top:12px;padding-right:24px;padding-bottom:12px;padding-left:24px;background-color:rgba(255,255,255,0.2)">
<!-- wp:paragraph {"textColor":"white","style":{"typography":{"fontSize":"28px","fontWeight":"800","letterSpacing":"4px"}}} -->
<p class="has-white-color has-text-color" style="font-size:28px;font-weight:800;letter-spacing:4px">WELCOME20</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"24px"}}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons" style="margin-top:24px">
<!-- wp:button {"style":{"color":{"background":"#ffffff","text":"#ff4757"},"border":{"radius":"50px"},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"40px","right":"40px"}},"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="border-radius:50px;background-color:#ffffff;color:#ff4757;padding-top:14px;padding-right:40px;padding-bottom:14px;padding-left:40px;font-weight:700">' . esc_html__( 'Shop Now', 'ppulse' ) . '</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
    ];
}

// ─── Template: Exit Intent Offer ──────────────────────────────────────────────
function ppulse_tpl_exit_offer() {
    return [
        'label'       => __( 'Exit Intent Offer', 'ppulse' ),
        'description' => __( 'Catch visitors before they leave with a last-minute offer.', 'ppulse' ),
        'icon'        => 'dashicons-arrow-left-alt',
        'preview_bg'  => '#f5f0ff',
        'meta'        => [
            'trigger_type'       => 'exit',
            'exit_intent'        => '1',
            'frequency_type'     => 'once',
            'frequency_days'     => 14,
            'popup_max_width'    => 600,
            'popup_bg_color'     => '#ffffff',
            'animate_in'         => '1',
            'animation_style'    => 'slide-up',
            'show_close_btn'     => '1',
            'overlay_click_close'=> '1',
        ],
        'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"48px","bottom":"48px","left":"48px","right":"48px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:48px;padding-right:48px;padding-bottom:48px;padding-left:48px">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"52px"}}} -->
<p class="has-text-align-center" style="font-size:52px">👋</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"textAlign":"center","level":2,"style":{"typography":{"fontSize":"26px","fontWeight":"700"}}} -->
<h2 class="wp-block-heading has-text-align-center" style="font-size:26px;font-weight:700">' . esc_html__( 'Wait — before you go!', 'ppulse' ) . '</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#666666"},"typography":{"fontSize":"16px"}}} -->
<p class="has-text-align-center" style="color:#666666;font-size:16px">' . esc_html__( 'We\'d hate to see you leave empty-handed. Let us sweeten the deal with an exclusive offer just for you.', 'ppulse' ) . '</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"12px"}}} -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"vivid-purple","style":{"border":{"radius":"6px"},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-vivid-purple-background-color has-background wp-element-button" style="border-radius:6px;padding-top:14px;padding-right:32px;padding-bottom:14px;padding-left:32px">' . esc_html__( 'Claim My Offer', 'ppulse' ) . '</a></div>
<!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"6px"},"spacing":{"padding":{"top":"14px","bottom":"14px","left":"32px","right":"32px"}}}} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" style="border-radius:6px;padding-top:14px;padding-right:32px;padding-bottom:14px;padding-left:32px">' . esc_html__( 'No thanks', 'ppulse' ) . '</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
    ];
}

// ─── Template: Full Width Image Cover ────────────────────────────────────────
function ppulse_tpl_image_cover() {
    return [
        'label'       => __( 'Full-Width Image Cover', 'ppulse' ),
        'description' => __( 'Immersive full-screen image backdrop with overlay text.', 'ppulse' ),
        'icon'        => 'dashicons-format-image',
        'preview_bg'  => '#1a1a2e',
        'meta'        => [
            'trigger_type'       => 'delay',
            'trigger_delay'      => 2,
            'frequency_type'     => 'once',
            'frequency_days'     => 30,
            'popup_max_width'    => 9999,
            'popup_bg_color'     => '#000000',
            'full_width_image'   => '1',
            'animate_in'         => '1',
            'animation_style'    => 'fade',
            'show_close_btn'     => '1',
            'overlay_click_close'=> '1',
            'overlay_opacity'    => '0',
        ],
        'content'     => '<!-- wp:cover {"dimRatio":50,"minHeight":100,"minHeightUnit":"vh","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover" style="min-height:100vh">
<span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span>
<div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"textColor":"white","style":{"typography":{"fontSize":"clamp(32px, 5vw, 64px)","fontWeight":"800"}}} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="font-size:clamp(32px,5vw,64px);font-weight:800">' . esc_html__( 'Your Stunning Headline', 'ppulse' ) . '</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","textColor":"white","style":{"typography":{"fontSize":"20px"}}} -->
<p class="has-text-align-center has-white-color has-text-color" style="font-size:20px">' . esc_html__( 'A compelling sub-headline that drives action.', 'ppulse' ) . '</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"32px"}}}} -->
<div class="wp-block-buttons" style="margin-top:32px">
<!-- wp:button {"style":{"border":{"radius":"50px"},"spacing":{"padding":{"top":"16px","bottom":"16px","left":"48px","right":"48px"}},"color":{"background":"#ffffff","text":"#000000"},"typography":{"fontWeight":"700","fontSize":"18px"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="border-radius:50px;background-color:#ffffff;color:#000000;padding-top:16px;padding-right:48px;padding-bottom:16px;padding-left:48px;font-weight:700;font-size:18px">' . esc_html__( 'Explore Now', 'ppulse' ) . '</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
</div>
<!-- /wp:cover -->',
    ];
}

// ─── Template: Cookie Notice ──────────────────────────────────────────────────
function ppulse_tpl_cookie_notice() {
    return [
        'label'       => __( 'Cookie / GDPR Notice', 'ppulse' ),
        'description' => __( 'Minimal bottom-bar cookie consent banner.', 'ppulse' ),
        'icon'        => 'dashicons-privacy',
        'preview_bg'  => '#f8f8f8',
        'meta'        => [
            'trigger_type'       => 'pageload',
            'frequency_type'     => 'once',
            'frequency_days'     => 365,
            'popup_max_width'    => 9999,
            'position'           => 'bottom-bar',
            'popup_bg_color'     => '#1e1e1e',
            'overlay_opacity'    => '0',
            'animate_in'         => '1',
            'animation_style'    => 'slide-up',
            'show_close_btn'     => '0',
            'overlay_click_close'=> '0',
        ],
        'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"16px","bottom":"16px","left":"24px","right":"24px"}}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="padding-top:16px;padding-right:24px;padding-bottom:16px;padding-left:24px">
<!-- wp:paragraph {"textColor":"white","style":{"typography":{"fontSize":"14px"}}} -->
<p class="has-white-color has-text-color" style="font-size:14px">🍪 ' . esc_html__( 'We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.', 'ppulse' ) . ' <a href="#" style="color:#7dd3fc">' . esc_html__( 'Learn more', 'ppulse' ) . '</a></p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"style":{"border":{"radius":"4px"},"spacing":{"padding":{"top":"8px","bottom":"8px","left":"20px","right":"20px"}},"color":{"background":"#3b82f6"},"typography":{"fontSize":"13px","fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button ppulse-close-trigger" href="#" style="border-radius:4px;background-color:#3b82f6;padding-top:8px;padding-right:20px;padding-bottom:8px;padding-left:20px;font-size:13px;font-weight:600">' . esc_html__( 'Accept', 'ppulse' ) . '</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->',
    ];
}

// ─── Template: Contact CTA ────────────────────────────────────────────────────
function ppulse_tpl_contact_cta() {
    return [
        'label'       => __( 'Contact / Lead CTA', 'ppulse' ),
        'description' => __( 'Two-column layout with icon and call-to-action.', 'ppulse' ),
        'icon'        => 'dashicons-phone',
        'preview_bg'  => '#f0fdf4',
        'meta'        => [
            'trigger_type'       => 'scroll',
            'trigger_scroll_pct' => 60,
            'frequency_type'     => 'once',
            'frequency_days'     => 7,
            'popup_max_width'    => 640,
            'popup_bg_color'     => '#ffffff',
            'animate_in'         => '1',
            'animation_style'    => 'slide-up',
            'show_close_btn'     => '1',
            'overlay_click_close'=> '1',
        ],
        'content'     => '<!-- wp:columns {"style":{"spacing":{"padding":{"top":"40px","bottom":"40px","left":"40px","right":"40px"},"blockGap":{"left":"32px"}}}} -->
<div class="wp-block-columns" style="padding-top:40px;padding-right:40px;padding-bottom:40px;padding-left:40px">
<!-- wp:column {"width":"33.33%","style":{"color":{"background":"#dcfce7"},"border":{"radius":"12px"},"spacing":{"padding":{"top":"24px","bottom":"24px","left":"24px","right":"24px"}}}} -->
<div class="wp-block-column" style="border-radius:12px;background-color:#dcfce7;padding-top:24px;padding-right:24px;padding-bottom:24px;padding-left:24px;flex-basis:33.33%">
<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"}}} -->
<p class="has-text-align-center" style="font-size:48px">💬</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"22px","fontWeight":"700"}}} -->
<h3 class="wp-block-heading" style="font-size:22px;font-weight:700">' . esc_html__( 'Let\'s Talk!', 'ppulse' ) . '</h3>
<!-- /wp:heading -->
<!-- wp:paragraph {"style":{"color":{"text":"#555555"},"typography":{"fontSize":"15px"}}} -->
<p style="color:#555555;font-size:15px">' . esc_html__( 'Have questions? Our team is ready to help you find the perfect solution.', 'ppulse' ) . '</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"vivid-green-cyan","style":{"border":{"radius":"6px"},"spacing":{"padding":{"top":"10px","bottom":"10px","left":"24px","right":"24px"}},"typography":{"fontWeight":"600"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-vivid-green-cyan-background-color has-background wp-element-button" style="border-radius:6px;padding-top:10px;padding-right:24px;padding-bottom:10px;padding-left:24px;font-weight:600">' . esc_html__( 'Contact Us', 'ppulse' ) . '</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->',
    ];
}

// ─── Template: Video Popup ────────────────────────────────────────────────────
function ppulse_tpl_video_popup() {
    return [
        'label'       => __( 'Video Popup', 'ppulse' ),
        'description' => __( 'Centered embed-ready video lightbox popup.', 'ppulse' ),
        'icon'        => 'dashicons-video-alt3',
        'preview_bg'  => '#0f0f0f',
        'meta'        => [
            'trigger_type'       => 'click',
            'trigger_click_selector' => '.ppulse-video-trigger',
            'frequency_type'     => 'per_page',
            'popup_max_width'    => 800,
            'popup_bg_color'     => '#000000',
            'overlay_color'      => '#000000',
            'overlay_opacity'    => '0.9',
            'animate_in'         => '1',
            'animation_style'    => 'zoom',
            'show_close_btn'     => '1',
            'overlay_click_close'=> '1',
        ],
        'content'     => '<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
<!-- wp:embed {"url":"https://www.youtube.com/watch?v=dQw4w9WgXcQ","type":"video","providerNameSlug":"youtube","responsive":true,"className":"wp-embed-aspect-16-9 wp-has-aspect-ratio"} -->
<figure class="wp-block-embed is-type-video is-provider-youtube wp-block-embed-youtube wp-embed-aspect-16-9 wp-has-aspect-ratio">
<div class="wp-block-embed__wrapper">
https://www.youtube.com/watch?v=dQw4w9WgXcQ
</div>
</figure>
<!-- /wp:embed -->
</div>
<!-- /wp:group -->',
    ];
}
