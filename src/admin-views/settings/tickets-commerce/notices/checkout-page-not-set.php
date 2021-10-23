<?php
/**
 * Checkout page set up notice.
 *
 * @since TBD
 *
 */

?>
<div class="event-tickets__admin-banner">
	<h3><?php echo esc_html__( 'Set up your checkout page', 'event-tickets' ); ?></h3>
	<p class="event-tickets__admin-banner-help-text">
		<?php 
			echo sprintf( 
				esc_html__( 
					"In order to start selling with Tickets Commerce, you'll need to set up " . 
					"your checkout page. Please configure the setting on Settings > Payments and " . 
					"confirm that the page you have selected has the proper shortcode. " . 
					"%sLearn more%s" 
				),
				'<a href="https://evnt.is/1axv" target="_blank" rel="noopener noreferrer">',
				'</a>'
			); 
		?>
	</p>
</div>
