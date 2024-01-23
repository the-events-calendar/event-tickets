# Flexible Tickets for Event Tickets - Controllers

The concept of "Controller" is borrowed from the MVC pattern and is used to separate the logic of the plugin from the
rendering of the HTML output.

Other PHP frameworks use the term "Controller" to refer to the class that handles the request and renders the HTML
output (e.g. Symfony or Laravel).

While routing might be something a plugin could do, most Controllers, in the context of a WordPress plugin, will handle
actions and filters hooking on them to customize the site functionality.

## Controllers in the context of the Flexible Tickets plugin

Controllers are best thought as "Service Providers with business logic".

All the feature Controllers are located in the `src/Tickets/Flexible_Tickets/Controllers` directory.

All Controllers :

* extend the `TEC\Common\Provider\Controller` class.
* are registered **only** in the `TEC\Tickets\Flexible_Tickets\Provider` class; the whole feature should
  be completely handled in the main provider.
* MUST implement the `do_register` and `unregister` methods.
* CAN override the `is_active` method to conditionally register the Controller if, and when, required.
* MUST have near 100% **direct** test coverage in the `ft_integration` suite; see the [Testing](testing.md) document for
  more details.
* CAN have coverage, if applicable, in the `ft_smoketest` suite; again: see the [Testing](testing.md) document for more
  details.

While a Controller is the right place to place the business logic, the common sense of a developer should be used to
decide where to place the logic: if a Controller size is too large, the Controller should be split into smaller,
specialized classes.
That can be done transparently of testing, as the Controller will be tested as a whole.