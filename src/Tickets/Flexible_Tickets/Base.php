<?php
/**
 * A Controller to register basic functionalities common to all the ticket types handled by the feature.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Admin\Editor_Data;
use TEC\Tickets\Flexible_Tickets\Templates\Admin_Views;
use Tribe\Tickets\Editor\Warnings;

/**
 * Class Base.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Base extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Repositories\Ticket_Groups::class, Repositories\Ticket_Groups::class );
		$this->container->singleton( Repositories\Posts_And_Ticket_Groups::class, Repositories\Posts_And_Ticket_Groups::class );

		$series_post_type = Series_Post_Type::POSTTYPE;
		add_filter( "tec_tickets_enabled_ticket_forms_{$series_post_type}", [
			$this,
			'enable_ticket_forms_for_series'
		] );

		// Remove the warnings about Recurring Events and Tickets not being supported.
		$editor_warnings = tribe( 'tickets.editor.warnings' );
		remove_action( 'tribe_events_tickets_new_ticket_warnings', [
			$editor_warnings,
			'show_recurring_event_warning_message'
		] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$series_post_type = Series_Post_Type::POSTTYPE;
		remove_filter( "tec_tickets_enabled_ticket_forms_{$series_post_type}", [
			$this,
			'enable_ticket_forms_for_series'
		] );

		// Remove the warnings about Recurring Events and Tickets not being supported.
		$editor_warnings = tribe( 'tickets.editor.warnings' );
		add_action( 'tribe_events_tickets_new_ticket_warnings', [
			$editor_warnings,
			'show_recurring_event_warning_message'
		] );
	}

	/**
	 * Disables default ticket types for Series.
	 *
	 * @since TBD
	 *
	 * @param array<string,bool> $enabled The default enabled forms, a map from ticket types to their enabled status.
	 *
	 * @return array<string,bool> The updated enabled forms.
	 */
	public function enable_ticket_forms_for_series( array $enabled ): array {
		$enabled['default']                    = false;
		$enabled['rsvp']                       = false;
		$enabled[ Series_Passes::TICKET_TYPE ] = true;

		return $enabled;
	}
}