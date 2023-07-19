<?php

// Disconnect Promoter to avoid license-related notices.
use Tribe\Tickets\Promoter\Triggers\Dispatcher;

remove_action( 'tribe_tickets_promoter_trigger', [ tribe( Dispatcher::class ), 'trigger' ] );
