<?php
/**
 * Class Tribe__Tickets__REST__V1__Documentation__QR_Definition_Provider
 *
 * @since 5.7.0
 */
class Tribe__Tickets__REST__V1__Documentation__QR_Definition_Provider
	implements Tribe__Documentation__Swagger__Provider_Interface {

	/**
	 * Returns an array in the format used by Swagger 2.0.
	 *
	 * @since 5.7.0
	 *
	 * While the structure must conform to that used by v2.0 of Swagger the structure can be that of a full document
	 * or that of a document part.
	 * The intelligence lies in the "gatherer" of information rather than in the single "providers" implementing this
	 * interface.
	 *
	 * @link http://swagger.io/
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation() {
		$documentation = [
			'type'       => 'object',
			'properties' => [
				'id'            => [
					'type'        => 'integer',
					'description' => __( 'The ticket WordPress post ID', 'event-tickets' ),
				],
				'api_key'       => [
					'type'        => 'string',
					'description' => __( 'The API key to authorize check in', 'event-tickets' ),
				],
				'security_code' => [
					'type'        => 'string',
					'description' => __( 'The security code of the ticket to verify for check in', 'event-tickets' ),
				],
				'event_id'      => [
					'type'        => 'integer',
					'description' => __( 'The event WordPress post ID', 'event-tickets' ),
				],
			],
		];

		/**
		 * Filters the Swagger documentation generated for an QR in the ET+ REST API.
		 *
		 * @since 5.7.0
		 *
		 * @param array $documentation An associative PHP array in the format supported by Swagger.
		 *
		 * @link http://swagger.io/
		 */
		$documentation = apply_filters( 'tribe_rest_swagger_qr_documentation', $documentation );

		return $documentation;
	}
}
