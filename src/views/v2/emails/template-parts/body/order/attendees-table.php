<?php
/**
 * Event Tickets Emails: Order Attendees Table
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/emails/template-parts/body/order/attendees-table.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version TBD
 *
 * @since TBD
 *
 * @var Tribe_Template  $this  Current template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var \WP_Post         $order                 [Global] The order object.
 * @var int              $order_id              [Global] The order ID.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 */

// @todo @codingmusician @juanfra Replace hardcoded data with dynamic data.
$attendees = [
	[
		'name' => 'David Hickox',
		'email' => 'david@theeventscalendar.com',
		'custom_fields' => [
			[
				'label' => 'Shirt size',
				'value' => 'large'
			],
			[
				'label' => 'Backstage pass',
				'value' => 'yes'
			],
		],
		'ticket_title' => 'General Admission',
		'ticket_id' => '17e4a14cec',
	],
	[
		'name' => 'Matt Whitson',
		'email' => 'matt@aptv.org',
		'custom_fields' => [
			[
				'label' => 'Shirt size',
				'value' => 'small'
			],
			[
				'label' => 'Backstage pass',
				'value' => 'yes'
			],
		],
		'ticket_title' => 'General Admission',
		'ticket_id' => '55e5e14w4',
	],
];

?>
<tr>
	<td class="tec-tickets__email-table-content-order-attendees-table-container">
		<table class="tec-tickets__email-table-content-order-attendees-table">
			<?php $this->template( 'template-parts/body/order/attendees-table/header-row' ); ?>
			<?php foreach ( $attendees as $attendee ) : ?>
				<?php $this->template( 'template-parts/body/order/attendees-table/attendee-info', [ 'attendee' => $attendee ] ); ?>
			<?php endforeach; ?>
		</table>
	</td>
</tr>