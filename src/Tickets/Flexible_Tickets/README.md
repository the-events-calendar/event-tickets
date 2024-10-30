# Flexible Ticktets for Event Tickets - technical documentation

This is the landing page for the project technical documentation.
As this file grows, it will be split into multiple files and linked from here.

## Table of contents

* [Activating and deactivating the feature](#activating-and-deactivating-the-feature)
* [Custom tables structure](_docs/custom-tables-structure.md)
* [Capacity concept and modeling](_docs/capacity.md)
* [Testing](_docs/testing.md)
* [Controllers](_docs/controllers.md)
* [WP CLI support](_docs/wp-cli.md)
* [Templating](_docs/html-templating.md)
* [Filters and actions](_docs/filters-and-actions.md)
* New Ticket types
	* [Series Passes](_docs/series-passes.md)

## Activating and deactivating the feature

The whole feature is active by default and can be completely deactivated by setting
the `TEC_FLEXIBLE_TICKETS_DISABLED` constant to a falsy value (e.g. `0` or `false`) in the
site `wp-config.php` file.

```php
define( 'TEC_FLEXIBLE_TICKETS_DISABLED', false );
```

If the constant is not set, or its value is truthy, the feature will be active.