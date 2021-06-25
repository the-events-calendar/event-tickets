<?php

namespace PHPSTORM_META {
	$map = [
		'' => '@',
		'tickets.main' => \Tribe__Tickets__Main::class,
		'tickets.rsvp' => \Tribe__Tickets__RSVP::class,
		'tickets.commerce.cart' => \Tribe__Tickets__Commerce__Cart::class,
		'tickets.commerce.currency' => \Tribe__Tickets__Commerce__Currency::class,
		'tickets.commerce.paypal' => \Tribe__Tickets__Commerce__PayPal__Main::class,
		'tickets.redirections' => \Tribe__Tickets__Redirections::class,
		'tickets.theme-compatibility' => \Tribe__Tickets__Theme_Compatibility::class,
		'tickets.main' => \Tribe__Tickets__Main::class,
	];

	// Allow PhpStorm IDE to resolve return types when calling tribe( Object_Type::class ) or tribe( `Object_Type` )
	override( \tribe( 0 ), map( $map ) );
	override( \tad_DI52_ServiceProvider::make( 0 ), map( $map ) );
	override( \tad_DI52_Container::make( 0 ), map( $map ) );
}