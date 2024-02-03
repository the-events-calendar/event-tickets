<?php
/**
 * Fieldset for Settings related fields in the Site Health page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Fieldset
 */

namespace TEC\Tickets\Site_Health\Fieldset;

use TEC\Tickets\Site_Health\Contracts\Fieldset_Abstract;
use Tribe__Utils__Array as Arr;

/**
 * Class Settings
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Fieldset
 */
class Settings extends Fieldset_Abstract {

	/**
	 * @inheritdoc
	 */
	protected float $priority = 10.0;

	/**
	 * @inheritdoc
	 */
	protected function get_fields(): array {
		return [
			[
				'id'    => 'ticket_enabled_post_types',
				'label' => esc_html__( 'Ticket-enabled post types', 'event-tickets' ),
				'value' => [ $this, 'get_post_types_enabled' ],
			],
			[
				'id'    => 'tickets_login_required_for_tickets',
				'label' => esc_html__( 'Login required for Tickets', 'event-tickets' ),
				'value' => [ $this, 'get_is_login_required_for_tickets' ],
			],
			[
				'id'    => 'tickets_login_required_for_rsvp',
				'label' => esc_html__( 'Login required for RSVP', 'event-tickets' ),
				'value' => [ $this, 'get_is_login_required_for_rsvp' ],
			],
		];
	}

	/**
	 * Get the post types enabled for tickets.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_post_types_enabled(): string {
		$value = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
		$value = array_filter( $value );

		return Arr::to_list( $value, ', ' );
	}

	/**
	 * Get if login is required for tickets.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_is_login_required_for_tickets(): string {
		$value = (array) tribe_get_option( 'ticket-authentication-requirements', [] );
		$value = array_filter( $value );

		return in_array( 'event-tickets_all', $value, true ) ? static::YES : static::NO;
	}

	/**
	 * Get if login is required for RSVP.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_is_login_required_for_rsvp(): string {
		$value = (array) tribe_get_option( 'ticket-authentication-requirements', [] );
		$value = array_filter( $value );

		return in_array( 'event-tickets_rsvp', $value, true ) ? static::YES : static::NO;
	}
}