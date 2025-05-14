<?php

$tec_support = dirname( __DIR__, 3 ) . '/the-events-calendar/tests/_support';
Codeception\Util\Autoload::addNamespace( 'Tribe\Events\Test', $tec_support );

remove_action( 'tribe_tickets_plugin_loaded', function () {}, 10 );
