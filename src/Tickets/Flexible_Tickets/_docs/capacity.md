# Flexible Tickets for Event Tickets - Capacity

The Flexible Tickets feature does not change the way Capacity is currently modeled in Event Tickets.
Conceptually, there are no changes from what users are used to.

## A recap about capacity

Before exploring how Capacity is modeled and used by Event Tickets, Events Tickets Plus, and the Flexible Tickets
feature,
it's worth spending a few words about what Capacity is and what is trying to model.
The example is not specific to a type of ticket introduced by the feature, but it is a good way to understand the
concepts.

### Shared capacity

A theatre wants to sell tickets for a play, and the theatre has a capacity of 100 seats.
The theatre wants to sell tickets for 2 types of viewers: adults and children.
There will be 2 tickets on sale: one for adults and one for children.
Each ticket can sell up to 100 seats, but the total number of seats sold for both tickets cannot exceed 100: the theatre
simply cannot seat more that 100 people.

In this scenario, the theatre has a "shared" capacity of 100 seats, and the tickets are not "capped": the theatre is
fine selling 100 Adult tickets and 0 Child tickets, 0 Adult tickets and 100 Child tickets, or any combination in
between.

### Capped capacity

Keeping in mind the same scenario above, the theatre might want to put a cap on the number of tickets sold for each type
of viewer.
E.g. a play might be more suited to children and the theatre would like to sell at most 50 Adult tickets.
The capacity for the Adult ticket would be "capped" at 50 seats.
The capacity for the Child ticket would not be "capped".
The capacity shared between the two tickets would still be 100 seats since the theatre cannot seat more than 100 people.

### Shared, capped and different capacities

After some renovation work, the theatre decides to add a new type of ticket: "VIP".
The VIP ticket will be sold at a higher price than the other tickets, and customers of the "VIP" tickets will be seated
in a new section of the theatre sporting 20 new, more comfortable, chairs with a better view on the stage.
The capacity for the VIP ticket will not be shared with the other tickets: the VIP ticket has its own, not shared
capacity.

### Unlimited capacity

During the renovation work, the theatre installed cameras in the theatre and decided to live-stream the play to anyone
that cannot, or does not want to, attend the play in person.
The theatre will sell tickets for the live-streaming, and the capacity for those tickets will be unlimited as there is
no hard limit on the number of people that can watch the play.
This new ticket, called "Live-stream", will have its own, unlimited, capacity.

## Capacity persistence layer

The Flexible Tickets feature does not change how Capacity is modeled in Event Tickets.
For the sake of clarity, it's worth relating the high-level concept of Capacity updates to the under-the-hood workings.
With the example above fresh in mind, it's easier to understand how Capacity work.

In the example below, we'll assume the provider is Commerce. This changes the name of the meta key used to model the
relationship between a Ticket and the ticketed Post according to this table:

| Provider               | Meta key                      |
|------------------------|-------------------------------|
| Commerce               | `_tec_tickets_commerce_event` |
| WooCommerce            | `_tribe_wooticket_event`      |
| Easy Digital Downloads | `_tribe_eddticket_for_event`  |
| PayPal                 | `_tribe_tpp_for_event`        |

At the start these are the entities at play (no pun intended):

* The play is an Event (a custom post type) with an ID of `789` and a capacity of `100` seats
* The Adult ticket has an ID of `123`, a capped shared capacity of `50` seats
* The Child ticket has an ID of `345`, an uncapped shared capacity of `100` seats
* The VIP ticket has an ID of `456`, a capped own capacity of `20` seats
* The Live-stream ticket has an ID of `567`, an unlimited own capacity

This is a snapshot of the `wp_postmeta` table reporting the Capacity related columns and values representing the Event
shared Capacity of 100 seats:

| post_id | meta_key                         | meta_value | Note                                                        |
|---------|----------------------------------|------------|-------------------------------------------------------------|
| 789     | _tribe_ticket_use_global_stock   | 1          | The flag indicating the Event uses globally shared Capacity |
| 789     | _tribe_ticket_capacity           | 100        | The max value of the Capacity of the Event                  |
| 789     | _tribe_ticket_global_stock_level | 100        | The current value of the Capacity of the Event              |

The Child ticket has the same Capacity as the Event, here are its `wp_postmeta` entries:

| post_id | meta_key                    | meta_value | Note                                                                             |
|---------|-----------------------------|------------|----------------------------------------------------------------------------------|
| 345     | _tec_tickets_commerce_event | 789        | The meta used to model the relationship with a ticketed post                     |
| 345     | _global_stock_mode          | global     | The ticket will use and affect the Event globally shared capacity, no cap        |
| 345     | _tribe_ticket_capacity      | 100        | The max value of the Capacity of the ticket, kept in sync with the Event one     |
| 345     | _stock                      | 100        | The current value of the Capacity of the ticket, kept in sync with the Event one |
| 345     | _manage_stock               | yes        | The ticket will manage its stock.                                                |

