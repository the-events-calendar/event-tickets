<?php
/**
 * Template to display a list of featured gateways.
 *
 * @since 5.3.0
 *
 * @var Tribe__Template    $this     Template object.
 * @var Abstract_Gateway[] $gateways Array of gateway objects.
 * @var Manager            $manager  Gateway Manager object.
 */

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Manager;

if ( empty( $gateways ) ) {
	return;
}

?>
<div class="tec-tickets__admin-settings-tickets-commerce-gateways">
	<?php
	foreach ( $gateways as $gateway ) {
		$this->template( 'gateways/item', [ 'gateway' => $gateway ] );
	}
	?>
</div>
