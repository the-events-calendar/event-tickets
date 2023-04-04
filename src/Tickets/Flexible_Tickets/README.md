# Flexible Ticktets for Event Tickets - technical documentation

This is the landing page for the project technical documentation.
As this file grows, it will be split into multiple files and linked from here.

## Table of contents

* [Activating and deactivating the feature](#activating-and-deactivating-the-feature)
* [Custom tables structure](_docs/custom-tables-structure.md)
* [Testing](_docs/testing.md)

## Activating and deactivating the feature

The whole feature is active by default and can be completely deactivated by setting
the `TEC_FLEXIBLE_TICKETS_DISABLEDEC_TICKETS_COMMERCE` constant to a falsy value (e.g. `0` or `false`) in the
site `wp-config.php` file.

```php
define( 'TEC_FLEXIBLE_TICKETS_DISABLEDEC_TICKETS_COMMERCE', false );
```

If the constant is not set, or its value is truthy, the feature will be active.