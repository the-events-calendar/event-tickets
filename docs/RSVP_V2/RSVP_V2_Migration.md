# RSVP V2 Migration

This document covers how a site moves from legacy RSVP ("V1") to RSVP V2, and how that migration is wired into
Event Tickets (ET). See [`RSVP_V2.md`](RSVP_V2.md) for the V2 implementation itself.

## Why a migration, not an in-place rewrite

RSVP V2 changes the underlying data shape (RSVP tickets/attendees become Tickets Commerce tickets/attendees/orders).
An in-place rewrite of the existing RSVP code would leave no fallback: if a site's data failed to migrate, or a
site's customizations depended on the old data shape, the only option would be staying on an old plugin version.

Instead, V1 and V2 both live in the codebase at the same time, gated behind a version router, with a reversible,
batch-processed migration moving data from the V1 shape to the V2 shape. The migration can be rolled back at any
point, and RSVP is fully hidden (not just read-only) while a migration is actively running.

The **compatibility boundary is the repository API**: code that goes through ET's ticket/attendee repositories sees
no change. Code that queries WordPress directly (e.g. `WP_Query` for `tribe_rsvp_tickets` posts, or a `get_post()`
call assuming the old post type) will see the new data shape once migrated.

## The version router: `TEC\Tickets\RSVP\Controller`

`src/Tickets/RSVP/Controller.php` decides, at runtime, whether a site runs RSVP V1, RSVP V2, or has RSVP disabled.
The resolved version is cached in the `tickets_rsvp_version` tribe option (`Controller::VERSION_OPTION_KEY`) so the
migration status lookup only happens once per resolution.

`detect_version_from_migration_status()` reads the `rsvp-to-tc` migration's status from the migration registry
(`migrations()->get_registry()->get('rsvp-to-tc')`) and maps it:

| Migration `Status` | Resolved version |
|---|---|
| `NOT_APPLICABLE` (no legacy data) | `v2` |
| `COMPLETED` | `v2` |
| `PENDING`, `SCHEDULED`, `FAILED`, `CANCELED`, `REVERTED` | `v1` |
| `RUNNING`, `PAUSED` | disabled |
| registry entry missing | `v2` |

If no migration is registered (`rsvp-to-tc` not found in the registry), the router assumes the migration completed
or was removed and defaults to V2.

`do_register()` uses this to decide what to wire up:

* **`v1`** → registers `V1\Controller` (`src/Tickets/RSVP/V1/Controller.php`).
* **`v2`**, only if `tec_tickets_commerce_is_enabled()` → registers `V2\Controller` and binds the RSVP
  ticket/attendee repositories to the V2 implementations in the container. If V2 was selected but TC isn't enabled,
  it falls back to disabled.
* **disabled** (explicit `TEC_TICKETS_RSVP_DISABLED` constant/env var, migration running/paused, or the
  `tec_tickets_rsvp_enabled` filter returning false) → `register_disabled()`.

`register_disabled()` binds a null-object RSVP implementation (`RSVP_Disabled`) plus "dud" repositories
(`Repositories\Ticket_Repository_Disabled`, `Repositories\Attendee_Repository_Disabled`, both under
`src/Tickets/RSVP/Repositories/`) so front-end/admin code calling into RSVP doesn't fatal while the feature is
off — it just gets empty results (`[]`, `0`, `null`) from a fluent no-op repository. The one deliberate exception
is `RSVP_Disabled::create_attendee_for_ticket()`, which throws instead of silently no-op'ing, since a caller
expecting a created attendee object back would otherwise be misled.

This same "disabled" path is what protects the site while `rsvp-to-tc` is `RUNNING` or `PAUSED`: the migration's
`before_up`/`before_down` explicitly set the version option to `Controller::DISABLED` for the duration of a batch
run, and `after_up`/`after_down` set it to `v2`/`v1` once the run fully completes.

`Controller` also force-enables Tickets Commerce and auto-creates the TC checkout/order-success pages
(`maybe_activate_tickets_commerce()`, via `Payments_Tab`) early, before providers register, whenever the resolved
version is `v2`.

V1 has no special teardown of its own — it's deactivated simply by never being registered (or having
`unregister()` called on it) by the router, not by any logic inside `V1\Controller` itself.

