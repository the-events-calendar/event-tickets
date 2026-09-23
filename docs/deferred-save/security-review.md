# Deferred ticket save: security review notes

Deferred ticket save is a new write path for tickets. Instead of the classic editor's AJAX calls and the block editor's REST calls per ticket, both editors send one `tec_tickets` payload with the post save, and one server handler applies it. This note is for the SVUL reviewers: where a request enters, what refuses it and where, and which test shows each refusal. Linear: SOFT-4822 and its sub-issues SOFT-4823 to SOFT-4828. Plans: the `soft-4823` to `soft-4828` changes in `the-events-calendar/plans`.

## Where a payload enters

| Entry point | Request | Reads | Class |
|---|---|---|---|
| Classic editor | `POST wp-admin/post.php` (the post form) | `$_POST['tec_tickets']`, `$_POST['tec_tickets_nonce']`, `$_POST['post_ID']` | `src/Tickets/Deferred_Save/Classic_Save.php`, on `save_post` at priority 20 |
| Block editor | `POST /wp/v2/{type}/{id}` and `POST /wp/v2/{type}` (the post save) | `$request['tec_tickets']` | `src/Tickets/Deferred_Save/Block_Save.php`, on `rest_after_insert_{type}` at priority 200 |

Both call `Commit::run( $raw, $post_id )` (`src/Tickets/Deferred_Save/Commit.php`). Nothing else reads the payload. No REST endpoint was added.

## The payload

```
tec_tickets = [
    update => [ ticket ID => data ],
    create => [ data, data ],
    delete => [ ticket ID ],
    move   => [ ticket ID => destination post ID ],
]
```

`data` is the array Event Tickets' `ticket_add()` accepts today. It is passed through, with two exceptions applied by the parser (`Payload.php`): `ticket_id` inside `data` is overwritten by the entry key on `update` and removed on `create`, because `ticket_add()` reads the ticket to write from it.

## The order of checks

1. **Classic only, `Classic_Save::on_save_post()`**: not an autosave, not a revision, not a REST request, the post type is ticketable, `tec_tickets` is present, `tec_tickets_nonce` verifies (user-bound, action `tec_tickets_deferred_save`), `post_ID` equals the post being saved, and the post uses deferred save (`Controller::uses_deferred_save()`, recurring events only by default, filterable). The post form's own nonce is not used because ECP rewrites the post ID on a split save; `post_ID` is rewritten with it, which is why the binding is to `post_ID`.
2. **Block only, `Block_Save::on_rest_after_insert()`**: reached only through the core posts controller, behind its `update_item_permissions_check` / `create_item_permissions_check` (`edit_post`) and cookie auth with `X-WP-Nonce`. Not on the autosaves route. When the post does not use deferred save the payload is ignored and the response still carries a `tec_tickets` result with one payload-level error, so the editor never mistakes a stale record for a fresh answer.
3. **`Payload::from_array()`**: root must be an array with only the four parts; IDs are positive integers or digit strings; `data` must be an array; the same ticket in `update` and `delete` rejects both. A rejected entry is dropped and recorded; a malformed root or unknown part rejects the whole payload.
4. **`Commit::run()`**: total entries across parts capped at 100 (`tec_tickets_deferred_save_max_entries`), whole payload rejected above it.
5. **`Checks::run()`**: the current user can edit the post (`edit_event_tickets`, or the type's `edit_others_posts`, or `edit_post`: today's rule from `Metabox::has_permission()` without its nonce), or the whole payload is rejected. Every `update`, `delete` and `move` ticket ID must resolve through its provider to a ticket whose post type is the provider's ticket type and whose event is the post being saved (an attendee, a page, another post's ticket and a missing ID are all rejected per entry). Every `delete` passes the existing `tribe_tickets_current_user_can_delete_ticket` filter. Every `move` destination must be a post the user can edit.
6. **Routing seam**: `tec_tickets_deferred_save_routes` may send entries to other posts (mini-project D). Any payload routed to a post other than the one being saved is run through `Checks::run()` again against that post.
7. **Replay**: `update`, `move`, `create`, `delete`, each entry guarded so an exception inside a provider becomes that entry's error and never a 500. `create` resolves its provider from `data['ticket_provider']` through `Tribe__Tickets__Tickets::get_ticket_provider_instance()`, which only returns active modules; `update`, `delete` and `move` resolve the provider from the ticket, never from `data`. Each entry's `data` goes through `tribe_sanitize_deep()` first, the sanitizer `tribe_get_request_var()` applies on the AJAX path today, and `ticket_type` is sanitized as `Metabox::ajax_ticket_add()` does. The same functions run as today: `ticket_add()`, the provider's `delete_ticket()`, `Move_Ticket_Types::move_ticket_type()`, plus the `tribe_tickets_ticket_added` and `tribe_tickets_ticket_deleted` actions the replaced callers fire.

