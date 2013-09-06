<div class="my_meta_control">
	

		<?php while($mb->have_fields_and_multi('picolev_badge')): ?>
		<?php $mb->the_group_open(); ?>
		<div class="picolev_badge_row">
		
				<?php
				// Set up the variable select box
				$mb->the_field('picolev_badge_variable');
				$variable_select_box = '<select name="' . $mb->get_the_name() . '"><option value="">Select a variable...</option>';
				$variable_list = array( 
					'completed_missions' => 'Completed missions',
					'streak' => 'Streak',
					'points' => 'Points',
					// '' => 'Consecutive days',
					// '' => 'Average mission time window'
				);
				foreach ( $variable_list as $variable => $variable_name ) {
					$variable_select_box .= '<option value="' . $variable . '"' . $mb->get_the_select_state( $variable ) . '>' . $variable_name . '</option>';
				}
				$variable_select_box .= '</select>';

				echo '<label>Variable</label> ';
				echo $variable_select_box;
				?>
				<?php
				// Set up the operator select box
				$mb->the_field('picolev_badge_operator');
				$operator_select_box = '<select name="' . $mb->get_the_name() . '"><option value="">Operator...</option>';
				$operator_list = array( 
					'gt' => '>',
					'eq' => '=='
				);
				foreach ( $operator_list as $operator => $operator_name ) {
					$operator_select_box .= '<option value="' . $operator . '"' . $mb->get_the_select_state( $operator ) . '>' . $operator_name . '</option>';
				}
				$operator_select_box .= '</select>';

				echo '<label> </label> ';
				echo $operator_select_box;
				?>
				<?php $mb->the_field('picolev_badge_value'); ?>
				<label for="picolev-badge-value"> </label>
				<input type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>" id="picolev-badge-value">
			</div>
		</div>

		<?php $mb->the_group_close(); ?>
		<?php endwhile; ?>

		<p><a href="#" class="docopy-picolev_badge button">Add Another</a></p>

		<input type="submit" class="button-primary" name="save" value="Save">
</div>