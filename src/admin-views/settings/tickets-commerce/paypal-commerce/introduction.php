<?php
/**
 * The Template for displaying the Tickets Commerce Payments Settings.
 *
 * @todo    This whole file needs to be completely reviewed once the designs are correct in place.
 * @version TBD
 *
 */

use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Gateways\PayPal\Signup;

$merchant           = tribe( Merchant::class );
$is_merchant_active = $merchant->is_active();
$path               = tribe_resource_url( 'images/admin/paypal-logo.svg', false, null, Tribe__Tickets__Main::instance() );
?>
<div class="tec-tickets-commerce-paypal">
	<div id="tec-tickets-commerce-paypal-connect">
		<?php if ( $is_merchant_active ) : ?>
			<?php
			$name = $merchant->get_merchant_id();

			$disconnect_url        = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'paypal-disconnect' ] );
			$refresh_url           = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'paypal-refresh-access-token' ] );
			$refresh_user_info_url = Tribe__Settings::instance()->get_url( [ 'tab' => 'payments', 'tc-action' => 'paypal-refresh-user-info' ] );

			$disconnect        = ' <a href="' . esc_url( $disconnect_url ) . '">' . esc_html__( 'Disconnect', 'event-tickets' ) . '</a>';
			$refresh           = ' <a href="' . esc_url( $refresh_url ) . '">' . esc_html__( 'Refresh Access Token', 'event-tickets' ) . '</a>';
			$refresh_user_info = ' <a href="' . esc_url( $refresh_user_info_url ) . '">' . esc_html__( 'Refresh User Info', 'event-tickets' ) . '</a>';
			?>
			<p><?php esc_html_e( 'PayPal Status: Connected', 'event-tickets' ); ?></p>
			<p><?php echo esc_html( sprintf( __( 'Connected as: %1$s', 'event-tickets' ), $name ) ) . $disconnect; ?></p>
			<p><?php echo $refresh . $refresh_user_info; ?></p>
		<?php else : ?>
			<h2><?php esc_html_e( 'Accept online payments with PayPal!', 'event-tickets' ); ?></h2>
			<p>
				<?php esc_html_e( 'Start selling tickets to your events today with PayPal. Attendees can purchase tickets directly on your site using debt or credit cards with no additional fees.', 'event-tickets' ); ?>
			</p>
			<?php echo tribe( Signup::class )->get_link_html(); ?>
		<?php endif; ?>
	</div>
	<div class="tec-tickets-commerce-paypal-logo">
		<img src="<?php echo esc_url( $path ); ?>" width="316" height="84" alt="<?php esc_attr_e( 'PayPal Logo Image', 'event-tickets' ); ?>">
		<ul>
			<li>
				<?php esc_html_e( 'Credit and Debit Card payments', 'event-tickets' ); ?>
			</li>
			<li>
				<?php esc_html_e( 'Easy no-API key connection', 'event-tickets' ); ?>
			</li>
			<li>
				<?php esc_html_e( 'Accept payments from around the world', 'event-tickets' ); ?>
			</li>
			<li>
				<?php esc_html_e( 'Supports 3D Secure payments', 'event-tickets' ); ?>
			</li>
		</ul>
	</div>
</div>