<?php


class Dummy_Endpoint {

	/**
	 * @var Tribe__REST__Messages_Interface
	 */
	protected $messages;

	/**
	 * @var array
	 */
	protected $supported_query_vars = [];

	/**
	 * Tribe__Tickets__REST__V1__Endpoints__Base constructor.
	 *
	 * @since 5.9.1
	 *
	 * @param \Tribe__Tickets__REST__V1__Messages $messages
	 */
	public function __construct( \Tribe__Tickets__REST__V1__Messages $messages ) {
		$this->messages = $messages;
	}

	/**
	 * Get Dummy Data
	 *
	 * @since 5.9.1
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get( WP_REST_Request $request ) {

		$data = $this->get_data();

		return new WP_REST_Response( $data );
	}

	/**
	 * Get WP Error
	 *
	 * @since 5.9.1
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error
	 */
	public function get_error( WP_REST_Request $request ) {

		return new WP_Error( 'ticket-not-accessible', __( 'The requested ticket is not accessible', 'event-tickets' ), [ 'status' => 403 ] );
	}


	/**
	 * Get Data for Testing
	 *
	 * @since 5.9.1
	 *
	 * @return array
	 */
	public function get_data() {

		$data = [
			'some' => 'data',
		];

		return $data;
	}


	/**
	 * Converts an array of arguments suitable for the WP REST API to the Swagger format.
	 *
	 * @since 5.9.1
	 *
	 * @param array $args
	 * @param array $defaults
	 *
	 * @return array The converted arguments.
	 */
	public function swaggerize_args( array $args = [], array $defaults = [] ) {
		if ( empty( $args ) ) {
			return $args;
		}

		$no_description = __( 'No description provided', 'event-tickets-plus' );
		$defaults       = array_merge(
			[
				'in'          => 'body',
				'type'        => 'string',
				'description' => $no_description,
				'required'    => false,
				'default'     => '',
				'items'       => [
					'type' => 'integer',
				],
			],
			$defaults
		);


		$swaggerized = [];
		foreach ( $args as $name => $info ) {
			if ( isset( $info['swagger_type'] ) ) {
				$type = $info['swagger_type'];
			} else {
				$type = isset( $info['type'] ) ? $info['type'] : false;
			}

			$type = $this->convert_type( $type );

			$read = [
				'name'             => $name,
				'in'               => isset( $info['in'] ) ? $info['in'] : false,
				'collectionFormat' => isset( $info['collectionFormat'] ) ? $info['collectionFormat'] : false,
				'description'      => isset( $info['description'] ) ? $info['description'] : false,
				'type'             => $type,
				'items'            => isset( $info['items'] ) ? $info['items'] : false,
				'required'         => isset( $info['required'] ) ? $info['required'] : false,
				'default'          => isset( $info['default'] ) ? $info['default'] : false,
			];

			if ( isset( $info['swagger_type'] ) ) {
				$read['type'] = $info['swagger_type'];
			}

			if ( $read['type'] !== 'array' ) {
				unset( $defaults['items'] );
			}

			$swaggerized[] = array_merge( $defaults, array_filter( $read ) );
		}

		return $swaggerized;
	}

	/**
	 * Converts REST format type argument to the correspondant Swagger.io definition.
	 *
	 * @since 5.9.1
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	protected function convert_type( $type ) {
		$rest_to_swagger_type_map = [
			'int'  => 'integer',
			'bool' => 'boolean',
		];

		return Tribe__Utils__Array::get( $rest_to_swagger_type_map, $type, $type );
	}
}
