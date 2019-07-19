<?php

/**
 * Class Tribe__Tickets__REST__V1__Documentation__Capacity_Details_Definition_Provider
 *
 * @since 4.8
 */
class Tribe__Tickets__REST__V1__Documentation__Capacity_Details_Definition_Provider
	implements Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * {@inheritdoc}
	 */
	public function get_documentation() {
		$documentation = array(
			'type'       => 'object',
			'properties' => array(
				'available_percentage' => array(
					'type'        => 'integer',
					'description' => __( 'The ticket available capacity percentage', 'event-tickets' ),
				),
				'max'                  => array(
					'type'        => 'integer',
					'description' => __( 'The ticket max capacity', 'event-tickets' ),
				),
				'available'            => array(
					'type'        => 'integer',
					'description' => __( 'The ticket current available capacity', 'event-tickets' ),
				),
				'sold'                 => array(
					'type'        => 'integer',
					'description' => __( 'The ticket sale count', 'event-tickets' ),
				),
				'pending'              => array(
					'type'        => 'integer',
					'description' => __( 'The ticket pending count', 'event-tickets' ),
				),
			),
		);

		/**
		 * Filters the Swagger documentation generated for capacity details in the Event Tickets REST API.
		 *
		 * @since 4.8
		 *
		 * @param array $documentation An associative PHP array in the format supported by Swagger.
		 *
		 * @link  http://swagger.io/
		 */
		$documentation = apply_filters( 'tribe_tickets_rest_swagger_capacity_details_documentation', $documentation );

		return $documentation;
	}
}
