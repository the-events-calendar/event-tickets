<?php
use \Tribe__Utils__Array as Arr;

/**
 * Builds and returns the correct Orders repository.
 *
 * @since TBD
 *
 * @param string $repository The slug of the repository to build/return.
 *
 * @return Tribe__Repository__Interface An instance of the requested repository
 *                                      class.
 */
function tec_tc_orders( $repository = 'default' ) {
	$map = [
		'default' => TEC\Tickets\Commerce\Repositories\Order::class,
	];

	$args = func_num_args() > 1 ? array_slice( func_get_args(), 1 ) : [];

	/**
	 * Filters the map relating orders repository slugs to service container bindings.
	 *
	 * @since TBD
	 *
	 * @param array  $map        A map in the shape [ <repository_slug> => <service_name> ]
	 * @param string $repository The currently requested implementation.
	 * @param array  $args       An array of additional call arguments used to call the function beside the
	 *                           repository slug.
	 */
	$map = apply_filters( 'tec_tickets_commerce_orders_repository_map', $map, $repository, $args );

	return tribe( Arr::get( $map, $repository, $map['default'] ) );
}

/**
 * Builds and returns the correct Tickets repository.
 *
 * @since TBD
 *
 * @param string $repository The slug of the repository to build/return.
 *
 * @return Tribe__Repository__Interface An instance of the requested repository
 *                                      class.
 */
function tec_tc_tickets( $repository = 'default' ) {
	$map = [
		'default' => TEC\Tickets\Commerce\Repositories\Tickets::class,
	];

	$args = func_num_args() > 1 ? array_slice( func_get_args(), 1 ) : [];

	/**
	 * Filters the map relating tickets repository slugs to service container bindings.
	 *
	 * @since TBD
	 *
	 * @param array  $map        A map in the shape [ <repository_slug> => <service_name> ]
	 * @param string $repository The currently requested implementation.
	 * @param array  $args       An array of additional call arguments used to call the function beside the
	 *                           repository slug.
	 */
	$map = apply_filters( 'tec_tickets_commerce_tickets_repository_map', $map, $repository, $args );

	return tribe( Arr::get( $map, $repository, $map['default'] ) );
}

/**
 * Builds and returns the correct Attendees repository.
 *
 * @since TBD
 *
 * @param string $repository The slug of the repository to build/return.
 *
 * @return Tribe__Repository__Interface An instance of the requested repository
 *                                      class.
 */
function tec_tc_attendees( $repository = 'default' ) {
	$map = [
		'default' => TEC\Tickets\Commerce\Repositories\Attendees::class,
	];

	$args = func_num_args() > 1 ? array_slice( func_get_args(), 1 ) : [];

	/**
	 * Filters the map relating attendees repository slugs to service container bindings.
	 *
	 * @since TBD
	 *
	 * @param array  $map        A map in the shape [ <repository_slug> => <service_name> ]
	 * @param string $repository The currently requested implementation.
	 * @param array  $args       An array of additional call arguments used to call the function beside the
	 *                           repository slug.
	 */
	$map = apply_filters( 'tec_tickets_commerce_attendees_repository_map', $map, $repository, $args );

	return tribe( Arr::get( $map, $repository, $map['default'] ) );
}
