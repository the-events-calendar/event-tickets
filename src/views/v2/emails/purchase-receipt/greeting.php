<?php
/**
 * Event Tickets Emails: Main template > Body > Greeting.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/greeting.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe_Template   $this                  Current template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

if ( empty( $order ) ) {
	return;
}

$hello = empty( $order['purchaser'] ) || empty( $order['purchaser']['first_name'] ) ? 
	__( 'Hello!', 'event-tickets' ) :
	sprintf(
		// Translators: %s - First name of purchaser.
		__( 'Hi, %s!', 'event-tickets' ),
		$order['purchaser']['first_name']
	);

?>
<tr>
	<td class="tec-tickets__email-table-content-greeting-container">
		<div>
			<?php esc_html_e( $hello ); ?>
		</div>
		<div>
			<?php 
				echo esc_html__( 'Below are the details of your recent ticket purchase.  Your tickets will arrive in a separate email.', 'event-tickets' );
			?>
		</div>
	</td>
</tr>