The Adult ticket has a capped Capacity of `50`, here are its `wp_postmeta` entries:

| post_id | meta_key                    | meta_value | Note                                                                             |
|---------|-----------------------------|------------|----------------------------------------------------------------------------------|
| 123     | _tec_tickets_commerce_event | 789        | The meta used to model the relationship with a ticketed post                     |
| 123     | _global_stock_mode          | capped     | The ticket will use and affect the Event globally shared capacity, with a cap    |
| 123     | _tribe_ticket_capacity      | 50         | The max value of the Capacity of the ticket, kept in sync with the Event one     |
| 123     | _stock                      | 50         | The current value of the Capacity of the ticket, kept in sync with the Event one |
| 123     | _manage_stock               | yes        | The ticket will manage its stock.                                                |

The VIP ticket has its own Capacity of `20`, here are its `wp_postmeta` entries:

| post_id | meta_key                    | meta_value | Note                                                                             |
|---------|-----------------------------|------------|----------------------------------------------------------------------------------|
| 456     | _tec_tickets_commerce_event | 789        | The meta used to model the relationship with a ticketed post                     |
| 456     | _global_stock_mode          | own        | The ticket will use and affect its own capacity, no cap                          |
| 456     | _tribe_ticket_capacity      | 20         | The max value of the Capacity of the ticket, kept in sync with the Event one     |
| 456     | _stock                      | 20         | The current value of the Capacity of the ticket, kept in sync with the Event one |
| 456     | _manage_stock               | yes        | The ticket will manage its stock.                                                |

Finally, the Live-stream ticket has its own unlimited Capacity, here are its `wp_postmeta` entries:

| post_id | meta_key                    | meta_value | Note                                                         |
|---------|-----------------------------|------------|--------------------------------------------------------------|
| 567     | _tec_tickets_commerce_event | 789        | The meta used to model the relationship with a ticketed post |
| 567     | _manage_stock               | no         | The ticket will not manage a stock.                          |

> Note the `_global_stock_mode` meta key is missing, this is because the ticket has an unlimited, own Capacity.

Now the stage is set up, let's start with a first customer purchasing 3 Adult and 7 Child tickets.
The `wp_postmeta` table will look like this (using diff syntax to highlight the changes):

```diff
| post_id | meta_key                         | meta_value |
|---------|----------------------------------|------------|
| 789     | _tribe_ticket_use_global_stock   | 1          |
| 789     | _tribe_ticket_capacity           | 100        |
-| 789     | _tribe_ticket_global_stock_level | 100        |
+| 789     | _tribe_ticket_global_stock_level | 90         |
| 345     | _tec_tickets_commerce_event      | 789        |
| 345     | _global_stock_mode               | global     |
| 345     | _manage_stock                    | yes        |
| 345     | _tribe_ticket_capacity           | 100        |
-| 345     | _stock                           | 100        |
+| 345     | _stock                           | 93         |
| 123     | _tec_tickets_commerce_event      | 789        |
| 123     | _global_stock_mode               | capped     |
| 123     | _manage_stock                    | yes        |
| 123     | _tribe_ticket_capacity           | 50         |
-| 123     | _stock                           | 50         |
+| 123     | _stock                           | 47         |
| 456     | _tec_tickets_commerce_event      | 789        |
| 456     | _global_stock_mode               | own        |
| 456     | _manage_stock                    | yes        |
| 456     | _tribe_ticket_capacity           | 20         |
| 456     | _stock                           | 20         |
| 567     | _tec_tickets_commerce_event      | 789        |
| 567     | _manage_stock                    | no         |
```

Then a Customer purchases 3 VIP tickets.
The `wp_postmeta` table will look like this:

```diff
| post_id | meta_key                         | meta_value |
|---------|----------------------------------|------------|
| 789     | _tribe_ticket_use_global_stock   | 1          |
| 789     | _tribe_ticket_capacity           | 100        |
| 789     | _tribe_ticket_global_stock_level | 90         |
| 345     | _tec_tickets_commerce_event      | 789        |
| 345     | _global_stock_mode               | global     |
| 345     | _manage_stock                    | yes        |
| 345     | _tribe_ticket_capacity           | 100        |
| 345     | _stock                           | 93         |
| 123     | _tec_tickets_commerce_event      | 789        |
| 123     | _global_stock_mode               | capped     |
| 123     | _manage_stock                    | yes        |
| 123     | _tribe_ticket_capacity           | 50         |
| 123     | _stock                           | 47         |
| 456     | _tec_tickets_commerce_event      | 789        |
| 456     | _global_stock_mode               | own        |
| 456     | _manage_stock                    | yes        |
| 456     | _tribe_ticket_capacity           | 20         |
-| 456     | _stock                           | 20         |
+| 456     | _stock                           | 17         |
| 567     | _tec_tickets_commerce_event      | 789        |
| 567     | _manage_stock                    | no         |
```

