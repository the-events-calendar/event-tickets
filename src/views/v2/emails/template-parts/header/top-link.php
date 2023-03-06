<?php
/**
 * Event Tickets Emails: Main template > Header > Top link.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/header/top-link.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 5.5.9
 *
 * @since 5.5.9
 *
 * @var Tribe_Template  $this  Current template object.
 */

// @todo Update link URL
?>
	<div class="tec-tickets__email-preview-link" align="center">
		<?php
		echo sprintf(
			'%s <a href="#" style="color:#3C434A;">%s</a>',
			esc_html__( 'Having trouble viewing this email?', 'event-tickets' ),
			esc_html__( 'Click here', 'event-tickets' ),
		);
		?>
	</div>
