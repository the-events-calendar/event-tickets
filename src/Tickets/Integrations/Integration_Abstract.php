<?php

namespace TEC\Tickets\Integrations;

use TEC\Common\Integrations\Integration_Abstract as Common_Integration_Abstract;

/**
 * Class Integration_Abstract
 *
 * @since 5.6.3
 *
 * @link    https://docs.theeventscalendar.com/apis/integrations/including-new-integrations/
 *
 * @package TEC\Tickets\Integrations
 */
abstract class Integration_Abstract extends Common_Integration_Abstract {

	/**
	 * @inheritDoc
	 */
	public static function get_parent(): string {
		return 'tickets';
	}

	/**
	 * Filters whether the integration should load.
	 *
	 * @since 5.6.3
	 *
	 * @param bool $value Whether the integration should load.
	 *
	 * @return bool
	 */
	protected function filter_should_load( bool $value ): bool {
		$value = parent::filter_should_load( $value );

		$slug   = static::get_slug();
		$type   = static::get_type();
		$parent = static::get_parent();


		return (bool)$value;
	}
}
