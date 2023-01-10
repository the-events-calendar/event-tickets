<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use Tribe__Utils__Array as Arr;

/**
 * Abstract Requests Contract.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
abstract class Abstract_Requests implements Requests_Interface {

	/**
	 * @inheritDoc
	 */
	public static function post( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ) {
		return static::request( 'POST', $endpoint, $query_args, $request_arguments, $raw );
	}

	/**
	 * @inheritDoc
	 */
	public static function get( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ) {
		return static::request( 'GET', $endpoint, $query_args, $request_arguments, $raw );
	}

	/**
	 * @inheritDoc
	 */
	public static function patch( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ) {
		return static::request( 'PATCH', $endpoint, $query_args, $request_arguments, $raw );
	}

	/**
	 * @inheritDoc
	 */
	public static function delete( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ) {
		return static::request( 'DELETE', $endpoint, $query_args, $request_arguments, $raw );
	}

	/**
	 * @inheritDoc
	 */
	public static function request( $method, $url, array $query_args = [], array $request_arguments = [], $raw = false, $retries = 0 ) {
		$method = strtoupper( $method );

		// If the endpoint passed is a full URL don't try to append anything.
		$url = 0 !== strpos( $url, 'https://' )
			? static::get_api_url( $url, $query_args )
			: add_query_arg( $query_args, $url );

		$default_arguments = [
			'headers' => [
				'Authorization' => 'Bearer ' . tribe( static::$merchant )->get_client_secret(),
			],
		];

		// By default, it's important that we have a body set for any method that is not the GET method.
		if ( 'GET' !== $method ) {
			$default_arguments['body'] = [];
		}

		foreach ( $default_arguments as $key => $default_argument ) {
			$request_arguments[ $key ] = array_merge( $default_argument, Arr::get( $request_arguments, $key, [] ) );
		}

		if ( 'GET' !== $method ) {
			$content_type = Arr::get( $request_arguments, [ 'headers', 'Content-Type' ] );
			if ( empty( $content_type ) ) {
				$content_type = Arr::get( $request_arguments, [ 'headers', 'content-type' ] );
			}

			// For all other methods we try to make the body into the correct type.
			if (
				! empty( $request_arguments['body'] )
				&& 'application/json' === strtolower( $content_type )
			) {
				$request_arguments['body'] = wp_json_encode( $request_arguments[ $key ] );
			}
		}

		if ( 'GET' === $method ) {
			$response = wp_remote_get( $url, $request_arguments );
		} elseif ( 'POST' === $method ) {
			$response = wp_remote_post( $url, $request_arguments );
		} else {
			$request_arguments['method'] = $method;
			$response                    = wp_remote_request( $url, $request_arguments );
		}

		$response = static::process_response( $response );

		if ( is_wp_error( $response ) ) {
			return static::prepare_errors_to_display( $response );
		}

		// When raw is true means we dont do any logic.
		if ( true === $raw ) {
			return $response;
		}

		/**
		 * @todo Determine if Stripe might need a retry pattern like PayPal.
		 */

		/**
		 * @todo we need to log and be more verbose about the responses. Specially around failed JSON strings.
		 */
		$response_body = wp_remote_retrieve_body( $response );
		$response_body = @json_decode( $response_body, true );
		if ( empty( $response_body ) ) {
			return $response;
		}

		if ( ! is_array( $response_body ) ) {
			tribe( 'logger' )->log_error( sprintf( '[%s] Unexpected Gateway %s response', $url, $method ), 'tickets-commerce' );

			return new \WP_Error( 'tec-tickets-commerce-gateway-client-unexpected', null, [
				'method'            => $method,
				'url'               => $url,
				'query_args'        => $query_args,
				'request_arguments' => $request_arguments,
				'response'          => $response,
				'gateway'           => static::$gateway::$key,
			] );
		}

		return $response_body;
	}

	/**
	 * @inheritDoc
	 */
	public static function process_response( $response ) {

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( ! empty( $response['response']['code'] ) && 200 !== $response['response']['code'] && ! empty( $response['body'] ) ) {
			$body = json_decode( $response['body'] );

			if ( ! empty( $body->error ) ) {
				return new \WP_Error( $response['response']['code'], $body->error->message, $body->error );
			}
		}

		return $response;
	}

	/**
	 * @inheritDoc
	 */
	public static function prepare_errors_to_display( \WP_Error $errors ) {
		$error = $errors->get_error_data();

		if ( ! $error ) {
			$return[] = [ $errors->get_error_code(), $errors->get_error_message() ];
		} elseif ( isset( $error->type ) && isset( $error->message ) ) {
			$return[] = [ $error->type, $error->message ];
		} elseif ( isset( $error->code ) && isset( $error->message ) ) {
			$return[] = [ $error->code, $error->message ];
		} else {
			$return[] = $error;
		}

		return [ 'errors' => $return ];
	}
}