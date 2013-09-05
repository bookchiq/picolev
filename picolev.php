<?php
/**
 * Plugin Name: Picolev
 * Description: Productivity through public accountability
 * Version:	 0.1.0
 * Author:	  Sarah Lewis
 * Author URI:  http://wpmoxie.com/
 * License:	 GPLv2+
 * Text Domain: picolev
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2013 Sarah Lewis (email : sarah@wpmoxie.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Useful global constants
define( 'PICOLEV_VERSION', '0.1.0' );
define( 'PICOLEV_URL', plugin_dir_url( __FILE__ ) );
define( 'PICOLEV_PATH', dirname( __FILE__ ) . '/' );
define( 'PICOLEV_SLUG', 'picolev_mission' );

require_once( 'includes/alerts.php' );
require_once( 'includes/debug.php' );
require_once( 'includes/badges.php' );
require_once( 'includes/missions.php' );

class Picolev
{
	function __construct() {
		register_activation_hook( __FILE__, array( 'Picolev', 'activate' ) );
		register_deactivation_hook( __FILE__, array( 'Picolev', 'deactivate' ) );

		add_action( 'init', array( 'Picolev', 'init' ) );
		add_action( 'wp_enqueue_scripts', array( 'Picolev', 'enqueue_scripts_and_styles' ) );
		add_action( 'bp_before_directory_activity_content', array( 'Picolev', 'do_picolev_mission_shortcode' ) );
		// add_action( 'bp_activity_entry_meta', array( 'Picolev', 'add_fb_like_button' ) );
		add_action( 'wp_head', array( 'Picolev', 'add_open_graph_tags' ) );

		add_filter( 'bp_activity_allowed_tags', array( 'Picolev', 'whitelist_tags_in_activity_action' ) );
		add_filter( 'map_meta_cap', array( 'Picolev', 'map_meta_cap' ), 10, 4 );

		add_shortcode( 'picolev-mission', array( 'Picolev', 'mission_management' ) );
		add_shortcode( 'picolev-leaderboards', array( 'Picolev', 'display_leaderboards' ) );
	}

	/**
	 * Default initialization for the plugin:
	 * - Registers the default textdomain.
	 * - Adds the custom post type.
	 */
	function init() {
		date_default_timezone_set( get_option('timezone_string') );

		$locale = apply_filters( 'plugin_locale', get_locale(), 'picolev' );
		load_textdomain( 'picolev', WP_LANG_DIR . '/picolev/picolev-' . $locale . '.mo' );
		load_plugin_textdomain( 'picolev', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		global $current_user;
		get_currentuserinfo();

		global $active_missions_query_args;
		$active_missions_query_args = array(
			'author' => $current_user->ID,
			'numberposts'		=>	1,
			'orderby'			=>	'post_date',
			'order'				=>	'ASC',
			'meta_key'			=>	'completed',
			'meta_value'		=>	'0',
			'post_type'			=>	PICOLEV_SLUG
		);

		register_post_type(
			PICOLEV_SLUG,
			array(	
				'label' => 'Mission',
				'description' => '',
				'public' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'capability_type' => 'picolev_mission',
				'capabilities' => array(
					'publish_posts' => 'publish_picolev_missions',
					'edit_posts' => 'edit_picolev_missions',
					'edit_others_posts' => 'edit_others_picolev_missions',
					'delete_posts' => 'delete_picolev_missions',
					'delete_others_posts' => 'delete_others_picolev_missions',
					'read_private_posts' => 'read_private_picolev_missions',
					'edit_post' => 'edit_picolev_mission',
					'delete_post' => 'delete_picolev_mission',
					'read_post' => 'read_picolev_mission',
				),
				'hierarchical' => true,
				'has_archive' => true,
				'query_var' => true,
				'exclude_from_search' => false,
				'rewrite' => array( 'slug' => 'mission' ),
				'supports' => array(
					'title',
					'comments',
					'author',
				),
				'labels' => array (
					'name' => 'Missions',
					'singular_name' => 'Mission',
					'menu_name' => 'Missions',
					'all_items' => 'All Missions',
					'add_new' => 'Add Mission',
					'add_new_item' => 'Add New Mission',
					'edit' => 'Edit',
					'edit_item' => 'Edit Mission',
					'new_item' => 'New Mission',
					'view' => 'View Mission',
					'view_item' => 'View Mission',
					'search_items' => 'Search Missions',
					'not_found' => 'No Missions Found',
					'not_found_in_trash' => 'No Missions Found in Trash',
					'parent' => 'Parent Mission',
				),
			)
		);

		$roles = array( 'subscriber', 'author', 'editor', 'administrator' );

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			$role->add_cap( 'publish_picolev_missions' );
			$role->add_cap( 'edit_picolev_missions' );

			if ( ( 'editor' == $role_name ) OR ( 'administrator' == $role_name ) ) {
				$role->add_cap( 'edit_others_picolev_missions' );
				
				$role->add_cap( 'delete_others_picolev_missions' );
			}

			if ( ( 'administrator' == $role_name ) ) {
				$role->add_cap( 'delete_picolev_missions' );
				$role->add_cap( 'read_private_picolev_missions' );
			}
		}
		
		// $role->add_cap( 'read_picolev_mission' );
	}

	/**
	 * Activate the plugin
	 */
	function activate() {
		// First load the init scripts in case any rewrite functionality is being loaded
		Picolev::init();

		flush_rewrite_rules();
	}
	
	/**
	 * Deactivate the plugin
	 * Uninstall routines should be in uninstall.php
	 */
	function deactivate() {

	}

	function enqueue_scripts_and_styles() {
		$latest_version = date( 'Ymdhis' );

		// Timeago
		wp_enqueue_script( 'jquery-timeago', plugins_url( 'assets/js/jquery.timeago-ck.js', __FILE__ ), array( 'jquery' ), '1.3.0', true );
		
		// Tablesorter
		wp_enqueue_script( 'jquery-tablesorter', plugins_url( 'assets/js/jquery.tablesorter.min-ck.js', __FILE__ ), array( 'jquery' ), '2.0.5b', true );

		// Icon font
		wp_enqueue_style( 'picolev-icon', plugins_url( 'assets/icons/css/picolev.css', __FILE__ ) );
		wp_enqueue_style( 'picolev-icon-animation', plugins_url( 'assets/icons/css/animation.css', __FILE__ ) );
		echo '<!--[if IE 7]><link rel="stylesheet" href="' . plugins_url( 'assets/icons/css/picolev-ie7.css', __FILE__ ) . '"><![endif]-->';

		// Initialization and custom functions
		wp_enqueue_script( 'picolev-scripts', plugins_url( 'assets/js/picolev-ck.js', __FILE__ ), array( 'jquery', 'jquery-timeago', 'jquery-tablesorter' ), $latest_version, true );
		wp_enqueue_style( 'picolev-plugin-styles', plugins_url( 'assets/css/picolev.css', __FILE__ ), false, $latest_version );
	}

	function add_open_graph_tags() {
		global $wp_query;
		$open_graph_tags = array();

		if ( bp_is_single_activity() ) {
			// Get the details for this specific activity
			$activity_id = trim( $wp_query->query['page'], '/' );

			$mission = Picolev::get_mission_for_activity( $activity_id );
			// $activities = get_posts( array(
			// 	'numberposts'		=>	1,
			// 	'page_id'			=> $activity_id,
			// 	'post_type'			=>	'bp-activity' )
			// );

			

			$open_graph_tags['og:url'] = '<meta property="og:url" content="' . home_url( $wp_query->query['pagename'] . $wp_query->query['page'] ) . '" />';
			
			if ( $mission ) {

				log_it( 'mission' , $mission );
				$open_graph_tags['og:title'] = '<meta property="og:title" content="' . get_the_author_meta( 'display_name' , $mission->post_author ) . ' is an agent of productivity on Picolev" />';
				$open_graph_tags['og:description'] = '<meta property="og:description" content="Mission: ' . $mission->post_title . '" />';
			}
		}
		
		echo implode( "\r\n", apply_filters( 'picolev_open_graph_tags', $open_graph_tags ) );
	}

	function add_fb_like_button() {
		echo '<div class="fb-like" data-href="' . bp_get_activity_thread_permalink() . '" data-width="300" data-show-faces="false" data-send="false"></div>';
	}

	function whitelist_tags_in_activity_action( $allowedtags ) {
	    $allowedtags['div']['class'] = array();
	    $allowedtags['abbr']['class'] = array();
	    return $allowedtags;
	}


	function map_meta_cap( $caps, $cap, $user_id, $args ) {
		/* If editing, deleting, or reading a picolev_mission, get the post and post type object. */
		if ( 'edit_picolev_mission' == $cap || 'delete_picolev_mission' == $cap || 'read_picolev_mission' == $cap ) {
			$post = get_post( $args[0] );
			$post_type = get_post_type_object( $post->post_type );

			/* Set an empty array for the caps. */
			$caps = array();
		}

		/* If editing a picolev_mission, assign the required capability. */
		if ( 'edit_picolev_mission' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->edit_posts;
			else
				$caps[] = $post_type->cap->edit_others_posts;
		}

		/* If deleting a picolev_mission, assign the required capability. */
		elseif ( 'delete_picolev_mission' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->delete_posts;
			else
				$caps[] = $post_type->cap->delete_others_posts;
		}

		/* If reading a private picolev_mission, assign the required capability. */
		elseif ( 'read_picolev_mission' == $cap ) {

			if ( 'private' != $post->post_status )
				$caps[] = 'read';
			elseif ( $user_id == $post->post_author )
				$caps[] = 'read';
			else
				$caps[] = $post_type->cap->read_private_posts;
		}

		/* Return the capabilities required by the user. */
		return $caps;
	}
}
$picolev = new Picolev();