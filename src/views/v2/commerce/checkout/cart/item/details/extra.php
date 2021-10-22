<?php
/**
 * Tickets Commerce: Checkout Cart Item Extra details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/item/details/extra.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since   TBD
 *
 * @version TBD
 *
 * @var \Tribe__Template $this             [Global] Template object.
 * @var Module           $provider         [Global] The tickets provider instance.
 * @var string           $provider_id      [Global] The tickets provider class name.
 * @var array[]          $items            [Global] List of Items on the cart to be checked out.
 * @var bool             $must_login       [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url        [Global] The site's login URL.
 * @var string           $registration_url [Global] The site's registration URL.
 * @var bool             $is_tec_active    [Global] Whether `The Events Calendar` is active or not.
 * @var array[]          $gateways         [Global] An array with the gateways.
 * @var int              $gateways_active  [Global] The number of active gateways.
 * @var array            $item             Which item this row will be for.
 */

use \TEC\Tickets\Commerce\Module;

if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
	return;
}

$iac                      = tribe( 'tickets-plus.attendee-registration.iac' );
$iac_field_configuration  = $iac->get_field_configurations( $item['ticket_id'] );
$meta                     = tribe( 'tickets-plus.meta' );
$meta_field_configuration = $meta->get_meta_fields_by_ticket( $item['ticket_id'] );
$field_configuration      = array_merge( $iac_field_configuration, $meta_field_configuration );
?>
<?php if ( ! empty( $item['extra']['attendees'] ) ) : ?>
	<?php foreach ( $item['extra']['attendees'] as $attendee ) : ?>
		<div class="tribe-tickets__commerce-checkout-cart-item-details-description-attendee">
			<?php foreach ( $iac_field_configuration as $field ) : ?>
				<?php if ( ! isset( $attendee['meta'][ $field->slug ] ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>

				<?php
				$short_slug = 'attendee-' . str_replace( 'tribe-tickets-plus-iac-', '', $field->slug );
				?>

				<div class="tribe-tickets__commerce-checkout-cart-item-details-description-<?php echo esc_attr( $short_slug ); ?>"><?php echo esc_html( $attendee['meta'][ $field->slug ] ); ?></div>
			<?php endforeach; ?>
			<?php
			$field_data = [];
			?>
			<?php foreach ( $meta_field_configuration as $field ) : ?>
				<?php
				$options = $field->get_hashed_options_map();
				if ( ! empty( $options ) ) {
					$values = [];
					foreach ( $options as $option_slug => $option_label ) {
						if ( ! isset( $attendee['meta'][ $option_slug ] ) ) {
							continue;
						}

						$values[] = $attendee['meta'][ $option_slug ];
					}

					$field_data[] = "{$field->label}: " . implode( ', ', $values );
				} else {
					$slug = $field->slug;

					if ( ! isset( $attendee['meta'][ $slug ] ) ) {
						continue;
					}

					$field_data[] = "{$field->label}: {$attendee['meta'][ $slug ]}";
				}
				?>
			<?php endforeach; ?>
			<div class="tribe-tickets__commerce-checkout-cart-item-details-description-attendee-fields"><?php echo esc_html( implode( ', ', $field_data ) ); ?></div>
		</div>
	<?php endforeach; ?>
<?php endif; ?>