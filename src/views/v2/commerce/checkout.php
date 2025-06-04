<?php
/**
 * Tickets Commerce: Checkout Page
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.1.9
 * @since 5.3.0 Added purchaser info templates.
 * @since 5.19.3 Turned form element back to section.
 *
 * @version 5.19.3
 *
 * @var \Tribe__Template $this                  [Global] Template object.
 * @var Module           $provider              [Global] The tickets provider instance.
 * @var string           $provider_id           [Global] The tickets provider class name.
 * @var array[]          $items                 [Global] List of Items on the cart to be checked out.
 * @var array[]          $sections              [Global] Which events we have tickets for.
 * @var bool             $is_tec_active         [Global] Whether `The Events Calendar` is active or not.
 * @var array[]          $gateways              [Global] An array with the gateways.
 * @var bool             $has_error             [Global] Whether there is an error or not.
 * @var array            $error                 [Global] Title and Content of the error.
 */

$attributes = [
	'data-js' => 'tec-tickets-commerce-notice',
	'data-notice-default-title' => esc_attr__( 'Checkout Unavailable!' , 'event-tickets' ),
	'data-notice-default-content' => esc_attr__( 'Checkout is not available at this time because a payment method has not been set up for this event. Please notify the site administrator.' , 'event-tickets' ),
];

?>
<div class="tribe-common event-tickets">
	<section
		class="tribe-tickets__commerce-checkout"
		<?php tribe_attributes( $attributes ); ?>
	>
		<?php $this->template( 'checkout/fields' ); ?>
		<?php $this->template( 'checkout/header' ); ?>
		<?php foreach ( $sections as $section ) : ?>
			<?php $this->template( 'checkout/cart', [ 'section' => $section ] ); ?>
		<?php endforeach; ?>
		<?php tribe( 'tickets.editor.template' )->template( 'v2/components/loader/loader' ); ?>
		<?php
		tribe( 'tickets.editor.template' )->template(
				'components/notice',
				[
					'notice_classes'  => [
						'tribe-tickets__notice--error',
						'tribe-tickets__commerce-checkout-notice',
						$has_error ? 'tribe-tickets__commerce-checkout-notice--visible' : '',
					],
					'content_classes' => [ 'tribe-tickets__commerce-checkout-notice-content' ],
					'title'           => $error['title'] ?? esc_html__( 'Checkout Error!', 'event-tickets' ),
					'content'         => $error['content'] ?? esc_html__( 'Something went wrong!', 'event-tickets' ),
				]
		);
		?>
		<?php $this->template( 'checkout/cart/empty' ); ?>
		<?php $this->template( 'checkout/purchaser-info' ); ?>
		<?php $this->template( 'checkout/gateways' ); ?>
		<?php $this->template( 'checkout/footer' ); ?>
		<?php $this->template( 'checkout/must-login' ); ?>
	</section>
</div>
