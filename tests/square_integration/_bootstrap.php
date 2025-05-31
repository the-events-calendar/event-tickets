<?php
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\Settings;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Webhooks;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );

// Let's make sure Commerce is enabled.
putenv( 'TEC_TICKETS_COMMERCE=1' );
putenv( 'TEC_DISABLE_LOGGING=1' );

// Ensure `post` is a ticketable post type.
$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
$ticketable[] = 'post';
tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

define( 'JSON_SNAPSHOT_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

// Start the posts auto-increment from a high number to make it easier to replace the post IDs in HTML snapshots.
DB::query( DB::prepare( "ALTER TABLE %i AUTO_INCREMENT = 5096", DB::prefix( 'posts' ) ) );

// Disconnect Promoter to avoid license-related notices.
remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );

tec_tickets_tests_fake_transactions_enable();

// Enable production TC mode for testing.
add_filter( 'tec_tickets_commerce_is_sandbox_mode', '__return_false' );

function tec_tickets_tests_get_fake_merchant_data(): array {
	return [
		'merchant_id'       => 'mi-8PoFNX4o9XOz9vMYOrZ6vA',
		'access_token'      => 'at-8PoFNX4o9XOz9vMYOrZ6vA',
		'refresh_token'     => 'rt-8PoFNX4o9XOz9vMYOrZ6vA',
		'merchant_country'  => 'US',
		'merchant_currency' => 'USD',
	];
}

$merchant = tribe( Merchant::class );
// Set merchant data.
$merchant->save_signup_data( tec_tickets_tests_get_fake_merchant_data() );
// Set a location ID.
tribe_update_option( Settings::OPTION_LOCATION_ID, 'li-8PoFNX4o9XOz9vMYOrZ6vA' );
// Set the gateway to enabled.
tribe_update_option( Gateway::get_enabled_option_key(), true );
// Set the webhook.
tribe_update_option( Webhooks::OPTION_WEBHOOK, require __DIR__ . '/../_data/square-webhook.php' );

// Enable pretty permalinks.
update_option( 'permalink_structure', '/%postname%/' );

// When we have logs of level error, critical, warning, throw an exception.
add_action( 'tribe_log', static function ( $level, $message, $context ) {
	if ( ! in_array( $level, [ 'error', 'critical', 'warning' ], true ) ) {
		return;
	}

	throw new RuntimeException( 'Triggered log: ' . $message . ' with context: ' . wp_json_encode( $context, JSON_SNAPSHOT_OPTIONS ) );
} );

tec_tickets_tests_enable_gateway_id_generation();
// Register last so that our settings updates are applied.
tribe_register_provider( Commerce_Provider::class );