## The migration framework: `stellarwp/migrations`

The generic migration engine is a separate, reusable package — `stellarwp/migrations` — vendored into
`tribe-common` and exposed under the `TEC\Common\StellarWP\Migrations\*` namespace. It is built on **Shepherd**
(`TEC\Common\StellarWP\Shepherd\*`, also vendored) for background batch task scheduling. ET is a consumer of this
package, not its owner — this keeps all RSVP-specific migration logic inside ET, while the framework itself can be
reused by other migrations across the TEC/StellarWP plugin family.

Bootstrap: `common/src/Common/Libraries/Migrations.php` registers the package's provider with hook prefix `tec`
(from `common/src/Common/Libraries/Provider.php`), which is why every dynamic hook name in this system contains
`tec`, e.g. `stellarwp_migrations_tec_automatic_schedule`, `stellarwp_migrations_tec_filters`.

Key pieces ET relies on:

* **`Abstracts\Migration_Abstract`** — base class every migration extends. Provides default no-op
  `before_up`/`after_up`/`before_down`/`after_down`, batch-count math, and `get_status()` (derives the migration's
  current `Status` from its latest `Migration_Executions` row, or `NOT_APPLICABLE`/`PENDING` if none exists).
* **`Enums\Status`** — `pending`, `scheduled`, `running`, `paused`, `completed`, `failed`, `canceled`, `reverted`,
  `not-applicable`.
* **`Enums\Operation`** — `UP` / `DOWN`.
* **`Registry`** — where migrations are registered (`register($id, $class)`) and looked up
  (`get($id)`) by ID. ET registers RSVP's migration under the ID `rsvp-to-tc`.
* **Admin UI** (`Admin\Provider`, `Admin\UI`) — a hidden admin submenu page rendering migration
  list/detail/progress views; ET configures its list/parent URLs so the UI links back into ET's own Settings page.
* **CLI** (`CLI\Commands`, registered as `wp {prefix} migrations ...` → **`wp tec migrations ...`**) — subcommands
  `list`, `run <id>`, `rollback <id>`, `logs <execution_id>`, `executions <id>`. ET does not register any
  RSVP-specific WP-CLI command; the generic command surface is used directly, e.g.:
  * `wp tec migrations run rsvp-to-tc`
  * `wp tec migrations rollback rsvp-to-tc`
  * `wp tec migrations list --tags=event-tickets`
  * `wp tec migrations executions rsvp-to-tc`
* **REST** (`REST\Endpoints`, namespace `tec/migrations/v1`, gated on `manage_options`) —
  `GET /migrations`, `POST /migrations/{id}/run`, `POST /migrations/{id}/rollback`,
  `GET /migrations/{id}/executions`, `GET /executions/{id}/logs`. For RSVP:
  `POST tec/migrations/v1/migrations/rsvp-to-tc/run`, `.../rsvp-to-tc/rollback`.
* **Logging** — a structured `Utilities\Logger` records diagnostic data per execution, with automatic log
  retention/cleanup.

Batch-processing, fail-fast (no retries by default, throws `ShepherdTaskFailWithoutRetryException` on batch
failure), and idempotent replay (`is_up_done()`/`is_down_done()` guards) are all framework responsibilities; a
migration author only has to implement the callbacks correctly. Note the framework does not itself enforce
site-wide WP maintenance mode during a run — ET's mitigation is the RSVP-specific "disabled" state described above,
which only protects RSVP functionality, not the rest of the site.

## Wiring RSVP into the framework: `src/Tickets/Migrations/Controller.php`

`TEC\Tickets\Migrations\Controller` is where ET plugs into the framework:

* `add_filter('stellarwp_migrations_tec_automatic_schedule', '__return_false')` — disables the framework's
  automatic scheduling for ET's migrations; running the RSVP migration is a deliberate admin action, not something
  that fires on its own.
* `migrations()->get_registry()->register('rsvp-to-tc', RSVP_To_Tickets_Commerce::class)` — registers the
  migration under the ID `rsvp-to-tc`, the same ID the `RSVP\Controller` router looks up.
