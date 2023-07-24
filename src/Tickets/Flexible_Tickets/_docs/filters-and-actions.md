# Flexible Ticktets for Event Tickets - Filters and actions

This document describes the filters and actions that have been added by the Flexible Tickets feature.

## Actions

| Plugin                | Action                                                         | Description                                                           |
|-----------------------|----------------------------------------------------------------|-----------------------------------------------------------------------|
| `event-tickets`       | `tec_tickets_ticket_add`                                       | Fires when a ticket is first created from any provider.               |
| `event-tickets`       | `tec_tickets_ticket_update`                                    | Fires when a pre-existing ticket is updated from any provider.        |
| `event-tickets`       | `tec_tickets_panels_before`                                    | Fires before rendering the Ticket panels.                             |
| `event-tickets`       | `tec_tickets_panels_after`                                     | Fires after rendering the Ticket panels.                              |
| `events-calendar-pro` | `tec_events_pro_custom_tables_v1_event_relationship_updated`   | Fires when an Event relationship with a Series is updated or created. |
| `events-calendar-pro` | `tec_events_pro_custom_tables_v1_series_relationships_updated` | Fires when a Series relationship with Events are updated or created.  |

## Filters

| Plugin          | Filter                                       | Description                                                                         |
|-----------------|----------------------------------------------|-------------------------------------------------------------------------------------|
| `event-tickets` | `tec_tickets_series_pass_singular_lowercase` | Filters the singular lowercase label for the Series Pass; defaults to `series pass` |
| `event-tickets` | `tec_tickets_series_pass_singular_uppercase` | Filters the singular uppercase label for the Series Pass; defaults to `Series Pass` |
| `event-tickets` | `tec_tickets_normalize_occurrence_id`        | Filters the normalization of Occurrence IDs when fetching tickets for them.         |