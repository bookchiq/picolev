<?php
class PicolevBadges {
	function __construct() {
		add_action( 'init', array( 'PicolevBadges', 'init' ) );
		add_action( 'bp_before_member_header_meta', array( 'PicolevBadges', 'show_badges' ) );
	}

	/**
	 * Default initialization for the plugin:
	 * - Adds the custom post type.
	 */
	function init() {
		register_post_type(
			'picolev_badge',
			array(	
				'label' => 'Badge',
				'description' => '',
				'public' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'hierarchical' => true,
				'has_archive' => true,
				'query_var' => true,
				'exclude_from_search' => false,
				'rewrite' => array( 'slug' => 'mission' ),
				'supports' => array(
					'title',
					'thumbnail'
				),
				'labels' => array (
					'name' => 'Badges',
					'singular_name' => 'Badge',
					'menu_name' => 'Badges',
					'all_items' => 'All Badges',
					'add_new' => 'Add Badge',
					'add_new_item' => 'Add New Badge',
					'edit' => 'Edit',
					'edit_item' => 'Edit Badge',
					'new_item' => 'New Badge',
					'view' => 'View Badge',
					'view_item' => 'View Badge',
					'search_items' => 'Search Badges',
					'not_found' => 'No Badges Found',
					'not_found_in_trash' => 'No Badges Found in Trash',
					'parent' => 'Parent Badge',
				),
			)
		);
	}

	function award_badges() {
		// Get a list of badges earned
		$earned_badges = get_user_meta( $user_id, 'picolev_badges', true );
		if ( empty( $earned_badges ) ) {
			$earned_badges = array();
		}

		// Get a list of badges available
		$badges = get_posts(  array(
			'numberposts'		=>	-1,
			'post_type'			=>	'picolev_badge',
			'post_status'		=>	'publish' )
		);

		if ( ! empty( $badges ) ) {
			$available_badges = array( '72' );

			foreach ( $badges as $badge ) {
				$available_badges[] = $badge->ID;
			}
		}

		// Check un-earned badges for eligibility
		$unearned_badges = array_diff( $available_badges, $earned_badges );

		if ( ! empty( $unearned_badges ) ) {
			// Get the basic statistics
			global $current_user;
			$user_id = $current_user->ID;

			$completed_missions = get_user_meta( $user_id, 'picolev_missions_completed', true );
			$points = PicolevMission::get_points( $user_id );
			$streak = PicolevMission::get_streak( $user_id );

			foreach( $unearned_badges as $potential_badge ) {
				// Get the criteria for this badge, and see if the user has passed it
				if ( $badge_criteria = get_post_meta( $potential_badge, '_picolev_badge_criteria', true ) ) {
					$met_all_criteria = true;

					foreach( $badge_criteria['picolev_badge'] as $criteria ) {
						$variable = $$criteria['picolev_badge_variable'];
						$value = $criteria['picolev_badge_value'];

						switch ( $criteria['picolev_badge_operator'] ) {
							case 'gt':
								$result = ( $variable > $value );
								break;
							case 'eq':
								$result = ( $variable == $value );
								break;
							default:
								$result = false;
								break;
						}

						if ( ! $result ) {
							$met_all_criteria = false;
						}
						// log_it( '$criteria', $result );
					}

					if ( $met_all_criteria ) {
						// Award the badge
						// Refactor: trigger the alert
						$earned_badges[] = $potential_badge;

						update_user_meta( $user_id, 'picolev_badges', $earned_badges );
					}
				}
				
			}
		}
	}

	function show_badges() {
		if ( $earned_badges = get_user_meta( bp_displayed_user_id(), 'picolev_badges', true ) ) {
			echo '<div class="picolev-badges">';
			foreach( $earned_badges as $earned_badge ) {
				echo get_the_post_thumbnail( $earned_badge, 'full' );
			}
			echo '</div>';
		}
	}
}
$picolev_badges = new PicolevBadges();