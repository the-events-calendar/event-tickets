## Flexible Tickets for Event Tickets - Testing

Testing will happen using [`slic`][1]; refer to the project documentation to setup.
The feature dedicated suites are set up alongside the existing Event Tickets' ones.
The project full testing coverage is required, but tests should be written to find a balance between coverage and speed.

### Integration tests

The suite `ft_integration` is dedicated to integration tests.
The purpose of the suite is to provide a fast, reliable and comprehensive test coverage of the feature's integration
with the rest of the plugin and WordPress core.
The suite loads Event Tickets, The Events Calendar and The Events Calendar PRO plugins.
Since the feature requires The Events Calendar Custom Table v1 (CT1) feature to
be active, that will be activated by default.

Controllers MUST be covered by direct, dedicated tests in this suite. The specialized classes used by Controllers CAN be
covered by tests in this suite, if required or more efficient.
Due to the inherent "global" nature of Controllers, they should be tested using test case extending
the `TEC\Common\Tests\Provider\Controller_Test_Case` class; the test case will set up a "sandbox" container
for the Controller under test to act that will diverge from the global container to avoid testing Controllers affecting
the global state and, thus, the following tests.

Tests should be named with a strict relation to the class they are testing; e.g. the tests for
the `src/Tickets/Flexible_Tickets/Some_Controller.php` class should be named `src/Tickets/Flexible_Tickets/Some_Controller_Test.php`.

The test case should be in the same namespace as the class being tested,
e.g. `src/Tickets/Flexible_Tickets/Some_Controller_Test.php` should define the `Some_Controller_Test` class in
the `Tribe\Tickets\Tickets\Flexible_Tickets` namespace.

### Smoke tests

The suite `ft_smoketest` is dedicated to "smoke tests".
These are fast tests using `curl` to fetch the content of a page and check that it contains the expected content and has
the expected status code.

If a Controller has a role in rendering some HTML somewhere, then it MUST be covered by a smoke test in this suite.

The suite uses a must-use plugin in (`tests/_data/mu-plugins/ft-smoketest.php`) to speed up the suite execution by
cutting out all the site external connections.

Test should be name in a way that makes it clear, by simply reading the name, what behaviour, component or controller
the test is targeting; use directories to group tests by component or controller as required.

#### Printing debugging information to the page during tests

The mu-plugin applies the `tec_debug_data` filter to print, on the `wp_footer` and `admin_footer` actions, debug
information that can be accessed in the context of a smoketest like this:

``php
$I->amOnPage( '/some-page' );
$debug_data = json_decode( $I->grabTextFrom( '#tec-debug-data' ) );
$I->assertEquals( 'foo-bar', $debug_data->some_data );
``

Hook on the `tec_debug_data` filter to add your own data to the debug information you might need on a page.

The plugin will also hook on the `tribe_log` action to collect and print on the page the logged messages; the messages
are stored in the `logs` property of the debug data and are stored by level (debug, error, warning and so on).

### CI

The project requires TEC and ECP to work correctly and is depending on the Custom Tables v1 feature being active in both
projects.
For this reason the project has a dedicated GitHub workflow that will clone and initialize TEC and ECP,
see `.github/workflows/tests-php-ft.yml`.

[1]: https://github.com/stellarwp/slic
