# Flexible Ticktets for Event Tickets - Capacity

The Flexible Tickets feature updates the way Capacity is modeled and handled by leverating the power of custom tables.

Conceptually, there are no changes from what users are used to, but the implementation is different.

This document will not cover the custom tables' structure, to know more about that, reference
the [Custom tables structure](custom-tables-structure.md) document.

The implementation described below will only apply, currently, to the new ticket types provided by the feature, and not
to the existing ones.

> Note: the custom tables' schema has been built around the assumption that capacities can either have a parent or not
> have one and that the parent-child relationship depth will not exceed 2 levels.

## A recap about capacity

This is better understood using the "shared" and "capped" capacity scenarios as an example.
The example is not specific to a type of ticket introduced by the feature, but it is a good way to understand the
concepts.

### Shared capacity

A theatre wants to sell tickets for a play, and the theatre has a capacity of 100 seats.
The theatre wants to sell tickets for 2 types of viewers: adults and children.
There will be 2 tickets on sale: one for adults and one for children.
Each ticket can sell up to 100 seats, but the total number of seats sold for both tickets cannot exceed 100: the theatre
simply cannot seat more that 100 people.

In this scenario, the theatre has a "shared" capacity of 100 seats, and the tickets are not "capped": the theatre is
fine selling 100 Adult tickets and 0 Child tickets or 0 Adult tickets and 100 Child tickets.

### Capped capacity

Keeping in mind the same scenario above, the theatre might want to puta cap on the number of tickets sold for each type
of viewer.
E.g. a play might be more suited to children and the theatre would like to sell at most 50 Adult tickets.
The capacity for the Adult ticket would be "capped" at 50 seats.
The capacity for the Child ticket not be "capped".
The capacity shared between the two tickets would still be 100 seats.

### Shared, capped and different capacities

After some renovation work, the theatre decides to add a new type of ticket: "VIP".
The VIP ticket will be sold at a higher price than the other tickets, and will be sold in a separate section of the
theatre; at most 20 seats will be available for the VIP ticket.
The capacity for the VIP ticket will not be shared with the other tickets: the VIP ticket has its own, not shared
capacity.

### Unlimited capacity

During the renovation work, the theatre installed cameras in the theatre and decided to live-stream the play to anyone
that cannot, or does not want to, attend the play in person.
The theatre will sell tickets for the live-streaming, and the capacity for those tickets will be unlimited as there is
no hard limit on the number of people that can watch the play.
This new ticket, called "Live-stream", will have its own, unlimited, capacity.

## Capacity relationships in the custom tables and SQL queries

The under-the-hood change introduced by the Flexible Tickets feature is the introduction of the concept of Capacity
relationships and, in particular, the parent-child relationship between capacities.

With the example above fresh in mind, it's easier to understand how Capacity and Capacity Groups are modeled in the
custom tables.

Ignoring the concern of how tickets are modeled, there are 2 tables that are relevant to the discussion:

* the `capacities` table (see [Custom tables structure](custom-tables-structure.md#capacities) for more details)
* the `capacities_relationships` table (
  see [Custom tables structure](custom-tables-structure.md#capacities-relationships) for more details)

At the start these are the entities at play (no pun intended):

* The play is an Event (a custom post type) with an ID of `789` and a capacity of `100` seats
* The Adult ticket has an ID of `123`, a capped shared capacity of `50` seats
* The Child ticket has an ID of `345`, an uncapped shared capacity of `100` seats
* The VIP ticket has an ID of `456`, a capped own capacity of `20` seats
* The Live-stream ticket has an ID of `567`, an unlimited own capacity

This is a snapshot of the `capacities_relationships` table (the `id` column of the table is not shown as it's not
relevant, the `object_id` entry would only contain the object ID):

| capacity_id | parent_capacity_id | object_id       |
|-------------|--------------------|-----------------|
| 1           | 0                  | 789 (Event)     |
| 2           | 1                  | 123 (Adult)     |
| 1           | 0                  | 345 (Child)     |
| 3           | 0                  | 456 (VIP)       |
| 4           | 0                  | 567 (Livestrem) |

And the `capacities` table at the start of this example:

| id  | initial_value | current_value | mode      |
|-----|---------------|---------------|-----------|
| 1   | 100           | 100           | shared    |
| 2   | 50            | 50            | capped    |
| 3   | 20            | 20            | own       |
| 4   | -1            | -1            | unlimited |

First a Customer wants to purchase 2 Adult and 1 Child ticket.

To fetch the current capacity for the Adult ticket the code will run this query:

```sql
SELECT MIN(current_value)
FROM `capacities`
WHERE `id` IN (
	SELECT capacity_id UNION SELECT parent_capacity_id # Will produce the couple [2,1].
	FROM `capacities_relationships`
	WHERE `object_id` = 123
)
```

The query will initially return `50` for the Adult ticket. This query is the same for all the tickets and will not be
repeated: the only thing that changes is the `object_id` value.

After the purchase of 2 Adult tickets, the `capacities` table will be updated using this query:

```sql
UPDATE `capacities`
SET `current_value` = `current_value` - 2
WHERE `id` IN (
	SELECT capacity_id UNION SELECT parent_capacity_id # Will produce the couple [2,1].
	FROM `capacities_relationships`
	WHERE `object_id` = 123
)
```

A similar query will be run for the Child ticket:

```sql
UPDATE `capacities`
SET `current_value` = `current_value` - 1
WHERE `id` IN (
	SELECT capacity_id UNION SELECT parent_capacity_id # Will produce the couple [1,0].
	FROM `capacities_relationships`
	WHERE `object_id` = 345
)
```

The `capacities` table will now look like this:

| id  | initial_value | current_value | mode      |
|-----|---------------|---------------|-----------|
| 1   | 100           | 97            | shared    |
| 2   | 50            | 48            | capped    |
| 3   | 20            | 20            | own       |
| 4   | -1            | -1            | unlimited |

Then a Customer purchases 3 VIP tickets.

The query to get the current capacity of the VIP ticket will be:

```sql
UPDATE `capacities`
SET `current_value` = `current_value` - 3
WHERE `id` IN (
	SELECT capacity_id UNION SELECT parent_capacity_id # Will produce the couple [3,0].
	FROM `capacities_relationships`
	WHERE `object_id` = 456
)
```

And the `capacities` table will look like this:

| id  | initial_value | current_value | mode      |
|-----|---------------|---------------|-----------|
| 1   | 100           | 97            | shared    |
| 2   | 50            | 48            | capped    |
| 3   | 20            | 17            | own       |
| 4   | -1            | -1            | unlimited |

Then a Customer purchases 1 Live-stream ticket; the `capacities` table will not change.
