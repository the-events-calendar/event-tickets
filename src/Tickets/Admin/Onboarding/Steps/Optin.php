<?php
/**
 * Optin step for the onboarding wizard.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding\Steps;

use TEC\Common\Admin\Onboarding\Steps\Abstract_Step;
use WP_REST_Response;

/**
 * Optin step for the onboarding wizard.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Optin extends Abstract_Step {

	public static function process( $response, $request ): WP_REST_Response {
		return new WP_REST_Response( [ 'success' => true ] );
	}

}
