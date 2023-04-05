# Flexible Tickets for Event Tickets - WP CLI support

The feature integrates with existing [WP CLI][1] commands to provide support for the new custom tables.

The Controller in charge of the integration is the `TEC\Tickets\Flexible_Tickets\Controller` one; the controller
will only be active in the context of a WP CLI request, where the `WP_CLI` constant is defined and truthy.

## Commands integrations

* `wp site empty` - will empty the custom tables as well.

[1]: https://wp-cli.org/