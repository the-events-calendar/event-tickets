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

`data` is the array Event Tickets' `ticket_add()` accepts today. It is passed through, with two exceptions applied by the parser (`Payload/Parser.php`): `ticket_id` inside `data` is overwritten by the entry key on `update` and removed on `create`, because `ticket_add()` reads the ticket to write from it.

## The order of checks

1. **Classic only, `Classic_Save::on_save_post()`**: not an autosave, not a revision, not a REST request, the post type is ticketable, `tec_tickets` is present, `tec_tickets_nonce` is present, the request is not a preview (`wp-preview=dopreview`: previewing a draft saves the draft itself through `post_preview()`, and the page keeps its staged changes for the real save), `post_ID` equals the post being saved, the nonce verifies (user-bound, action `tec_tickets_deferred_save`), and the post has not been committed earlier in the same request (a post re-saved inside its own `save_post`, as TEC does when "Sticky in Month View" changes, is committed once). Heartbeat refreshes `tec_tickets_nonce` with the post form's own nonces (`Classic_Save::refresh_nonce()` on `wp_refresh_nonces`, only when core refreshed them for a user who may edit the post), so an edit screen left open does not lose its staged changes to an expired nonce. The feature as a whole is on unless `Controller::is_active()` is switched off through the `TEC_TICKETS_DEFERRED_SAVE_DISABLED` constant or environment variable or the `tec_tickets_deferred_save_active` filter; there is no per-post switch. The post form's own nonce is not used because ECP rewrites the post ID on a split save; `post_ID` is rewritten with it, which is why the binding is to `post_ID`.
2. **Block only, `Block_Save::on_rest_after_insert()`**: reached only through the core posts controller, behind its `update_item_permissions_check` / `create_item_permissions_check` (`edit_post`). Any authentication core accepts reaches it: cookie auth with `X-WP-Nonce` from the editor, and Application Passwords from API clients. That is wider than the legacy `tribe/tickets/v1` endpoints, whose body nonce in practice requires a cookie session; the capability and ownership checks below are the same whichever way the user authenticated. Not on the autosaves route. The hooks are attached per ticketable post type, so a payload sent with a non-ticketable type is never read and the response carries no `tec_tickets` field.
3. **`Payload\Parser::parse()`**: root must be an array with only the four parts; IDs are positive integers or digit strings; `data` must be an array; a ticket named in more than one of `update`, `move` and `delete` loses every entry. A rejected entry is dropped and recorded in `Payload\Rejections`; a malformed root or unknown part throws `Payload\Malformed_Exception`, which `Commit` answers with one payload-level error.
4. **`Commit::run()`**: the post ID is normalised through `Event::filter_event_id()`, as the AJAX save does. Total entries across parts capped at 100 (`tec_tickets_deferred_save_max_entries`), counted on the raw payload before parsing, invalid entries included, so no payload makes the parser work through more than the cap; whole payload rejected above it.
5. **`Checks::run()`**: the current user holds `edit_post` on the post, through `Ticket_Permissions::user_can_edit_tickets_of()` and its `tec_tickets_user_can_edit_tickets_of` filter, or the whole payload is rejected. Every `update`, `delete` and `move` ticket ID must resolve through its provider to a ticket whose post type is the provider's ticket type and whose event is the post being saved (an attendee, a page, another post's ticket and a missing ID are all rejected per entry). Every `update` and `move` needs `user_can_edit_ticket()` and every `delete` `user_can_delete_ticket()`, which also requires `delete_post` on the ticket; the legacy `tribe_tickets_current_user_can_delete_ticket` filter takes no user, so it is not asked. Every `move` destination must be a post the user can edit.
6. **Routing seam**: `tec_tickets_deferred_save_routes` may send entries to other posts (mini-project D). Any payload routed to a post other than the one being saved is run through `Checks::run()` again against that post.
7. **Replay**: `update`, `move`, `create`, `delete`, each entry guarded so an exception inside a provider becomes that entry's error and never a 500; the guard also turns off the manual-update flag `ticket_add()` leaves on when it throws. An exception from a `tribe_tickets_ticket_added` listener after the ticket was saved is logged and the entry keeps its success, so the editor never creates it again. `create` resolves its provider from `data['ticket_provider']` through `Tribe__Tickets__Tickets::get_ticket_provider_instance()`, which only returns active modules; `update`, `delete` and `move` resolve the provider from the ticket, never from `data`. Each entry's `data` goes through `tribe_sanitize_deep()` first, the sanitizer `tribe_get_request_var()` applies on the AJAX path today, and `ticket_type` is sanitized as `Metabox::ajax_ticket_add()` does. The same functions run as today: `ticket_add()`, the provider's `delete_ticket()`, `Move_Ticket_Types::move_ticket_type()`, plus the `tribe_tickets_ticket_added` and `tribe_tickets_ticket_deleted` actions the replaced callers fire.

