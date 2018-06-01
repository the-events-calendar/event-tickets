<?php

class Tribe__Tickets__REST__V1__Documentation__Ticket_Definition_Provider
	implements Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * While the structure must conform to that used by v2.0 of Swagger the structure can be that of a full document
	 * or that of a document part.
	 * The intelligence lies in the "gatherer" of informations rather than in the single "providers" implementing this
	 * interface.
	 *
	 * @since TBD
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation() {
		$documentation = array(
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'type' => 'integer',
					'description' => __( 'The ticket WordPress post ID', 'event-tickets' ),
				),
			),
		);

		/**
		 * Filters the Swagger documentation generated for a tikcet in the Event Ticker REST API.
		 *
		 * @since TBD
		 *
		 * @param array $documentation An associative PHP array in the format supported by Swagger.
		 *
		 * @link http://swagger.io/
		 */
		$documentation = apply_filters( 'tribe_tickets_rest_swagger_event_documentation', $documentation );

		return $documentation;
	}
}
