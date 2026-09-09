<p align="center"><a href="https://theeventscalendar.com/products/wordpress-event-tickets/"><img src="https://s3.theeventscalendar.com/uploads/2020/04/ET-Icon.svg" alt="Event Tickets" width="100px" height="auto"></a></p>


Welcome to the Event Tickets repository on GitHub. Here you can browse the source code and keep track of development.

Event Tickets provides a simple way for visitors to RSVP or purchase tickets to your events. As a standalone plugin, it enables you to add RSVPs or tickets to posts or pages. When paired with [The Events Calendar](https://evnt.is/18tg), you can add that same functionality directly to your event listings.

We recommend to stay up to date about everything happening in the project by [following @TheEventsCal](https://twitter.com/TheEventsCal) on Twitter.

If you are not a developer, please use the [Event Tickets plugin page](https://wordpress.org/plugins/event-tickets/) on WordPress.org.

## Documentation
* [Event Tickets Documentation](https://theeventscalendar.com/knowledgebase/k/new-user-primer-event-tickets-event-tickets-plus/)
* [Event Tickets Developer Documentation](https://docs.theeventscalendar.com/product/event-tickets/)
* [Contributing guide](https://github.com/the-events-calendar/event-tickets/blob/master/CONTRIBUTING.md)
* [Quick tests introduction](https://github.com/the-events-calendar/event-tickets/blob/master/tests.md)

## Reporting Issues
To report an issue, [please create a new pull request](https://github.com/the-events-calendar/event-tickets/pulls).

## Support
This repository is not suitable for support. Please don't use our issue tracker for support requests, but for core Event Tickets issues only. Support can take place through the appropriate channels:

* If you have a problem, you may want to start with the [self help guide](https://theeventscalendar.com/knowledgebase/k/new-user-primer-event-tickets-event-tickets-plus/).
* [TheEventsCalendar.com premium support portal](https://support.theeventscalendar.com/ ) for customers who have purchased our premium plugins.
* [Our community forum on wp.org](https://wordpress.org/plugins/event-tickets/) which is available for all Event Tickets users.

Support requests on this repository will be closed on sight.

## Contributing to Event Tickets
If you have a patch or have stumbled upon an issue with Event Tickets core, you can contribute this back to the code. Please read our [contributor guidelines](https://github.com/the-events-calendar/event-tickets/blob/master/CONTRIBUTING.md) for more information how you can do this.

## Specs and planning

Work on this repository is planned before it is written, using
[OpenSpec](https://github.com/Fission-AI/OpenSpec). **This is enforced** — every
pull request is checked for a plan.

Plans do not live here. A TEC feature routinely spans several repositories, so a
spec kept in one of them is invisible from the others. They all live in one shared
store instead: [`the-events-calendar/plans`](https://github.com/the-events-calendar/plans).

### First time only

```bash
npm install -g @fission-ai/openspec
git clone git@github.com:the-events-calendar/plans.git ~/repos/tec-plans
openspec store register ~/repos/tec-plans --id tec-plans
```

The clone path is yours to choose. The `--id` is not — every command refers to the
store as `tec-plans`.

### Working on a ticket

1. **Create the change before writing code**, named after the ticket in lower
   case. OpenSpec requires kebab-case and rejects anything else, so the ticket
   `SOFT-1234` becomes the change `soft-1234`:
   `openspec new change soft-1234 --store tec-plans --description "what this does"`
2. Write the proposal, then commit and push it in the plans repo.
3. Branch as usual: `feat/SOFT-1234/short-desc`.
4. Implement. If the work shows the plan was wrong — it often does — update the
   change rather than letting the plan and the code drift apart.
5. Open the PR. The template asks for the change ID, and CI checks the plan exists
   and is still active.

Commands that read or write plans take `--store tec-plans`: `new change`,
`status`, `instructions`, `list`, `show`, `validate`, `archive`, `context` and
`doctor`. The `openspec store` commands manage registrations instead and take
`--id`, which is why the setup block above uses that.

A machine that has run the skills repo's `install.sh` has OpenSpec's
`defaultStore` set to `tec-plans` and resolves without the flag. Pass it anyway:
a machine without that setting writes the change into whatever repository you
happen to be standing in.

### Where the rest is written down

This section covers what is specific to working here. The
[`tec-openspec` skill](https://github.com/stellarwp/skills-se) covers the
workflow itself — writing a proposal worth reviewing, keeping it current, and
archiving it once (after the last repository merges, not per repo). Install it with:

The below will work only once the `stellarwp/skills-se` becomes public.

```text
/plugin marketplace add stellarwp/skills-se
/plugin install nexcess-se
```

## Running the tests

PHP tests are Codeception/wp-browser suites run inside Docker by [slic](https://github.com/stellarwp/slic), the same runner CI uses.

### Setup

Event Tickets does not test alone. CI clones **the-events-calendar as a sibling directory** of `event-tickets` (plus `events-pro` for the CT1/FT suites) and points slic at the shared parent. This layout is the thing people get wrong — the suites activate `the-events-calendar/the-events-calendar.php` by path relative to the plugins directory.

```bash
# 1. One parent directory holding every plugin side by side.
mkdir -p ~/plugins && cd ~/plugins
git clone --recursive git@github.com:the-events-calendar/event-tickets.git
git clone --recursive git@github.com:the-events-calendar/the-events-calendar.git
git clone --recursive git@github.com:the-events-calendar/events-pro.git   # ct1_*/ft_*/slr_ecp_* only
git clone https://github.com/stellarwp/slic.git
# => ~/plugins/{event-tickets,the-events-calendar,events-pro,slic}

# 2. Point slic at that parent.
./slic/slic here
./slic/slic build-subdir off
./slic/slic xdebug off

# 3. Install deps for every plugin AND every common, in this order.
./slic/slic use the-events-calendar        && ./slic/slic composer install --no-dev
./slic/slic use the-events-calendar/common && ./slic/slic composer install --no-dev
./slic/slic use events-pro                 && ./slic/slic composer install --no-dev   # if cloned
./slic/slic use event-tickets/common       && ./slic/slic composer install --no-dev
./slic/slic use event-tickets              && ./slic/slic composer install

# 4. Start WordPress and the themes the suites expect.
./slic/slic up wordpress
./slic/slic wp theme install twentytwenty --activate
./slic/slic wp theme install kadence       # square_integration runs on this theme
```

`slic use event-tickets` must come last — it selects the plugin whose suites run.

### Running a suite

```bash
cd ~/plugins && ./slic/slic use event-tickets

./slic/slic run integration                                              # whole suite
./slic/slic run integration tests/integration/RSVP_Tickets_Test.php      # one file
./slic/slic run integration tests/integration/RSVP_Tickets_Test.php:test_ticket_with_stock
./slic/slic run wpunit --filter test_ticket_stock                        # by name
```

Never run `slic run` with no suite — WordPress globals leak across suites and everything fails.

### Suites

Every suite below runs on each PR that touches PHP files. `acceptance` is commented out of the CI matrix and is not run.

| Suite | Covers | Workflow |
|---|---|---|
| `functional` | Browser-less request/response tests (WPBrowser + DB dump) | tests-php.yml |
| `integration` | Core ET integration tests with TEC active | tests-php.yml |
| `wpunit` | Unit-level tests booted inside WordPress | tests-php.yml |
| `restv1` | Legacy `tribe/tickets/v1` REST API | tests-php.yml |
| `rest_tec_v1_integration` | New `tec/v1` REST API; CI also Spectral-lints the OpenAPI docs | tests-php.yml |
| `views_integration` | Template/view rendering | tests-php.yml |
| `commerce_integration` | Tickets Commerce: orders, gateways, checkout | tests-php.yml |
| `slr_integration` | Seating (layouts/reservations) with TEC | tests-php.yml |
| `square_integration` | Square gateway, on the Kadence theme | tests-php.yml |
| `ct1_integration` | Custom Tables v1 with TEC + ECP | tests-php-ct1.yml |
| `slr_ecp_integration` | Seating combined with Events Calendar Pro | tests-php-ct1.yml |
| `order_modifiers_integration` | Fees, coupons, other order modifiers | tests-php-ct1.yml |
| `ft_smoketest` | Flexible Tickets smoke tests against a seeded dump | tests-php-ft.yml |
| `ft_integration` | Flexible Tickets / Series Passes with TEC + ECP | tests-php-ft.yml |
| `ft_ct1_migration` | Flexible Tickets migration onto CT1 tables | tests-php-ft.yml |
| `acceptance` | Chrome/WebDriver e2e (`slic up chrome`, `slic npm run build`) | none |

### How this differs from CI

- CI pins WordPress with `slic wp core update --force --version=6.8`; locally the container's shipped version is fine unless chasing a version-specific failure.
- CI appends `--ext DotReporter` (compact logs) and installs `twentytwentyfour` as well; neither matters locally.
- CI runs `slic npm install && slic npm run build` only for `acceptance`, which is disabled.
- CI checks out sibling plugins on a matching branch via its `smart-checkout` action; locally check out the branches you need by hand.