* Registers a "Migrations" tab (`add_action('tribe_settings_do_tabs', ...)`) inside ET's own Settings page
  (`tec-tickets-settings`), rendering the framework's shared list UI filtered to migrations tagged
  `event-tickets` (`stellarwp_migrations_tec_filters` filter), with the tag-search chrome hidden via inline CSS
  since only one migration is relevant here.

## The migration itself: `src/Tickets/Migrations/RSVP_To_Tickets_Commerce.php`

`TEC\Tickets\Migrations\RSVP_To_Tickets_Commerce extends Migration_Abstract`. All RSVP migration logic lives here,
in ET only — nothing migration-related lives in Event Tickets Plus, so behavior is identical whether ET+ is active
or not.

* **Batch size**: 50 (`get_default_batch_size()`).
* **Applicability**: applicable if there's legacy V1 data to migrate, or previously-migrated data exists (so
  rollback stays available even after a full migration).
* **`up()`** — for each unmigrated `tribe_rsvp_tickets` post: changes its `post_type` to the TC ticket post type,
  migrates its meta onto the TC ticket meta keys (e.g. `_tribe_rsvp_for_event` → TC's event-relation key), then
  groups its attendees by their legacy `_tribe_rsvp_order` hash and creates one TC **Order** per group
  (`post_status = tec-tc-completed`, `gateway = free`), migrating each attendee's meta onto TC attendee meta keys
  (security code, opt-out, price paid, email, ticket-sent, going/not-going status) and relating it to the new order
  via `post_parent`. Tickets with no event relation are logged and skip-marked rather than migrated.
* **`down()`** — reverses this per ticket: rolls back attendees, deletes any migration-created order left with no
  attendees, reverts the ticket's `post_type` back to `tribe_rsvp_tickets`, and restores original meta/post-name/
  post-title values.
* **Rollback-safety meta keys** — the migration never overwrites data without first preserving it:
  * `_tec_rsvp_migrated_to_tc` — marks a ticket as migrated (or `-1` for skipped tickets with no event relation).
  * `_tec_rsvp_migration_created` — marks orders the migration itself created, so rollback only deletes
    migration-created orders, never pre-existing ones.
  * `_tec_rsvp_original_ticket_meta`, `_tec_rsvp_original_post_name`, `_tec_rsvp_original_post_title` — snapshot
    values overwritten during `up()`, so `down()` restores exact prior state instead of guessing.
* **RSVP visibility during a run** — `before_up`/`before_down` set the `tickets_rsvp_version` option to
  `Controller::DISABLED` on the first batch of a run; `after_up`/`after_down` set it to `v2`/`v1` once the run
  fully completes. This is what hides RSVP entirely while data is in flux, rather than risking reads/writes against
  a half-migrated data set.
* **Meta-key renames** use raw SQL for performance (`UPDATE ... SET meta_key = ...`), with manual cache
  invalidation afterward (`wp_cache_delete`, `tribe_cache()->set_last_occurrence(...)`) since bypassing
  `update_post_meta()` also bypasses its cache/hook side effects.

## Testing

* **`tests/rsvp_to_tc_migration/`** — dedicated Codeception suite covering `up()`/`down()` correctness end to end:
  simple tickets, multiple attendees in one vs. multiple orders, "not going" status preservation, unlimited
  capacity, date-restricted tickets, opt-out attendees, batching across multiple tickets, missing order hash, and
  structural parity between migrated data and what a native RSVP V2 ticket/attendee/order looks like.
* **`tests/integration/TEC/Tickets/Migrations/Controller_Test.php`** — covers the `event-tickets` tag injection
  into the Migrations admin tab's filters. Tab registration/ordering and the `automatic_schedule` filter override
  are not directly covered by this suite.

## Rollout plan

* Existing RSVP data and behavior are unaffected until a site runs the migration — the router resolves to `v1` for
  as long as `rsvp-to-tc` has legacy data and hasn't been migrated.
* New sites (no legacy RSVP data) resolve straight to `v2` (`NOT_APPLICABLE` status).
* The legacy RSVP UI/code is not being removed yet; per the project plan it is scheduled for deprecation, with the
  team no longer QAing or supporting the old UI outside of security fixes or fatals, and IAN notices used to
  communicate the timeline to users.
