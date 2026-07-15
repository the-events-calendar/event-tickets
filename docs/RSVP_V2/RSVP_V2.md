# RSVP V2

## What is RSVP V2

RSVP V2 is the reimplementation of the RSVP feature on top of the **Tickets Commerce (TC)** framework.

Historically, RSVP had its own bespoke data structure: its own post type/meta shape, its own "order" concept (really
just a group of attendees sharing an `_tribe_rsvp_order` hash), and its own front-end/AJAX handling, all separate
from how paid tickets work. RSVP V2 removes that duplication:

* An RSVP is now a Tickets Commerce ticket (a `tribe_tc_tickets` post) flagged with ticket meta `_type = tc-rsvp`.
* RSVPing creates a real Tickets Commerce **Order**, processed through TC's Free gateway and immediately marked
  `Completed`.
* Attendees are Tickets Commerce attendee posts (`TC_Attendee::POSTTYPE`) related to their order via `post_parent`,
  the same convention TC uses for paid tickets.

RSVP V2 **requires Tickets Commerce to be enabled**. If TC is disabled, a site falls back to a "disabled" state (see
[`RSVP_V2_Migration.md`](RSVP_V2_Migration.md) for how routing between RSVP V1, V2, and disabled works).

RSVP V2 does not replace RSVP V1 in place. Both versions live side by side in the codebase; a router
(`TEC\Tickets\RSVP\Controller`) decides at runtime which one is active for a given site, based on that site's
migration status. This document covers the V2 implementation itself; see
[`RSVP_V2_Migration.md`](RSVP_V2_Migration.md) for the router and the data migration.

## New features / behavior changes vs. legacy RSVP

* **Individual Attendee Collection (IAC)** — name/email fields for attendees can be collected per-RSVP via a modal,
  matching the IAC options already available for paid tickets (`data-iac` attribute on the front-end wrapper,
  `RSVPAttendeeCollection` block component gated on `hasTicketsPlus && hasIacVars`).
* **"Limit" replaces "Capacity"** — the metabox/DTO field is named `rsvp_limit` (still stored as ticket capacity
  under the hood); unlimited capacity (`-1`) renders as an empty field.
* **"Not Going" responses** — RSVP tickets can optionally show a "Not Going" count and let attendees flip their
  status. New ticket meta `_tribe_ticket_show_not_going` and `not_going_count` are exposed on the ticket's REST
  properties. The Attendees report replaces the (always-"Completed") order-status pill with a Going/Not Going pill,
  and hides the check-in column/action for attendees marked "not going".
* **RSVP orders in the standard Tickets Commerce Orders UI** — because an RSVP is a real TC order, it shows up
  alongside paid-ticket orders; the single-order admin screen gets an extra "Attendee's status" (Going/Not going)
  row when `show_not_going` is enabled.
* **Attendees can update their own status** — a logged-in purchaser can change their going/not-going status from
  "My Tickets", verified against the order's purchaser user ID.
* **Server-rendered response flow** — RSVP submission returns rendered HTML (via `Tribe__Tickets__Editor__Template`
  templates under `v2/commerce/rsvp/*`), not raw JSON, matching how the rest of the front-end ticket flow works.
* **Settings guard-rail** — while RSVP V2 is active, the "Enable Tickets Commerce" settings toggle is replaced with
  a forced-checked, disabled control, since disabling TC would break RSVP.

## Main files

All V2 code lives under `src/Tickets/RSVP/V2/`, registered by a single Controller.

### Registration

* **`src/Tickets/RSVP/V2/Controller.php`** — `TEC\Tickets\RSVP\V2\Controller`. Central registration point; wires up
  every class below as a singleton, registers/unregisters all their hooks, and fires
  `tec_tickets_rsvp_v2_registered` once done. Also calls the shared `RSVP_Controller_Methods` trait
  (`src/Tickets/RSVP/RSVP_Controller_Methods.php`), which both V1 and V2 use to register the legacy
  `Tribe__Tickets__RSVP` singleton, RSVP block AJAX hooks, Promoter observer hooks, and the CSV importer — V2 does
  not reimplement these, it reuses them.
* **`src/Tickets/RSVP/V2/Constants.php`** — central constants: `TC_RSVP_TYPE = 'tc-rsvp'` (the ticket `_type` meta
  value marking a TC ticket as an RSVP), `SHOW_NOT_GOING_META_KEY`, `RSVP_STATUS_META_KEY`.
* **`src/Tickets/RSVP/V2/Ticket.php`** — small helper to get/check/set a ticket's RSVP type (`is_rsvp()`,
  `set_as_rsvp()`, `get_type()`).

### Data layer

* **`src/Tickets/RSVP/V2/Repositories/Ticket_Repository.php`** — extends
  `Tribe__Tickets__Ticket_Repository`, scoped to `_type = tc-rsvp` tickets; remaps field aliases (`capacity`,
  `price`, `stock`, etc.) onto TC `Ticket` meta keys.
