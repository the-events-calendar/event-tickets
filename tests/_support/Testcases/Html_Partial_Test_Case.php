<?php
/**
 * The base test case that should be used to test HTML partials.
 *
 * @package Tribe\Tickets\Test\Testcases;
 */

namespace Tribe\Tickets\Test\Testcases;

use Tribe\Tests\Traits\With_WP_Version_Tolerant_Snapshots;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Test\Products\WPBrowser\Views\Legacy\PartialTestCase;

/**
 * Class Html_Partial_Test_Case
 *
 * @package Tribe\Events\Virtual\Tests\Test_Cases
 */
class Html_Partial_Test_Case extends PartialTestCase {
	use With_WP_Version_Tolerant_Snapshots;
	use With_Post_Remapping;
}
