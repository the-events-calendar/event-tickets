<?php

namespace PHPSTORM_META {
	$map = [
		''                            => '\@',
		'tickets.main'                => \Tribe__Tickets__Main::class,
		'tickets.rsvp'                => \Tribe__Tickets__RSVP::class,
		'tickets.commerce.cart'       => \Tribe__Tickets__Commerce__Cart::class,
		'tickets.commerce.currency'   => \Tribe__Tickets__Commerce__Currency::class,
		'tickets.commerce.paypal'     => \Tribe__Tickets__Commerce__PayPal__Main::class,
		'tickets.redirections'        => \Tribe__Tickets__Redirections::class,
		'tickets.theme-compatibility' => \Tribe__Tickets__Theme_Compatibility::class,
		'tickets.main'                => \Tribe__Tickets__Main::class,
		'tickets.status'              => \Tribe__Tickets__Status__Manager::class,

		'tickets.rest-v1.main'         => \Tribe__Tickets__REST__V1__Main::class,
		'tickets.rest-v1.messages'     => \Tribe__Tickets__REST__V1__Messages::class,
		'tickets.rest-v1.headers-base' => \Tribe__Tickets__REST__V1__Headers__Base::class,
		'tickets.rest-v1.settings'     => \Tribe__Tickets__REST__V1__Settings::class,
		'tickets.rest-v1.system'       => \Tribe__Tickets__REST__V1__System::class,
		'tickets.rest-v1.validator'    => \Tribe__Tickets__REST__V1__Validator__Base::class,
		'tickets.rest-v1.repository'   => \Tribe__Tickets__REST__V1__Post_Repository::class,

	];

	// Allow PhpStorm IDE to resolve return types when calling tribe( Object_Type::class ) or tribe( `Object_Type` )
	override( \tribe( 0 ), map( $map ) );
	override( \tad_DI52_Container::make( 0 ), map( $map ) );
}