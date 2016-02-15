<div id="tribe-loading"><span></span></div>
<form method="POST" class="tribe-attendees-email">
	<div id="plugin-information-title">
		<?php esc_html_e( 'Send the attendee list by email', 'event-tickets' ); ?>
	</div>

	<div id="attendees_email_wrapper">
		<?php wp_nonce_field( 'email-attendees-list' ); ?>
		<label for="email_to_user">
			<span><?php esc_html_e( 'Select a User:', 'event-tickets' ); ?></span>
			<?php wp_dropdown_users(
				array(
					'name'             => 'email_to_user',
					'id'               => 'email_to_user',
					'show_option_none' => esc_html__( 'Select...', 'event-tickets' ),
					'selected'         => '',
				)
			); ?>
		</label>
		<span class="attendees_or"><?php esc_html_e( 'or', 'event-tickets' ); ?></span>
		<label for="email_to_address">
			<span><?php esc_html_e( 'Email Address:', 'event-tickets' ); ?></span>
			<input type="text" name="email_to_address" id="email_to_address" value="">
		</label>
	</div>
		<div id="plugin-information-footer">
		<?php
		if ( false !== $status ) {
			echo '<div class="tribe-attendees-email-message ' . ( is_wp_error( $status ) ? 'error ' : 'updated ' ) . 'notice is-dismissible">';
			if ( is_wp_error( $status ) ) {
				echo '<ul>';
				foreach ( $status->errors as $key => $error ) {
					echo '<li>' . wp_kses( reset( $error ), array() ) . '</li>';
				}
				echo '</ul>';
			} else {
				echo '<p>' . wp_kses( $status, array() ) . '</p>';
			}
			echo '</div>';
		}
		?>

		<?php echo '<button type="submit" class="button button-primary right" name="tribe-send-email" value="1">' . esc_html__( 'Send Email', 'event-tickets' ) . '</button>'; ?>
	</div>
</form>