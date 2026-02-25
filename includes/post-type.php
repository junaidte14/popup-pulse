<?php
/**
 * Custom Post Type & Taxonomy Registration
 *
 * @package PopupPulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ppulse_register_post_type' );
add_action( 'init', 'ppulse_register_taxonomy' );
add_filter( 'post_updated_messages', 'ppulse_post_updated_messages' );
add_filter( 'use_block_editor_for_post_type', 'ppulse_force_gutenberg', 10, 2 );
add_action( 'add_meta_boxes',   'ppulse_remove_unwanted_meta_boxes', 99 );
add_filter( 'manage_' . PPULSE_POST_TYPE . '_posts_columns',       'ppulse_list_columns' );
add_action( 'manage_' . PPULSE_POST_TYPE . '_posts_custom_column', 'ppulse_list_column_content', 10, 2 );
add_filter( 'manage_edit-' . PPULSE_POST_TYPE . '_sortable_columns', 'ppulse_sortable_columns' );

// ─── Register CPT ─────────────────────────────────────────────────────────────
function ppulse_register_post_type() {
    $labels = [
        'name'                  => _x( 'Popups', 'post type general name', 'ppulse' ),
        'singular_name'         => _x( 'Popup', 'post type singular name', 'ppulse' ),
        'menu_name'             => _x( 'Popup Pulse', 'admin menu', 'ppulse' ),
        'name_admin_bar'        => _x( 'Popup', 'add new on admin bar', 'ppulse' ),
        'add_new'               => _x( 'Add New', 'popup', 'ppulse' ),
        'add_new_item'          => __( 'Add New Popup', 'ppulse' ),
        'new_item'              => __( 'New Popup', 'ppulse' ),
        'edit_item'             => __( 'Edit Popup', 'ppulse' ),
        'view_item'             => __( 'View Popup', 'ppulse' ),
        'all_items'             => __( 'All Popups', 'ppulse' ),
        'search_items'          => __( 'Search Popups', 'ppulse' ),
        'not_found'             => __( 'No popups found.', 'ppulse' ),
        'not_found_in_trash'    => __( 'No popups found in Trash.', 'ppulse' ),
        'filter_items_list'     => __( 'Filter popups list', 'ppulse' ),
        'items_list_navigation' => __( 'Popups list navigation', 'ppulse' ),
        'items_list'            => __( 'Popups list', 'ppulse' ),
    ];

    $args = [
        'labels'             => $labels,
        'description'        => __( 'Popup Pulse managed popups.', 'ppulse' ),
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'menu_position'      => 25,
        'menu_icon'          => 'data:image/svg+xml;base64,' . base64_encode( ppulse_menu_icon_svg() ),
        'show_in_admin_bar'  => true,
        'show_in_rest'       => true,   // Required for Gutenberg
        'rest_base'          => 'ppulse-popups',
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => [ 'title', 'editor', 'revisions', 'custom-fields' ],
        'taxonomies'         => [ PPULSE_TAX ],
        'rewrite'            => false,
    ];

    register_post_type( PPULSE_POST_TYPE, $args );
}

// ─── Register Taxonomy ────────────────────────────────────────────────────────
function ppulse_register_taxonomy() {
    $labels = [
        'name'          => _x( 'Popup Categories', 'taxonomy general name', 'ppulse' ),
        'singular_name' => _x( 'Popup Category', 'taxonomy singular name', 'ppulse' ),
        'menu_name'     => __( 'Categories', 'ppulse' ),
        'all_items'     => __( 'All Categories', 'ppulse' ),
        'edit_item'     => __( 'Edit Category', 'ppulse' ),
        'update_item'   => __( 'Update Category', 'ppulse' ),
        'add_new_item'  => __( 'Add New Category', 'ppulse' ),
        'new_item_name' => __( 'New Category Name', 'ppulse' ),
        'search_items'  => __( 'Search Categories', 'ppulse' ),
        'not_found'     => __( 'No categories found.', 'ppulse' ),
    ];

    register_taxonomy( PPULSE_TAX, PPULSE_POST_TYPE, [
        'labels'            => $labels,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => false,
    ] );
}

// ─── Force Gutenberg for this CPT ─────────────────────────────────────────────
function ppulse_force_gutenberg( $use_block_editor, $post_type ) {
    if ( $post_type === PPULSE_POST_TYPE ) {
        return true;
    }
    return $use_block_editor;
}

// ─── Remove default meta boxes we don't need ──────────────────────────────────
function ppulse_remove_unwanted_meta_boxes() {
    remove_meta_box( 'slugdiv', PPULSE_POST_TYPE, 'normal' );
    remove_meta_box( 'commentstatusdiv', PPULSE_POST_TYPE, 'normal' );
    remove_meta_box( 'trackbacksdiv', PPULSE_POST_TYPE, 'normal' );
    remove_meta_box( 'commentsdiv', PPULSE_POST_TYPE, 'normal' );
    remove_meta_box( 'authordiv', PPULSE_POST_TYPE, 'normal' );
}

// ─── Custom list columns ──────────────────────────────────────────────────────
function ppulse_list_columns( $columns ) {
    $new = [];
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( $key === 'title' ) {
            $new['ppulse_status']  = __( 'Status', 'ppulse' );
            $new['ppulse_trigger'] = __( 'Trigger', 'ppulse' );
            $new['ppulse_freq']    = __( 'Frequency', 'ppulse' );
            $new['ppulse_tmpl']    = __( 'Template', 'ppulse' );
        }
    }
    unset( $new['date'] );
    $new['date'] = __( 'Date', 'ppulse' );
    return $new;
}

function ppulse_list_column_content( $column, $post_id ) {
    $meta = ppulse_get_popup_meta( $post_id );

    switch ( $column ) {
        case 'ppulse_status':
            if ( $meta['enabled'] ) {
                echo '<span class="ppulse-badge ppulse-badge--on">' . esc_html__( 'Active', 'ppulse' ) . '</span>';
            } else {
                echo '<span class="ppulse-badge ppulse-badge--off">' . esc_html__( 'Disabled', 'ppulse' ) . '</span>';
            }
            break;

        case 'ppulse_trigger':
            $triggers = ppulse_trigger_labels();
            $trigger  = $meta['trigger_type'];
            echo esc_html( $triggers[ $trigger ] ?? ucfirst( $trigger ) );
            if ( $trigger === 'delay' && $meta['trigger_delay'] > 0 ) {
                echo ' <small>(' . esc_html( $meta['trigger_delay'] ) . 's)</small>';
            }
            if ( $meta['exit_intent'] ) {
                echo ' <span class="ppulse-badge ppulse-badge--info">' . esc_html__( '+Exit Intent', 'ppulse' ) . '</span>';
            }
            break;

        case 'ppulse_freq':
            $freq  = $meta['frequency_type'];
            $freqs = ppulse_frequency_labels();
            echo esc_html( $freqs[ $freq ] ?? ucfirst( $freq ) );
            if ( $freq === 'limited' && $meta['frequency_limit'] > 0 ) {
                /* translators: %d: number of times */
                echo ' <small>(' . esc_html( sprintf( _n( '%d time', '%d times', (int) $meta['frequency_limit'], 'ppulse' ), $meta['frequency_limit'] ) ) . ')</small>';
            }
            break;

        case 'ppulse_tmpl':
            if ( $meta['is_template'] ) {
                echo '<span class="ppulse-badge ppulse-badge--tmpl">&#9733; ' . esc_html__( 'Template', 'ppulse' ) . '</span>';
            } else {
                echo '&mdash;';
            }
            break;
    }
}

