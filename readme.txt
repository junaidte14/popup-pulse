=== Popup Pulse ===
Contributors:      popuppulse
Tags:              popup, modal, overlay, gutenberg, marketing
Requires at least: 6.2
Tested up to:      6.7
Stable tag:        1.0.0
Requires PHP:      7.4
License:           GPL v2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Design beautiful popups with the Gutenberg block editor. Powerful triggers, smart frequency controls, pre-built templates, and full-width image covers.

== Description ==

**Popup Pulse** is a production-ready, lightweight popup manager built entirely on the WordPress block editor (Gutenberg). Design any type of popup you can imagine using the familiar Gutenberg interface — no page builder required.

= Key Features =

**Design**
* Full Gutenberg block editor for popup content — use any block
* Full-width image cover backgrounds
* 7 position presets: center modal, corner notifications, top/bottom bars
* Custom background color, overlay color & opacity
* 5 entrance animations: Fade, Slide Up, Slide Down, Zoom, Bounce

**Triggers**
* Immediate (page load)
* Time delay (configurable seconds)
* Scroll percentage
* Click on any CSS selector
* Exit intent (mouse-leave detection)
* Date-range scheduling

**Frequency / Repetition**
* Always show
* Once (cookie-based)
* Limited number of times
* Once per session
* Once per page load
* Configurable cookie duration

**Behavior**
* Show / hide close button
* Close on overlay click
* Auto-close after configurable delay (with progress bar)
* Escape key to close
* Accessible focus trap for modals

**Targeting**
* Device targeting: desktop, tablet, mobile (server + client-side)
* Global device disable rules in settings
* Page/post targeting: all, front page, or specific IDs

**Templates**
* 8 pre-built layout templates (Newsletter, Announcement, Coupon, Exit Offer, Image Cover, Cookie Notice, Contact CTA, Video Popup)
* Save any popup as a custom template
* Apply any template to existing popups

**Code Quality**
* Non-OOP procedural WordPress style
* Zero external dependencies (no jQuery plugins, no CSS frameworks)
* Clean transient caching for performance
* Full nonce verification on all AJAX actions
* Sanitized inputs, escaped outputs throughout
* CSS custom properties, `prefers-reduced-motion` support
* WCAG-friendly focus management and ARIA attributes

== Installation ==

1. Upload the `popup-pulse` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu
3. Go to **Popup Pulse → Add New** to create your first popup
4. Design your content in the Gutenberg editor
5. Configure triggers, frequency, and display options in the **Popup Settings** panel
6. Publish the popup — it will appear on your site immediately

== Frequently Asked Questions ==

= Does Popup Pulse slow down my site? =
The frontend scripts and styles are only loaded when at least one active popup exists. The popup list is cached using WordPress transients.

= Can I use Contact Form 7 or Gravity Forms inside a popup? =
Yes. Any block or shortcode that works in the WordPress editor will work inside a Popup Pulse popup.

= How does exit intent work on mobile? =
Exit intent on desktop is detected via `mouseleave` events. On mobile, the plugin listens to the `pagehide` event as a fallback.

= Can I open a popup programmatically? =
Yes. Use the global JS API: `window.PopupPulse.open(POPUP_ID)` and `window.PopupPulse.close(POPUP_ID)`.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
