<?php
/**
 * The REST API handler for the Onboarding Wizard.
 * Cleverly named...API.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding
 */

namespace TEC\Tickets\Admin\Onboarding;

use TEC\Common\Admin\Onboarding\Abstract_API;

/**
 * Class API
 *
 * @todo Move shared pieces to common.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding
 */
class API extends Abstract_API {

	/**
	 * The action for this nonce.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NONCE_ACTION = '_tec_tickets_wizard';

	/**
	 * Rest Endpoint namespace
	 *
	 * @since TBD
	 *
	 * @var  string
	 */
	protected const ROOT_NAMESPACE = 'tec/tickets/onboarding';
}
