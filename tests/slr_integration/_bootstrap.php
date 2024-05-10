<?php

// Build the Service façade now to make sure it will be correctly injected in the other Controllers.
use Codeception\Util\Autoload;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Seat_Types;
use \TEC\Tickets\Commerce\Provider as Commerce_Provider;

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );

// Let's make sure Commerce is enabled.
putenv( 'TEC_TICKETS_COMMERCE=1' );
putenv( 'TEC_DISABLE_LOGGING=1' );
tribe_register_provider( Commerce_Provider::class );

tribe()->get( \TEC\Tickets\Seating\Service\Service::class );

tribe()->get( Layouts::class )->truncate();
tribe()->get( Seat_Types::class )->truncate();

define( 'JSON_SNAPSHOT_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

Autoload::addNamespace( 'TEC\Tickets\Seating\Tests\Integration', __DIR__  );