* **`src/Tickets/RSVP/V2/Repositories/Attendee_Repository.php`** — extends `Tribe__Tickets__Attendee_Repository`,
  implements `TEC\Tickets\RSVP\Contracts\Attendee_Repository_Interface` (the shared GDPR contract also used by
  V1). For RSVP, "order status" is the going/no going status; attendee → order relation is via `post_parent`.
* **`src/Tickets/RSVP/V2/Repository_Filters.php`** — keeps `tc-rsvp` tickets living in the same
  `tribe_tc_tickets` table as paid tickets, while excluding them from normal TC ticket listings by default (and
  un-excluding them when explicitly queried for).
* **`src/Tickets/RSVP/V2/Meta_Fields.php`** — persists the "Show Not Going" toggle onto ticket meta on save.

### Cart & checkout

* **`src/Tickets/RSVP/V2/Cart/RSVP_Cart.php`** — `extends Abstract_Cart`. The key architectural change: RSVP
  sign-ups go through a Tickets Commerce cart implementation (transient-backed, same mechanism as a normal TC
  purchase cart) instead of bespoke RSVP storage.
* **`src/Tickets/RSVP/V2/REST/Order_Endpoint.php`** — registers `POST /rsvp/v2/order` (public route). This is the
  RSVP submission flow: validates attendee details, swaps in `RSVP_Cart`, upserts the RSVP ticket into the cart, and
  creates a real TC `Order` via the TC **Free gateway**, transitioning it straight to `Completed`. Also handles an
  `opt-in` step for updating attendee-list opt-out after submission. Notable filters:
  `tec_tickets_rsvp_v2_cart_upsert_item_args`, `tec_tickets_rsvp_v2_render_step_template_args`,
  `tec_tickets_rsvp_v2_show_attendees_list_optout`.
* **`src/Tickets/RSVP/V2/REST_Properties.php`** — adds the RSVP-only `show_not_going` / `not_going_count` fields
  to the existing TC Ticket REST resource (not a separate endpoint), including OpenAPI/Swagger doc entries.

### Editors

* **`src/Tickets/RSVP/V2/Metabox.php`** — renders the dedicated Classic Editor RSVP metabox
  (`tec-tickets-commerce-rsvp`), positioned directly after the Tickets metabox; exposes the "Limit" and
  "Show Not Going" fields, and adds the Going/Not Going status to the TC single-order admin screen.
* **`src/Tickets/RSVP/V2/Classic_Editor.php`** — hides RSVP from the generic Tickets metabox (V2 has its own), and
  on save (`save_post`) builds ticket data via `Classic_Editor_Post_Data` and saves it through
  `Module::ticket_add()` — the same code path used to create a normal Tickets Commerce ticket.
* **`src/Tickets/RSVP/V2/Data_Transfer_Objects/Classic_Editor_Post_Data.php`** — DTO that parses raw metabox
  `$_POST` fields (`rsvp_limit`, `show_not_going`, dates) into the array shape TC's `ticket_add()` expects.
* **`src/Tickets/RSVP/V2/Block_Editor.php`** — injects `config.tickets.rsvpV2` into `tribe_editor_config` for the
  JS block editor (consumed by `src/modules/data/blocks/rsvp-v2/config.js`).
* **`src/Tickets/RSVP/V2/Settings.php`** — forces the "Enable Tickets Commerce" toggle on and disabled while RSVP
  V2 is active.

### Front-end

* **`src/Tickets/RSVP/V2/Frontend.php`** — renders the RSVP form template, unhooks the legacy V1 front-end form
  rendering (so markup doesn't double-render), lets a logged-in purchaser update their own going/not-going status,
  and shows RSVP status on the "My Tickets" page.
* **`src/Tickets/RSVP/V2/Attendees.php`** — swaps the legacy attendee-ID lookup for the V2 attendee repository,
  and adjusts the Attendees report display (Going/Not Going pill instead of order status, hidden check-in
  column/action for "not going" attendees).
* **`src/Tickets/RSVP/V2/Assets.php`** — registers/enqueues the JS and CSS for the classic editor metabox
  (`commerce/tickets.js`), the RSVP block (`commerce/rsvp-block.js`), and RSVP front-end styles.

### Block editor (JS)

* **`src/modules/blocks/rsvp-v2/`** — the Gutenberg block UI (`container.js`, `container-header/`,
  `container-content/`, `container-panel/`, `action-dashboard/`). `container-content` renders capacity, "Not Going"
  responses, and (when Tickets Plus + IAC vars are present) the IAC attendee-collection UI.
* **`src/modules/data/blocks/rsvp-v2/`** — the Redux data layer: `config.js` (reads
  `window.tribe_editor_config.tickets.rsvpV2`), `thunks.js` (REST calls against the TC tickets endpoint, handling
  `iac`, `capacity`, `show_not_going`, `not_going_count`).

### Tests

* **`tests/rsvp_v2_integration/`** — dedicated integration suite with one test class per production class above
  (repositories, cart, REST endpoint/properties, metabox, frontend, block editor, settings, attendees, etc.), plus
  HTML/REST snapshot coverage (metabox states, `data-iac` attribute, single-order-page integration).