function ppulse_sortable_columns( $columns ) {
    $columns['ppulse_status'] = 'ppulse_status';
    return $columns;
}

// ─── Updated messages ─────────────────────────────────────────────────────────
function ppulse_post_updated_messages( $messages ) {
    $messages[ PPULSE_POST_TYPE ] = [
        0  => '',
        1  => __( 'Popup updated.', 'ppulse' ),
        2  => __( 'Custom field updated.', 'ppulse' ),
        3  => __( 'Custom field deleted.', 'ppulse' ),
        4  => __( 'Popup updated.', 'ppulse' ),
        6  => __( 'Popup published.', 'ppulse' ),
        7  => __( 'Popup saved.', 'ppulse' ),
        8  => __( 'Popup submitted.', 'ppulse' ),
        9  => __( 'Popup scheduled.', 'ppulse' ),
        10 => __( 'Popup draft updated.', 'ppulse' ),
    ];
    return $messages;
}

// ─── Menu icon SVG ────────────────────────────────────────────────────────────
function ppulse_menu_icon_svg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="#a7aaad" d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 2v12h16V6H4zm8 2a4 4 0 1 1 0 8A4 4 0 0 1 12 8zm0 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm4.5 5.5a1 1 0 0 1 1 1v1a1 1 0 0 1-2 0v-1a1 1 0 0 1 1-1z"/></svg>';
}

// ─── Label helpers ────────────────────────────────────────────────────────────
function ppulse_trigger_labels() {
    return [
        'delay'    => __( 'Time Delay', 'ppulse' ),
        'scroll'   => __( 'Scroll %', 'ppulse' ),
        'click'    => __( 'Click Element', 'ppulse' ),
        'exit'     => __( 'Exit Intent Only', 'ppulse' ),
        'pageload' => __( 'Immediate', 'ppulse' ),
    ];
}

function ppulse_frequency_labels() {
    return [
        'always'      => __( 'Always', 'ppulse' ),
        'once'        => __( 'Once (cookie)', 'ppulse' ),
        'limited'     => __( 'Limited times', 'ppulse' ),
        'per_session' => __( 'Once per session', 'ppulse' ),
        'per_page'    => __( 'Once per page load', 'ppulse' ),
    ];
}