Then a Customer purchases 1 Live-stream ticket; the `wp_postmeta` table will not change:

```diff
| post_id | meta_key                         | meta_value |
|---------|----------------------------------|------------|
| 789     | _tribe_ticket_use_global_stock   | 1          |
| 789     | _tribe_ticket_capacity           | 100        |
| 789     | _tribe_ticket_global_stock_level | 90         |
| 345     | _tec_tickets_commerce_event      | 789        |
| 345     | _global_stock_mode               | global     |
| 345     | _manage_stock                    | yes        |
| 345     | _tribe_ticket_capacity           | 100        |
| 345     | _stock                           | 93         |
| 123     | _tec_tickets_commerce_event      | 789        |
| 123     | _global_stock_mode               | capped     |
| 123     | _manage_stock                    | yes        |
| 123     | _tribe_ticket_capacity           | 50         |
| 123     | _stock                           | 47         |
| 456     | _tec_tickets_commerce_event      | 789        |
| 456     | _global_stock_mode               | own        |
| 456     | _manage_stock                    | yes        |
| 456     | _tribe_ticket_capacity           | 20         |
| 456     | _stock                           | 17         |
| 567     | _tec_tickets_commerce_event      | 789        |
| 567     | _manage_stock                    | no         |
```

Due to a change in the regulations, the theatre cannot sit 100 people anymore in the main area, only 85: the Event
Capacity is reduced to `85`.
The meta values will change accordingly:

```diff
| post_id | meta_key                         | meta_value |
|---------|----------------------------------|------------|
| 789     | _tribe_ticket_use_global_stock   | 1          |
-| 789     | _tribe_ticket_capacity           | 100        |
+| 789     | _tribe_ticket_capacity           | 85         |
-| 789     | _tribe_ticket_global_stock_level | 90         |
+| 789     | _tribe_ticket_global_stock_level | 75         |
| 345     | _tec_tickets_commerce_event      | 789        |
| 345     | _global_stock_mode               | global     |
| 345     | _manage_stock                    | yes        |
-| 345     | _tribe_ticket_capacity           | 100        |
+| 345     | _tribe_ticket_capacity           | 85         |
-| 345     | _stock                           | 93         |
+| 345     | _stock                           | 78         |
| 123     | _tec_tickets_commerce_event      | 789        |
| 123     | _global_stock_mode               | capped     |
| 123     | _manage_stock                    | yes        |
-| 123     | _tribe_ticket_capacity           | 50         |
+| 123     | _tribe_ticket_capacity           | 35         |
-| 123     | _stock                           | 47         |
+| 123     | _stock                           | 32         |
| 456     | _tec_tickets_commerce_event      | 789        |
| 456     | _global_stock_mode               | own        |
| 456     | _manage_stock                    | yes        |
| 456     | _tribe_ticket_capacity           | 20         |
| 456     | _stock                           | 17         |
| 567     | _tec_tickets_commerce_event      | 789        |
| 567     | _manage_stock                    | no         |
```

1 VIP, 2 Adult and 2 Child tickets are refunded; the meta values will change accordingly:

```diff
| post_id | meta_key                         | meta_value |
|---------|----------------------------------|------------|
| 789     | _tribe_ticket_use_global_stock   | 1          |
| 789     | _tribe_ticket_capacity           | 85         |
-| 789     | _tribe_ticket_global_stock_level | 75         |
+| 789     | _tribe_ticket_global_stock_level | 79         |
| 345     | _tec_tickets_commerce_event      | 789        |
| 345     | _global_stock_mode               | global     |
| 345     | _manage_stock                    | yes        |
| 345     | _tribe_ticket_capacity           | 85         |
-| 345     | _stock                           | 78         |
+| 345     | _stock                           | 80         |
| 123     | _tec_tickets_commerce_event      | 789        |
| 123     | _global_stock_mode               | capped     |
| 123     | _manage_stock                    | yes        |
| 123     | _tribe_ticket_capacity           | 35         |
-| 123     | _stock                           | 32         |
+| 123     | _stock                           | 34         |
| 456     | _tec_tickets_commerce_event      | 789        |
| 456     | _global_stock_mode               | own        |
| 456     | _manage_stock                    | yes        |
| 456     | _tribe_ticket_capacity           | 20         |
-| 456     | _stock                           | 17         |
+| 456     | _stock                           | 18         |
| 567     | _tec_tickets_commerce_event      | 789        |
| 567     | _manage_stock                    | no         |
```