## What the editors add

- Classic (`src/resources/js/deferred-save.js`): staged rows are cloned from server-printed `<template>` elements and filled through `textContent`; a duplicated ticket's form is parsed with `DOMParser` and only read; field names are matched by attribute comparison; the open edit panel's inputs are disabled on submit so they never post as top-level fields (ETP's `Meta::save_meta` falls back to `$_POST` for them). The rejected-ticket notice (`Classic/Notices.php`) is a per-user transient and names a ticket only when it is a ticket on the saved post, so a payload cannot probe other posts' titles.
- Classic, continued: a saved ticket carries one staged change at most (move refused while an edit is staged, edit while a move is staged, delete replaces either), and a submit that would carry more fields than `max_input_vars` is refused rather than truncated by PHP.
- Block (`src/modules/data/blocks/ticket/deferred*.js`): the payload builder drops `__proto__`, `constructor` and `prototype` segments and descends only through own properties, and never names a ticket in two parts; the payload is rebuilt on `editor.preSavePost` from the blocks that exist, and what it carries is recorded; the response is applied once, to exactly what was sent (`reconcileSaveResponse()`), so changes staged while the request was in flight stay staged; a payload-level refusal keeps every sent change staged with its message; refused deletes and moves are shown as editor notices; lifecycle hooks receive the ticket details and run isolated after the store is settled; error messages render as React text.

## Guarantees and the tests that show them

| Guarantee | Tests |
|---|---|
| Payload contract, normalisation, key overrides `ticket_id` | `tests/integration/TEC/Tickets/Deferred_Save/Payload/Parser_Test.php`, `Payload_Test.php` |
| Post capability, ownership per entry, delete filter, move destination | `Checks_Test.php` |
| Entry cap on raw entries, routed payloads re-checked, ticket type sanitized, guarded replay, manual-update flag reset, listener failure after a save, normalised post ID, sanitisation parity, provider from the ticket | `Commit_Test.php` |
| Classic: nonce, `post_ID` binding, autosave, revision, REST request, preview, a post re-saved during its save committed once, nonce refreshed by heartbeat, any ticketable post, nested save of another post | `Classic_Save_Test.php`, `Controller_Test.php` |
| Block: non-ticketable type ignored, autosave, created IDs by position, errors per entry, a prepare answer that is not a response passed on | `Block_Save_Test.php` |
| Classic notice never names a foreign post | `Classic/Notices_Test.php` |
| End-to-end attacks as subscriber, contributor, author, logged out; foreign tickets in every part; cap; sanitisation parity | `Security/Classic_Entry_Point_Test.php`, `Security/Rest_Entry_Point_Test.php` |
| Payload builder cannot pollute prototypes and never names a ticket twice; response applied once, to what was sent; payload-level refusals; stale records | `src/modules/data/blocks/ticket/__tests__/deferred.test.js`, `deferred-sagas.test.js` |
| Classic: one staged change per ticket, input budget, locale-aware validation | `src/resources/js/deferred-save/__tests__/*.test.js` |

## Deliberate choices a reviewer may question

- **User-bound nonce on the classic path.** ECP rewrites the post ID on a "This event" / "This and following" save, so a post-bound nonce cannot be verified inside `save_post`. The `post_ID` binding is what ties the payload to one post; ECP rewrites it with the post.
- **Only `edit_post` opens the post.** `Metabox::has_permission()` also accepts `edit_event_tickets` or the type's `edit_others_posts`. Community Events grants `edit_event_tickets` to every logged-in user by default, so that rule admits any subscriber to any post; `edit_others_posts` is covered by `edit_post`, which `map_meta_cap()` maps to it for someone else's post. The legacy AJAX and REST paths still use the old rule.
- **Sanitisation is the providers' plus `tribe_sanitize_deep()`**, exactly today's AJAX path. `ticket_sku` reaches meta as the AJAX path leaves it.
- **The move destination** is checked for `edit_post` only; whether it can hold tickets is `move_ticket_type()`'s behaviour today. The legacy AJAX move checks no capability at all.
- **REST hooks are attached per ticketable type at registration.** A type made ticketable later through `tribe_tickets_post_types` is not hooked and its payload is ignored (the editor reports it as not saved). The classic hook checks the type at runtime.
- **An exception inside a provider after it persisted a ticket** is reported as that entry's failure; `Commit` cannot see the ID the provider never returned. The legacy AJAX save answers the same case with a 500.
- **The classic notice is rendered by hand**, escaped, rather than through StellarWP AdminNotices, whose per-ID dismissal does not fit a one-shot notice per save.

## Open items for mini-project D

- Which post a routed payload lands on after a split is D's; the seam re-checks against the destination, so D cannot widen what the user may do.
- The classic seam runs at `save_post` priority 20, before ECP commits occurrences at `redirect_post_location` 100; if D needs the occurrences it moves its hook, not the checks.
