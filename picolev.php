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
require_once( 'includes/debug.php' );

class Picolev
{
	function __construct() {
		register_activation_hook( __FILE__, array( 'Picolev', 'activate' ) );
		register_deactivation_hook( __FILE__, array( 'Picolev', 'deactivate' ) );

		add_action( 'init', array( 'Picolev', 'init' ) );
		add_action( 'wp_enqueue_scripts', array( 'Picolev', 'enqueue_scripts_and_styles' ) );
		add_action( 'bp_before_directory_activity_content', array( 'Picolev', 'do_picolev_mission_shortcode' ) );

		add_filter( 'bp_activity_allowed_tags', array( 'Picolev', 'whitelist_tags_in_activity_action' ) );
		add_filter( 'map_meta_cap', array( 'Picolev', 'map_meta_cap' ), 10, 4 );

		add_shortcode( 'picolev-mission', array( 'Picolev', 'mission_management' ) );
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

		wp_enqueue_script( 'jquery-timeago', plugins_url( 'assets/js/jquery.timeago-ck.js', __FILE__ ), array( 'jquery' ), '1.3.0', true );
		wp_enqueue_script( 'picolev-scripts', plugins_url( 'assets/js/picolev-ck.js', __FILE__ ), array( 'jquery', 'jquery-timeago' ), $latest_version, true );
	}

