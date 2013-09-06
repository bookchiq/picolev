<?php
class PicolevMission {
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
			if ( PicolevMission::has_active_mission() ) {
				if( 'POST' == $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['action'] ) && 'mission_accomplished' == $_POST['action'] && ! empty( $_POST['mission_id'] ) ) {
					PicolevMission::complete_mission();
					return PicolevMission::output_mission_form();
				} elseif ( 'POST' == $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['action'] ) && 'mission_aborted' == $_POST['action'] && ! empty( $_POST['mission_id'] ) ) {
					PicolevMission::abort_mission();
					return PicolevMission::output_mission_form();
				} else {
					return PicolevMission::show_current_mission();
				}
			} else {
				if( 'POST' == $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['action'] ) && 'new_mission' == $_POST['action'] ) {
					// The form has been submitted and needs to be processed
					return PicolevMission::add_mission();
				} else {
					return PicolevMission::output_mission_form();
				}
			}
		}
	}

	function output_mission_form() {
		$out = '<div class="picolev-mission new" id="postbox">' . "\r\n";
		$out .= '<div class="wrapper">' . "\r\n";
		$out .= '<form id="new_mission" name="new_mission" method="post" action="">' . "\r\n";

		$out .= "<h2>Let's go!</h2>\r\n";
		$out .= '<p><label for="title">What\'s your next mission?</label>' . "\r\n";
		$out .= '<input type="text" id="title" value="" tabindex="1000" name="title" />' . "\r\n";
		$out .= '</p>' . "\r\n";

		$out .= '<p><label for="predicted_time">How many minutes will it take you?</label>' . "\r\n";
		$out .= '<input type="text" id="predicted_time" value="" tabindex="1001" name="predicted_time" />' . "\r\n";
		$out .= '</p>' . "\r\n";

		$out .= '<p><input type="submit" value="Go!" tabindex="1002"></p>' . "\r\n";
		$out .= '<input type="hidden" name="action" value="new_mission">' . "\r\n";
		$out .= '</form></div></div>';
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


			// Add to the activity stream
			$activity_id = bp_activity_add( array(
				'action' => $current_user->data->display_name . __( ' accepted a mission', 'picolev' ),
				'content' => '<div class="mission-name">' . __( 'The mission: ', 'picolev' ) . $title . '</div><div class="mission-predicted-finish-time">The deadline: <abbr class="timeago" title="' . date( 'c', $predicted_finish_time ) . '">' . date( 'g:i A T', $predicted_finish_time ) . '</abbr></div>',
				'component' => 'PicolevMission',
				'type' => 'picolev_mission_update',
			) );

			// Connect the activity ID to the mission
			update_post_meta( $post_id, 'activity_id', $activity_id );

			// If this is the first mission of the day, give 'em some points for playing
			$beginning_of_today = strtotime( date( 'Y-m-d' ) . ' 00:00:00' );
			$most_recent_points = get_user_meta( $current_user->ID, 'picolev_points_modified', true );
			if ( empty( $most_recent_points ) OR ( $most_recent_points < $beginning_of_today ) ) {
				// Reset the daily point count
				update_user_meta( $current_user->ID, 'picolev_daily_points', 0 );
				PicolevMission::add_points( 5 );
			}

			header( 'location: ' . esc_url( $_SERVER['REQUEST_URI'] ) );

			return PicolevMission::show_current_mission();
		} else {
			return $out . PicolevMission::output_mission_form();
		}
	}

	function complete_mission() {
		global $current_user;
		if ( ! empty( $_POST['mission_id'] ) && 0 != $current_user->ID ) {
			$mission_id = intval( $_POST['mission_id'], 10 );

			$points_earned = 10; // These are just for accomplishing a mission, period
			$current_streak = get_user_meta( $current_user->ID, 'picolev_streak', true );

			update_post_meta( $mission_id, 'completed', time() );
			$predicted_finish_time = get_post_meta( $mission_id, 'predicted_time', true );
			$time_to_spare = '';
			$seconds_to_spare = $predicted_finish_time - time();
			if ( $seconds_to_spare > 60 ) {
				$time_to_spare = ' with ' . round( $seconds_to_spare / 60 ) . ' minutes to spare';
			} elseif ( $seconds_to_spare > 0 ) {
				// Really just squeaked by!
				$time_to_spare = ' with ' . $seconds_to_spare . ' seconds to spare (!)';
			}

			if ( ! empty( $time_to_spare ) ) {
				$time_to_spare = '<span class="mission-spare-time">' . $time_to_spare . '</span>';

				// Update mission stats
				$missions_completed_before_deadline = ( $tempvar = get_user_meta( $current_user->ID, 'picolev_missions_completed_before_deadline', true ) ) ? $tempvar : 0;
				$missions_completed_before_deadline++;
				update_user_meta( $current_user->ID, 'picolev_missions_completed_before_deadline', $missions_completed_before_deadline );

				// Add bonus points!
				if ( ! empty( $current_streak ) ) {
					$points_earned += $current_streak;
					$current_streak++;
				} else {
					// We're starting a new streak. Woot!
					$current_streak = 1;
				}
			}

			$mission_activity_id = PicolevMission::get_activity_id_for_mission( $mission_id );

			$activity_id = bp_activity_add( array(
				'action' => $current_user->data->display_name . ' accomplished <a href="' . bp_activity_get_permalink( $mission_activity_id ) . '">a mission</a>' . $time_to_spare,
				'content' => '',
				'component' => 'PicolevMission',
				'type' => 'picolev_mission_update_success',
			) );

			PicolevMission::add_points( $points_earned );
			update_user_meta( $current_user->ID, 'picolev_streak', $current_streak );

			// Update mission stats
			$missions_completed = ( $tempvar = get_user_meta( $current_user->ID, 'picolev_missions_completed', true ) ) ? $tempvar : 0;
			$missions_completed++;
			update_user_meta( $current_user->ID, 'picolev_missions_completed', $missions_completed );

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
			$out .= '<div class="wrapper">' . "\r\n";
			$out .= '<h6>Your Current Mission</h6>';
			$out .= '<h1 class="mission-name">' . $active_mission[0]->post_title . '</h1>' . "\r\n";
			$out .= '<div class="mission-start-time">Started: <abbr class="timeago" title="' . date( 'c', $start_time ) . '">' . date( 'g:i A T', $start_time ) . '</abbr></div>' . "\r\n";
			$out .= '<div class="mission-predicted-finish-time">Deadline: <abbr class="timeago" title="' . date( 'c', $predicted_finish_time ) . '">' . date( 'g:i A T', $predicted_finish_time ) . '</abbr></div>' . "\r\n";
			$out .= '<form id="mission_accomplished" name="mission_accomplished" method="post" action="">' . "\r\n";
			$out .= '<input type="submit" value="Mark mission accomplished!">' . "\r\n";
			$out .= '<input type="hidden" name="action" value="mission_accomplished">' . "\r\n";
			$out .= '<input type="hidden" name="mission_id" value="' . $active_mission[0]->ID . '">' . "\r\n";
			$out .= '</form>' . "\r\n";
			$out .= '<form id="mission_aborted" name="mission_aborted" method="post" action="">' . "\r\n";
			$out .= '<input type="submit" value="...or abort mission and break your streak :(">' . "\r\n";
			$out .= '<input type="hidden" name="action" value="mission_aborted">' . "\r\n";
			$out .= '<input type="hidden" name="mission_id" value="' . $active_mission[0]->ID . '">' . "\r\n";
			$out .= '</form>' . "\r\n";
			$out .= '</div></div>' . "\r\n";
			return $out;
		}
	}

	function abort_mission() {
		global $current_user;
		if ( ! empty( $_POST['mission_id'] ) && 0 != $current_user->ID ) {
			$mission_id = intval( $_POST['mission_id'], 10 );

			// Update the mission
			update_post_meta( $mission_id, 'completed', -1 );
		
			// Update mission stats
			$missions_aborted = ( $tempvar = get_user_meta( $current_user->ID, 'picolev_missions_aborted', true ) ) ? $tempvar : 0;
			$missions_aborted++;
			update_user_meta( $current_user->ID, 'picolev_missions_aborted', $missions_aborted );

			// Reset the streak
			update_user_meta( $current_user->ID, 'picolev_streak', 0 );

			$mission_activity_id = PicolevMission::get_activity_id_for_mission( $mission_id );

			// Update the activity stream
			$activity_id = bp_activity_add( array(
				'action' => $current_user->data->display_name . ' aborted <a href="' . bp_activity_get_permalink( $mission_activity_id ) . '">a mission</a>',
				'content' => '',
				'component' => 'PicolevMission',
				'type' => 'picolev_mission_update_failure',
			) );
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

	function add_points( $points ) {
		global $current_user;

		$user_id = $current_user->ID;
		$meta_name = 'picolev_points';
		$new_daily_points = $points; 

		// Add to existing points, if they're set
		if ( $current_points = get_user_meta($user_id, $meta_name, true ) ) {
			$points += $current_points;
		}

		update_user_meta( $user_id, $meta_name, $points );
		update_user_meta( $user_id, 'picolev_points_modified', time() );

		// Update the daily count
		if ( $current_daily_points = get_user_meta($user_id, 'picolev_daily_points', true ) ) {
			$new_daily_points += $current_daily_points;
		}
		update_user_meta( $current_user->ID, 'picolev_daily_points', $new_daily_points );

		// Check for eligible badges and award them
		PicolevBadges::award_badges();
	}

	function get_activity_id_for_mission( $mission_id ) {
		return get_post_meta( $mission_id, 'activity_id', true );
	}

	function get_mission_for_activity( $activity_id ) {
		$missions = get_posts( array(
			'numberposts'		=>	1,
			'meta_key'			=>	'activity_id',
			'meta_value'		=>	$activity_id,
			'post_type'			=>	PICOLEV_SLUG )
		);

		if ( ! empty( $missions[0] ) ) {
			return $missions[0];
		} else {
			return false;
		}
	}

	function get_points( $user_id = null ) {
		if ( empty( $user_id ) ) {
			global $current_user;
			$user_id = $current_user->ID;
		}
		
		$current_points = get_user_meta($user_id, 'picolev_points', true );

		return ( ! empty( $current_points ) ) ? $current_points : '0';
	}

	function get_streak( $user_id = null ) {
		if ( empty( $user_id ) ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		$current_streak = get_user_meta( $user_id, 'picolev_streak', true );
		
		return ( ! empty( $current_streak ) ) ? $current_streak : '0';
	}

	function get_daily_points( $user_id = null ) {
		if ( empty( $user_id ) ) {
			global $current_user;
			$user_id = $current_user->ID;
		}

		// Only a user if they have been active today
		$beginning_of_today = strtotime( date( 'Y-m-d' ) . ' 00:00:00' );
		$most_recent_points = get_user_meta( $user_id, 'picolev_points_modified', true );
		if ( ! empty( $most_recent_points ) && ( $most_recent_points > $beginning_of_today ) ) {
			$current_daily_points = get_user_meta( $user_id, 'picolev_daily_points', true );
			return $current_daily_points;
		} else {
			return 0;
		}
	}

	/**
	 * Called by shortcode [picolev-leaderboards]
	 * Output the leaderboards
	 * 
	 * @return string The content that will replace the shortcode
	 */
	function display_leaderboards() {
		// Add daily, too
		
		$agents_args = array(
			'meta_key'     => 'picolev_points',
			'meta_value'   => '0',
			'meta_compare' => '>',
			'orderby'      => 'login',
			'order'        => 'ASC',
			'fields'       => 'all_with_meta'
		);
		$agents = get_users( $agents_args );

		if ( ! empty( $agents ) ) {
			foreach( $agents as $member_id => $agent ) {
				// $current_user_points = PicolevMission::get_points( $member_id );

				$avatar = bp_core_fetch_avatar( array( 'item_id' => $member_id, 'type' => 'thumb' ) );

				$photo_link = ( ! empty( $avatar ) ) ? '<a href="' . bp_core_get_user_domain( $member_id ) . '" title="' . bp_core_get_user_displayname( $member_id ) . '">' . $avatar . '</a>' : '';

				$agent_details[ $member_id ] = array(
					'photo' => $photo_link,
					'link' => '<a href="' . home_url( '/members/' . $agent->data->user_nicename . '/' ) . '">' . $agent->data->display_name . '</a>',
					'points' => PicolevMission::get_points( $member_id ),
					'streak' => PicolevMission::get_streak( $member_id ),
				);

				// Get daily points
				$daily_points = PicolevMission::get_daily_points( $member_id );
				if ( ! empty( $daily_points ) ) {
					$agent_details_daily[ $member_id ] = $agent_details[ $member_id ];
					$agent_details_daily[ $member_id ][ 'points' ] = $daily_points;
				}
			}
		}

		// Build the "today" content
		// for ( $agents as $agent )

		$out = '<div id="tab-leaderboard" class="tabbable ">
	<ul class="nav nav-tabs">
		<li class="active"><a href="#leaderboard_today" data-toggle="tab">Today</a></li>
		<li class=""><a href="#leaderboard_all_time" data-toggle="tab">All Time</a></li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane active" id="leaderboard_today">';

		$out .= PicolevMission::get_leaderboard_table( $agent_details_daily );

		$out .= '</div><div class="tab-pane" id="leaderboard_all_time">';
	
		$out .= PicolevMission::get_leaderboard_table( $agent_details );
		$out .= '</div></div></div>';

		return $out;
	}

	function get_leaderboard_table( $agents ) {
		if ( ! empty( $agents ) ) {

			$out = '
				<table class="tablesorter sortable">
					<thead>
						<tr>
							<th>&nbsp;</th>
							<th class="icon">Agent<i></i></th>
							<th class="icon">Points earned<i></i></th>
							<th class="icon">Streak<i></i></th>
						</tr>
					</thead>
					<tbody>';	

			foreach( $agents as $agent ) {
				$out .= '<tr>';
				$out .= '<td>' . $agent['photo'] . '</td>';
				$out .= '<td>' . $agent['link'] . '</td>';
				$out .= '<td>' . $agent['points'] . '</td>';
				$out .= '<td>' . $agent['streak'] . '</td>';
				$out .= '</tr>';
			}

			$out .= '</tbody>
				</table>';

			return $out;
		} else {
			return '<p>No eligible agents yet. <a href="' . home_url() . '">Be the first!</a>';
		}
	}

	function do_picolev_mission_shortcode() {
		echo do_shortcode( '[picolev-mission]' );
	}
}
$picolev_mission = new PicolevMission();