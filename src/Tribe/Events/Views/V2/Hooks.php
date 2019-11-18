<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( Tribe\Events\Tickets\Views\V2\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets.views.v2.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( Tribe\Events\Tickets\Views\V2\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets.views.v2.hooks' ), 'some_method' ] );
 *
 * @since 4.10.9
 *
 * @package Tribe\Events\Tickets\Views\V2
 */

namespace Tribe\Tickets\Events\Views\V2;

use Tribe\Events\Tickets\Views\V2\Models\Tickets;
use Tribe__Tickets__Main as Plugin;

/**
 * Class Hooks.
 *
 * @since 4.10.9
 *
 * @package Tribe\Events\Tickets\Views\V2
 */
class Hooks extends \tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 4.10.9
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Filters the list of folders TEC will look up to find templates to add the ones defined by Tickets.
	 *
	 * @since 4.10.9
	 *
	 * @param array $folders The current list of folders that will be searched template files.
	 *
	 * @return array The filtered list of folders that will be searched for the templates.
	 */
	public function filter_template_path_list( array $folders = [] ) {
		$folders[] = [
			'id'       => 'event-tickets',
			'priority' => 17,
			'path'     => Plugin::instance()->plugin_path . 'src/views/v2',
		];

		return $folders;
	}

	/**
	 * Add tickets data to the event object.
	 *
	 * @since 4.10.9
	 *
	 * @param array    $props An associative array of all the properties that will be set on the "decorated" post
	 *                        object.
	 * @param \WP_Post $post  The post object handled by the class.
	 *
	 * @return array The model properties. This value might be cached.
	 */
	public function add_tickets_data( $props, $event ) {
		$props['tickets'] = new Tickets( $event->ID );

		return $props;
	}

	/**
	 * Adds the actions required by each Tickets Views v2 component.
	 *
	 * @since 4.10.9
	 */
	protected function add_actions() {
		// silence is golden
	}

	/**
	 * Adds the filters required by each Tickets Views v2 component.
	 *
	 * @since 4.10.9
	 */
	protected function add_filters() {
		add_filter( 'tribe_template_path_list', [ $this, 'filter_template_path_list' ] );
		add_filter( 'tribe_post_type_events_properties', [ $this, 'add_tickets_data' ], 20, 2 );
	}
}