	/**
	 * Called by shortcode [picolev-mission]
	 * Output the current mission or mission form, as applicable
	 * 
	 * @return string The content that will replace the shortcode
	 */
	function mission_management() {		
		global $current_user;

		// Limit to logged-in users
		if ( 0 != $current_user->ID ) {
			if ( Picolev::has_active_mission() ) {
				if( 'POST' == $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['action'] ) && 'mission_accomplished' == $_POST['action'] && ! empty( $_POST['mission_id'] ) ) {
					Picolev::complete_mission();
					return Picolev::output_mission_form();
				} else {
					return Picolev::show_current_mission();
				}
			} else {
				if( 'POST' == $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['action'] ) && 'new_mission' == $_POST['action'] ) {
					// The form has been submitted and needs to be processed
					return Picolev::add_mission();
				} else {
					return Picolev::output_mission_form();
				}
			}
		}
	}

	function output_mission_form() {
		$out = '<div class="picolev-mission new" id="postbox">' . "\r\n";
		$out .= '<form id="new_mission" name="new_mission" method="post" action="">' . "\r\n";

		$out .= '<p><label for="title">Mission:</label>' . "\r\n";
		$out .= '<input type="text" id="title" value="" tabindex="1000" size="20" name="title" />' . "\r\n";
		$out .= '</p>' . "\r\n";

		$out .= '<p><label for="predicted_time">How many minutes it will take you:</label>' . "\r\n";
		$out .= '<input type="text" id="predicted_time" value="" tabindex="1001" size="20" name="predicted_time" />' . "\r\n";
		$out .= '</p>' . "\r\n";

		$out .= '<input type="submit" value="Go!" tabindex="1002">' . "\r\n";
		$out .= '<input type="hidden" name="action" value="new_mission">' . "\r\n";
		$out .= '</form></div>';
		return $out;
	}

	function add_mission() {
		$go_ahead = true;

		global $current_user;

		// Limit to logged-in users
		if ( 0 == $current_user->ID ) {
			$go_ahead = false;
		}
		
		$out = '';
		// Do some minor form validation to make sure there is content
		if (isset ($_POST['title'])) {
			$title = esc_attr( $_POST['title'] );
		} else {
			$go_ahead = false;
			$out .= 'Please enter a mission';
		}

		if ( isset($_POST['predicted_time'] ) && ( $predicted_time = intval( $_POST['predicted_time'], 10 ) ) ) {
			
		} else {
			$go_ahead = false;
			$out .= 'Please enter your time estimate';
		}

		if ( $go_ahead ) {
			// Add the content of the form to $post as an array
			$new_post = array(
				'post_title'	=> $title,
				'post_status'   => 'publish',
				'post_type' => PICOLEV_SLUG
			);

			// Save the new mission
			$post_id = wp_insert_post( $new_post );

			// Add the relevant postmeta
			// Add completion time prediction
			$predicted_finish_time = time() + ( $predicted_time * 60  );
			update_post_meta( $post_id, 'predicted_time', $predicted_finish_time );
			// Add default completion value: not completed! There will be time enough for that later. ;)
			update_post_meta( $post_id, 'completed', '0' );	

			$activity_id = bp_activity_add( array(
				'action' => $current_user->data->display_name . __( ' accepted a mission', 'picolev' ),
				'content' => '<div class="mission-name">' . __( 'The mission: ', 'picolev' ) . $title . '</div><div class="mission-predicted-finish-time">The deadline: <abbr class="timeago" title="' . date( 'c', $predicted_finish_time ) . '">' . date( 'g:i A T', $predicted_finish_time ) . '</abbr></div>',
				/* the component argument will be set to our component's identifier */
				'component' => 'picolev_mission',
				/* the type argument will be set to our component's type */
				'type' => 'picolev_mission_update',
			) );

			header( 'location: ' . esc_url( $_SERVER['REQUEST_URI'] ) );

			return Picolev::show_current_mission();
		} else {
			return $out . Picolev::output_mission_form();
		}
	}

	function complete_mission() {
		global $current_user;
		if ( $post_id = intval( $_POST['mission_id'], 10 )  && 0 != $current_user->ID ) {

			update_post_meta( $post_id, 'completed', time() );
			$predicted_finish_time = get_post_meta( $post_id, 'predicted_time', true );
			$time_to_spare = '';
			$seconds_to_spare = $predicted_finish_time - time();
			if ( $seconds_to_spare > 60 ) {
				$time_to_spare = 'Completed with ' . round( $seconds_to_spare / 60 ) . ' minutes to spare.';
			} elseif ( $seconds_to_spare > 0 ) {
				// Really just squeaked by!
				$time_to_spare = 'Completed with ' . $seconds_to_spare . ' seconds to spare!';
			}

			if ( ! empty( $time_to_spare ) ) {
				$time_to_spare = '<div class="mission-spare-time">' . $time_to_spare . '</div>';
			}

			$activity_id = bp_activity_add( array(
				'action' => $current_user->data->display_name . __( ' accomplished a mission', 'picolev' ),
				'content' => '<div class="mission-name">' . __( 'The mission: ', 'picolev' ) . get_the_title( $post_id ) . '</div>' . $time_to_spare,
				/* the component argument will be set to our component's identifier */
				'component' => 'picolev_mission',
				/* the type argument will be set to our component's type */
				'type' => 'picolev_mission_update',
			) );

			header( 'location: ' . esc_url( $_SERVER['REQUEST_URI'] ) );
		}
	}

	function show_current_mission() {
		global $active_missions_query_args;

		$active_mission = get_posts( $active_missions_query_args );

		if ( ! empty( $active_mission ) ) {

			$start_time = strtotime( $active_mission[0]->post_date );
			$predicted_finish_time = get_post_meta( $active_mission[0]->ID, 'predicted_time', true );

			$out = '<div class="picolev-mission current">' . "\r\n";
			$out .= '<div class="mission-name">' . $active_mission[0]->post_title . '</div>' . "\r\n";
			$out .= '<div class="mission-start-time">Started: <abbr class="timeago" title="' . date( 'c', $start_time ) . '">' . date( 'g:i A T', $start_time ) . '</abbr></div>' . "\r\n";
			$out .= '<div class="mission-predicted-finish-time">Deadline: <abbr class="timeago" title="' . date( 'c', $predicted_finish_time ) . '">' . date( 'g:i A T', $predicted_finish_time ) . '</abbr></div>' . "\r\n";
			$out .= '<form id="mission_accomplished" name="mission_accomplished" method="post" action="">' . "\r\n";
			$out .= '<input type="submit" value="Mission Accomplished!">' . "\r\n";
			$out .= '<input type="hidden" name="action" value="mission_accomplished">' . "\r\n";
			$out .= '<input type="hidden" name="mission_id" value="' . $active_mission[0]->ID . '">' . "\r\n";
			$out .= '</form></div>' . "\r\n";
			return $out;
		}
	}

	function has_active_mission() {
		global $active_missions_query_args, $current_user;

		if ( 0 == $current_user->ID ) {
			return false;
		} else {
			$active_mission = get_posts( $active_missions_query_args );

			if ( ! empty( $active_mission ) ) {
				return true;
			} else {
				return false;
			}
		}
	}

	function do_picolev_mission_shortcode() {
		echo do_shortcode( '[picolev-mission]' );
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