# Flexible Tickets for Event Tickets - APIs

The feature introduces the use of custom tables to store some entities modeled, until now, at the `postmeta` level.

This document describes the APIs that have been introduced to interact with the custom tables and the existing ones that
have been modified to work transparently with the new tables.

## Existing APIs

* `tribe_get_event_capacity( int|WP_Post $post ): ?int` - returns the capacity of the event, if any. From the point of
  view of code that uses this function, nothing changes when the post is an Event or any other kind of post. If the post
  is a Series, the function result will be read from the custom tables.

## New APIs

@todo about Models, Repositories, and the Custom Tables API.