## What the editors add

- Classic (`src/resources/js/deferred-save.js`): staged rows are cloned from server-printed `<template>` elements and filled through `textContent`; a duplicated ticket's form is parsed with `DOMParser` and only read; field names are matched by attribute comparison; the open edit panel's inputs are disabled on submit so they never post as top-level fields (ETP's `Meta::save_meta` falls back to `$_POST` for them). The rejected-ticket notice (`Classic/Notices.php`) is a per-user transient and names a ticket only when it is a ticket on the saved post, so a payload cannot probe other posts' titles.
- Block (`src/modules/data/blocks/ticket/deferred*.js`): the payload builder drops `__proto__`, `constructor` and `prototype` segments and descends only through own properties; the response is applied once, against the create order captured when the request went out; staging is disabled while a save is in flight; error messages render as React text.

## Guarantees and the tests that show them

| Guarantee | Tests |
|---|---|
| Payload contract, normalisation, key overrides `ticket_id` | `tests/integration/TEC/Tickets/Deferred_Save/Payload_Test.php` |
| Post capability, ownership per entry, delete filter, move destination | `Checks_Test.php` |
| Entry cap, routed payloads re-checked, ticket type sanitized, guarded replay, sanitisation parity, provider from the ticket | `Commit_Test.php` |
| Classic: nonce, `post_ID` binding, autosave, revision, REST request, switch off, nested save | `Classic_Save_Test.php` |
| Block: switch off answered, autosave, created IDs by position, errors per entry | `Block_Save_Test.php` |
| Classic notice never names a foreign post | `Classic/Notices_Test.php` |
| End-to-end attacks as subscriber, contributor, author, logged out; foreign tickets in every part; cap; sanitisation parity | `Security/Classic_Entry_Point_Test.php`, `Security/Rest_Entry_Point_Test.php` |
| Payload builder cannot pollute prototypes; response applied once; stale records | `src/modules/data/blocks/ticket/__tests__/deferred.test.js`, `deferred-sagas.test.js` |

## Deliberate choices a reviewer may question

- **User-bound nonce on the classic path.** ECP rewrites the post ID on a "This event" / "This and following" save, so a post-bound nonce cannot be verified inside `save_post`. The `post_ID` binding is what ties the payload to one post; ECP rewrites it with the post.
- **`edit_event_tickets`** is not registered by any TEC plugin. It is kept in the rule for parity with today's ticket writes; a site may grant it to a role.
- **Sanitisation is the providers' plus `tribe_sanitize_deep()`**, exactly today's AJAX path. `ticket_sku` reaches meta as the AJAX path leaves it.
- **The move destination** is checked for `edit_post` only; whether it can hold tickets is `move_ticket_type()`'s behaviour today.

## Open items for mini-project D

- Which post a routed payload lands on after a split is D's; the seam re-checks against the destination, so D cannot widen what the user may do.
- The classic seam runs at `save_post` priority 20, before ECP commits occurrences at `redirect_post_location` 100; if D needs the occurrences it moves its hook, not the checks.
