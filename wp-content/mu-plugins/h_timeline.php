<?php
/**
 * Plugin Name: Hauser & Wirth Timeline
 * Author: msplint
 * Version: 1.1.1
 *
 * @package wpengine/common-mu-plugin
 */
require_once(__DIR__ . '/advanced-custom-fields-pro/acf.php');
if ( ! function_exists('h_timeline') ) {

// Register Custom Post Type
function h_timeline() {

	$labels = array(
		'name'                  => _x( 'Entries', 'Post Type General Name', 'text_domain' ),
		'singular_name'         => _x( 'Entry', 'Post Type Singular Name', 'text_domain' ),
		'menu_name'             => __( 'Timeline', 'text_domain' ),
		'name_admin_bar'        => __( 'Timeline', 'text_domain' ),
		'archives'              => __( 'Timeline Archives', 'text_domain' ),
		'attributes'            => __( 'Entry Attributes', 'text_domain' ),
		'parent_item_colon'     => __( 'Parent Entry:', 'text_domain' ),
		'all_items'             => __( 'All Entries', 'text_domain' ),
		'add_new_item'          => __( 'Add New Entry', 'text_domain' ),
		'add_new'               => __( 'Add New', 'text_domain' ),
		'new_item'              => __( 'New Entry', 'text_domain' ),
		'edit_item'             => __( 'Edit Entry', 'text_domain' ),
		'update_item'           => __( 'Update Entry', 'text_domain' ),
		'view_item'             => __( 'View Entry', 'text_domain' ),
		'view_items'            => __( 'View Entries', 'text_domain' ),
		'search_items'          => __( 'Search Entry', 'text_domain' ),
		'not_found'             => __( 'Not found', 'text_domain' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'text_domain' ),
		'featured_image'        => __( 'Featured Image', 'text_domain' ),
		'set_featured_image'    => __( 'Set featured image', 'text_domain' ),
		'remove_featured_image' => __( 'Remove featured image', 'text_domain' ),
		'use_featured_image'    => __( 'Use as featured image', 'text_domain' ),
		'insert_into_item'      => __( 'Insert into item', 'text_domain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this item', 'text_domain' ),
		'items_list'            => __( 'Items list', 'text_domain' ),
		'items_list_navigation' => __( 'Items list navigation', 'text_domain' ),
		'filter_items_list'     => __( 'Filter items list', 'text_domain' ),
	);
	$args = array(
		'label'                 => __( 'Entry', 'text_domain' ),
		'description'           => __( 'Post Type Description', 'text_domain' ),
		'labels'                => $labels,
		'supports'              => array( 'title' ),
		'taxonomies'            => array( 'category', 'post_tag' ),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-calendar-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'capability_type'       => 'page',
		'show_in_rest'          => true,
		'rest_base'          		=> 'timeline',
	);
	register_post_type( 'h_timeline', $args );

}
add_action( 'init', 'h_timeline', 0 );

}
