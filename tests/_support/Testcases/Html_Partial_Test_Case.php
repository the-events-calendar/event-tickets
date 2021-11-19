<?php
/**
 * The base test case that should be used to test HTML partials.
 *
 * @package Tribe\Tickets\Test\Testcases;
 */

namespace Tribe\Tickets\Test\Testcases;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Test\Products\WPBrowser\Views\Legacy\PartialTestCase;

/**
 * Class Html_Partial_Test_Case
 *
 * @package Tribe\Tickets\Test\Testcases
 */
class Html_Partial_Test_Case extends PartialTestCase {
	use SnapshotAssertions;
	use With_Post_Remapping;
}
