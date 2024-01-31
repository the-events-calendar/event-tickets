<?php
?>

<div id="tribe_panel_settings" class="ticket_panel panel_settings" aria-hidden="true" >
	<h4><?php
		echo esc_html(
			sprintf(
				_x( '%s Settings', 'meta box ticket form heading', 'event-tickets' ),
				tribe_get_ticket_label_singular( 'meta_box_ticket_form_heading' )
			)
		); ?>
	</h4>

	<section class="settings_main">
		<?php
		/**
		 * Allows for the insertion of additional elements into the ticket settings admin panel above the ticket table
		 *
		 * @since 4.6
		 *
		 * @param int Post ID
		 */
		do_action( 'tribe_events_tickets_settings_content_before', $post_id );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$admin_views->template( 'editor/fieldset/settings-provider' );

		/**
		 * Allows for the insertion of additional elements into the ticket settings admin panel below the ticket table
		 *
		 * @since 4.6
		 *
		 * @param int Post ID
		 */
		do_action( 'tribe_events_tickets_settings_content', $post_id );
		?>
	</section>

	<?php $admin_views->template( 'editor/panel/header-image', [ 'post_id' => $post_id ] ); ?>

	<input type="button" id="tribe_settings_form_save" name="tribe_settings_form_save" value="<?php esc_attr_e( 'Save settings', 'event-tickets' ); ?>" class="button-primary" />
	<input type="button" id="tribe_settings_form_cancel" name="tribe_settings_form_cancel" value="<?php esc_attr_e( 'Cancel', 'event-tickets' ); ?>" class="button-secondary" />
</div>
