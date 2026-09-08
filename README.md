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

1. **Create the change before writing code**, named after the ticket:
   `openspec new change SOFT-1234 --store tec-plans --description "what this does"`
2. Write the proposal, then commit and push it in the plans repo.
3. Branch as usual: `feat/SOFT-1234/short-desc`.
4. Implement. If the work shows the plan was wrong — it often does — update the
   change rather than letting the plan and the code drift apart.
5. Open the PR. The template asks for the change ID, and CI checks the plan exists
   and is still active.

Every `openspec` command takes `--store tec-plans`. There is no default and no
repo-side link, so omitting it writes the change into whatever repository you
happen to be standing in.

### Where the rest is written down

This section covers what is specific to working here. The
[`tec-openspec` skill](https://github.com/the-events-calendar/skills) covers the
workflow itself — writing a proposal worth reviewing, keeping it current, and
archiving it once (after the last repository merges, not per repo). Install it with:

```
/plugin marketplace add the-events-calendar/skills
/plugin install tec
```
