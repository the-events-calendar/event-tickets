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
use TEC\Tickets\Site_Health\Subsections\Features\Tickets_Commerce_Subsection;
use TEC\Tickets\Site_Health\Subsections\Plugins\Plugin_Data_Subsection;

/**
  * Class Provider
  *
  * @since   5.6.0.1

  * @package TEC\Tickets\Site_Health
  */
class Provider extends Service_Provider {

	/**
	 * Internal placeholder to pass around the section slug.
	 *
	 * @since 5.6.0.1
	 *
	 * @var string
	 */
	protected $slug;

	/**
	 * Register our service provider.
	 *
	 * @since 5.6.0.1
	 *
	 * @return void
	 */
	public function register() {
		// Plugin subsection.
		$this->container->singleton( Plugin_Data_Subsection::class );

		// Feature subsection.
		$this->container->singleton( Tickets_Commerce_Subsection::class );

		$this->slug = Info_Section::get_slug();
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Add the action hooks.
	 *
	 * @since 5.6.0.1
	 */
	public function add_actions() {
		// no op.
	}

	/**
	 * Add the filter hooks.
	 *
	 * @since 5.6.0.1
	 */
	public function add_filters() {
		add_filter( 'tec_debug_info_sections', [ $this, 'filter_include_sections' ] );
	}

	/**
	 * This builds the Info_Section object and adds it to the Site Health screen.
	 *
	 * @since 5.6.0.1
	 *
	 * @param array $sections The array of sections to be displayed.
	 */
	public function filter_include_sections( $sections ) {
		$sections[ Info_Section::get_slug() ] = $this->container->make( Info_Section::class );

		return $sections;
	}
}
