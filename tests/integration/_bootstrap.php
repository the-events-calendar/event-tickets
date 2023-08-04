<?php

// Disconnect Promoter to avoid license-related notices.

use TEC\Tickets\Commerce\Provider as Commerce_Provider;
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );
add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
tribe_register_provider( Commerce_Provider::class );