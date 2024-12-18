<?php


class Tribe__Tickets__REST__V1__Validator__Base
	extends Tribe__Tickets__Validator__Base
	implements Tribe__Tickets__REST__V1__Validator__Interface {

		/**
		 * Remove all the ticket data from ticket in rest response by authorized user.
		 *
		 * @since 5.17.0.1
		 *
		 * @param array $ticket The ticket data.
		 *
		 * @return array The ticket data with password protected fields removed.
		 */
		public function remove_ticket_data( array $ticket ): array {
			foreach ( $ticket as $key => $val ) {
				if ( is_array( $val ) || is_object( $val ) ) {
					$ticket[ $key ] = $this->remove_ticket_data( (array) $val );
					continue;
				}

				if ( is_numeric( $val ) ) {
					$ticket[ $key ] = 0;
					continue;
				}

				if ( is_bool( $val ) ) {
					$ticket[ $key ] = null;
					continue;
				}

				$ticket[ $key ] = __( 'No Access', 'event-tickets' );;
			}

			return $ticket;
		}

		/**
		 * Check if the ticket should be seen by the current request.
		 *
		 * @since 5.18.0
		 *
		 * @param int             $parent_id The parent's ID.
		 * @param WP_REST_Request $request   The request object.
		 *
		 * @return bool Whether the ticket should be seen by the current user.
		 */
		public function should_see_ticket( int $parent_id, WP_REST_Request $request ): bool {
			if ( empty( $parent_id ) ) {
				$parent_id = 0;
			}

			$parent = get_post( $parent_id );

			if ( ! ( $parent instanceof WP_Post && $parent->ID ) ) {
				// Possibly parent does not exist anymore. Unauthorized should see nothing.
				return false;
			}

			if ( ! 'publish' === $parent->post_status ) {
				// Unauthorized users should not see tickets from not published events.
				return false;
			}

			try {
				$tec_validator = tribe( 'tec.rest-v1.validator' );

				if ( ! method_exists( $tec_validator, 'can_access_password_content' ) ) {
					// The validator is available but outdated. Better to hide data than assume its good.
					throw new Exception( 'Method not found' );
				}

				if ( post_password_required( $parent ) && ! $tec_validator->can_access_password_content( $parent, $request ) ) {
					// Unauthorized users should not see tickets from password protected events.
					return false;
				}
			} catch ( Exception $e ) {
				// If the validator is not available, we can't check the password. Fail silently hiding data.
				return false;
			}

			return true;
		}
}
