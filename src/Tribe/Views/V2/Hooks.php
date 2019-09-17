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
 * @since TBD
 *
 * @package Tribe\Events\Tickets\Views\V2
 */

namespace Tribe\Events\Tickets\Views\V2;

use Tribe\Events\Tickets\Views\V2\Partials\List_Tickets;

/**
 * Class Hooks.
 *
 * @since TBD
 *
 * @package Tribe\Events\Tickets\Views\V2
 */
class Hooks extends \tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Filters the list of folders TEC will look up to find templates to add the ones defined by Tickets.
	 *
	 * @since TBD
	 *
	 * @param array $folders The current list of folders that will be searched template files.
	 *
	 * @return array The filtered list of folders that will be searched for the templates.
	 */
	public function filter_template_path_list( array $folders = [] ) {
		$folders[] = [
			'id'       => 'event-tickets',
			'priority' => 17,
			'path'     => \Tribe__Tickets__Main::instance()->plugin_path . 'src/views/v2',
		];

		return $folders;
	}

	/**
	 * Adds the actions required by each Tickets Views v2 component.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		// silence is golden
	}

	/**
	 * Adds the filters required by each Tickets Views v2 component.
	 *
	 * @since TBD
	 */
	protected function add_filters() {
		add_filter( 'tribe_template_path_list', [ $this, 'filter_template_path_list' ] );
	}
}
