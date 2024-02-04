<?php
/**
 * Service Provider for interfacing with tec-common Site Health.
 *
 * @since   5.6.0.1
 *
 * @package TEC\Tickets\Site_Health
 */

namespace TEC\Tickets\Site_Health;

use TEC\Common\Contracts\Service_Provider;
use TEC\Tickets\Site_Health\Fieldset\Commerce;
use TEC\Tickets\Site_Health\Fieldset\Settings;

/**
  * Class Provider
  *
  * @since   5.6.0.1

  * @package TEC\Tickets\Site_Health
  */
class Provider extends Service_Provider {

	public function register() {
		$this->container->bind( Settings::class );
		$this->container->bind( Commerce::class );

		$this->add_filters();
	}

	/**
	 * Include the filter
	 *
	 * @since 5.6.0
	 */
	public function add_filters(): void {
		add_filter( 'tec_debug_info_sections', [ $this, 'filter_include_sections' ] );
	}

	/**
	 * Filter the sections to include the Tickets section.
	 *
	 * @since TBD
	 *
	 * @throws \TEC\Common\lucatume\DI52\ContainerException
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	public function filter_include_sections( $sections ): array {
		// Reset to ensure array.
		if ( ! is_array( $sections ) ) {
			$sections = [];
		}

		$sections[ Info_Section::get_slug() ] = $this->container->make( Info_Section::class );

		return $sections;
	}
}
