<?php
/**
 * Checkout page set up notice.
 *
 * @since TBD
 *
 * @var TEC\Tickets\Commerce\Settings   $this               Settings object.
 * @var array                           $notice_heading     Notice heading/title.
 * @var array                           $notice_content     Notice body/text.
 */

?>
<div class="event-tickets__admin-banner">
	<h3><?php esc_html_e( $notice_heading ); ?></h3>
	<p class="event-tickets__admin-banner-help-text">
		<?php 
			echo wp_kses( $notice_content, 'post' ); 
		?>
	</p>
</div>
