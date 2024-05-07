<?php

// Build the Service façade now to make sure it will be correctly injected in the other Controllers.
use Codeception\Util\Autoload;
use TEC\Tickets\Seating\Tables\Layouts;
use TEC\Tickets\Seating\Tables\Seat_Types;

tribe()->get( \TEC\Tickets\Seating\Service\Service::class );

tribe()->get( Layouts::class )->truncate();
tribe()->get( Seat_Types::class )->truncate();

define( 'JSON_SNAPSHOT_OPTIONS', JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

Autoload::addNamespace( 'TEC\Tickets\Seating\Tests\Integration', __DIR__  );