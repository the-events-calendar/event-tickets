<?php

namespace Tribe\Tickets\Test\Traits;

trait CapacityMatrix {

	/**
	 * Get ticket matrix mode variations.
	 *
	 * @return array List of mode variations.
	 */
	public function _get_ticket_mode_matrix() {
		return [
			[
				// Shared capacity (with limit for this ticket).
				'ticket' => [
					'mode'           => 'capped',
					'capacity'       => 10,
					'event_capacity' => 15,
				],
			],
			[
				// Shared capacity (with limit for this ticket).
				'ticket' => [
					'mode'           => 'capped',
					'capacity'       => 11,
					'event_capacity' => 15,
				],
			],
			[
				// Shared capacity (no set optional limit for this ticket).
				'ticket' => [
					'mode'           => 'capped',
					'capacity'       => '',
					'event_capacity' => 15,
				],
			],
			[
				// Limited capacity for this ticket only.
				'ticket' => [
					'mode'     => 'own',
					'capacity' => 12,
				],
			],
			[
				// Limited capacity for this ticket only.
				'ticket' => [
					'mode'     => 'own',
					'capacity' => 13,
				],
			],
			[
				// Unlimited capacity.
				'ticket' => [
					'mode'     => '',
					'capacity' => '',
				],
			],
		];
	}

	/**
	 * Get ticket matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_matrix() {
		$providers   = array_keys( $this->get_providers() );
		$mode_matrix = $this->_get_ticket_mode_matrix();

		$matrix = [];

		foreach ( $providers as $provider ) {
			foreach ( $mode_matrix as $mode ) {
				$matrix[] = array_merge( $mode, [
					'provider' => $provider,
				] );
			}
		}

		return $matrix;
	}

	/**
	 * Get ticket update matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_update_matrix() {
		$ticket_matrix = $this->_get_ticket_matrix();
		$mode_matrix   = $this->_get_ticket_mode_matrix();

		$matrix = [];

		foreach ( $ticket_matrix as $ticket ) {
			foreach ( $mode_matrix as $mode ) {
				$new_ticket = $ticket;

				unset( $new_ticket['ticket'] );

				$new_ticket = array_merge( $mode, $new_ticket );

				if ( $ticket === $new_ticket ) {
					continue;
				}

				$matrix[] = [
					'from' => $ticket,
					'to'   => $new_ticket,
				];
			}
		}

		return $matrix;
	}

	/**
	 * Get RSVP matrix mode variations.
	 *
	 * @return array List of mode variations.
	 */
	public function _get_rsvp_mode_matrix() {
		return [
			[
				// Limited capacity for this ticket only.
				'ticket' => [
					'capacity' => 12,
				],
			],
			[
				// Limited capacity for this ticket only.
				'ticket' => [
					'capacity' => 13,
				],
			],
			[
				// Unlimited capacity.
				'ticket' => [
					'capacity' => '',
				],
			],
		];
	}

	/**
	 * Get RSVP matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_rsvp_matrix() {
		$providers   = array_keys( $this->get_rsvp_providers() );
		$mode_matrix = $this->_get_rsvp_mode_matrix();

		$matrix = [];

		foreach ( $providers as $provider ) {
			foreach ( $mode_matrix as $mode ) {
				$matrix[] = array_merge( $mode, [
					'provider' => $provider,
				] );
			}
		}

		return $matrix;
	}

	/**
	 * Get RSVP update matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_rsvp_update_matrix() {
		$rsvp_matrix = $this->_get_rsvp_matrix();
		$mode_matrix = $this->_get_rsvp_mode_matrix();

		$matrix = [];

		foreach ( $rsvp_matrix as $rsvp ) {
			foreach ( $mode_matrix as $mode ) {
				$new_rsvp = $rsvp;

				unset( $new_rsvp['ticket'] );

				$new_rsvp = array_merge( $mode, $new_rsvp );

				if ( $rsvp === $new_rsvp ) {
					continue;
				}

				$matrix[] = [
					'from' => $rsvp,
					'to'   => $new_rsvp,
				];
			}
		}

		return $matrix;
	}

	/**
	 * Get matrix as arguments when using dataProvider with non-cest tests.
	 *
	 * @param array $matrix Original matrix data.
	 *
	 * @return array Matrix data returning with each within their own array.
	 */
	protected function _get_matrix_as_args( $matrix ) {
		$matrix_as_args = [];

		foreach ( $matrix as $matrix_item ) {
			// Setup as an item in an array so it is all passed as first argument by dataProvider.
			$matrix_as_args[] = [
				$matrix_item,
			];
		}

		return $matrix_as_args;
	}

	/**
	 * Get ticket matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_matrix_as_args() {
		return $this->_get_matrix_as_args( $this->_get_ticket_matrix() );
	}

	/**
	 * Get ticket update matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_ticket_update_matrix_as_args() {
		return $this->_get_matrix_as_args( $this->_get_ticket_update_matrix() );
	}

	/**
	 * Get RSVP matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_rsvp_matrix_as_args() {
		return $this->_get_matrix_as_args( $this->_get_rsvp_matrix() );
	}

	/**
	 * Get RSVP update matrix variations.
	 *
	 * @return array List of variations.
	 */
	public function _get_rsvp_update_matrix_as_args() {
		return $this->_get_matrix_as_args( $this->_get_rsvp_update_matrix() );
	}

	/**
	 * Prepare HTML so it can be used in snapshot testing.
	 *
	 * @param string $html HTML to prepare.
	 *
	 * @return string Prepared HTML.
	 */
	protected function prepare_html( $html ) {
		$html = preg_replace( '/check=\w+/', 'check=nonceABC', $html );

		return $html;
	}

	/**
	 * Get list of providers for test.
	 *
	 * @return array List of providers.
	 */
	protected function get_providers() {
		return [];
	}

	/**
	 * Get list of RSVP providers for test.
	 *
	 * @return array List of RSVP providers.
	 */
	protected function get_rsvp_providers() {
		return [
			'Tribe__Tickets__RSVP' => 'rsvp',
		];
	}

	/**
	 * Get matching provider ID or class.
	 *
	 * @param string $provider Provider class or ID.
	 *
	 * @return string The matching provider ID or class.
	 */
	protected function get_provider( $provider ) {
		$providers      = $this->get_providers();
		$rsvp_providers = $this->get_rsvp_providers();

		if ( isset( $providers[ $provider ] ) ) {
			return $providers[ $provider ];
		} elseif ( isset( $rsvp_providers[ $provider ] ) ) {
			return $rsvp_providers[ $provider ];
		}

		$found = array_search( $provider, $providers, true );

		if ( ! $found ) {
			$found = array_search( $provider, $rsvp_providers, true );
		}

		return $found;
	}

	/**
	 * Get capacity amount from arguments.
	 *
	 * @param array $args List of arguments.
	 *
	 * @return int Capacity amount.
	 */
	protected function get_capacity( array $args ) {
		$capacity = isset( $args['tribe-ticket']['capacity'] ) ? $args['tribe-ticket']['capacity'] : $args['ticket']['capacity'];

		if ( '' === $capacity ) {
			if ( isset( $args['ticket']['event_capacity'] ) ) {
				return $args['ticket']['event_capacity'];
			} elseif ( isset( $args['tribe-ticket']['event_capacity'] ) ) {
				return $args['tribe-ticket']['event_capacity'];
			}

			return - 1;
		}

		return $capacity;
	}

}
