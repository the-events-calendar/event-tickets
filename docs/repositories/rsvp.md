# RSVP Repository API Documentation

**Version**: Phase 1 - Foundation
**Last Updated**: 2025-11-04
**Since**: Event Tickets 4.10.6

## Table of Contents

1. [Overview](#overview)
   - [What is the Repository Pattern?](#what-is-the-repository-pattern)
   - [Why Use Repositories?](#why-use-repositories)
   - [Benefits](#benefits)
2. [Ticket Repository](#ticket-repository)
   - [Methods](#ticket-methods)
     - [adjust_sales()](#adjust_sales)
     - [get_field()](#get_field-ticket)
     - [get_event_id()](#get_event_id)
     - [duplicate()](#duplicate)
   - [Field Aliases](#ticket-field-aliases)
3. [Attendee Repository](#attendee-repository)
   - [Methods](#attendee-methods)
     - [get_field()](#get_field-attendee)
     - [bulk_update()](#bulk_update)
     - [get_status_counts()](#get_status_counts)
   - [Field Aliases](#attendee-field-aliases)
4. [Migration Guide](#migration-guide)
   - [Pattern 1: Ticket Creation](#pattern-1-ticket-creation)
   - [Pattern 2: Ticket Retrieval](#pattern-2-ticket-retrieval)
   - [Pattern 3: Stock Management](#pattern-3-stock-management-atomic)
   - [Pattern 4: Attendee Meta Fields](#pattern-4-attendee-meta-fields)
   - [Pattern 5: Check-in](#pattern-5-check-in)
   - [Pattern 6: Bulk Updates](#pattern-6-bulk-updates)
   - [Pattern 7: Field-Specific Lookups](#pattern-7-field-specific-lookups)
5. [Best Practices](#best-practices)
6. [Event Tickets Plus Extensions](#event-tickets-plus-extensions)

---

## Overview

### What is the Repository Pattern?

The Repository pattern provides an abstraction layer between your application logic and data storage. Instead of directly calling WordPress functions like `get_post_meta()`, `update_post_meta()`, or `wp_insert_post()`, you interact with a repository object that handles all data access operations.

### Why Use Repositories?

**Before Repositories:**
```php
// Scattered throughout codebase:
$price = get_post_meta( $ticket_id, '_price', true );
$stock = get_post_meta( $ticket_id, '_stock', true );
$sales = get_post_meta( $ticket_id, 'total_sales', true );
update_post_meta( $ticket_id, 'total_sales', $sales + 1 );
update_post_meta( $ticket_id, '_stock', $stock - 1 );
```

**With Repositories:**
```php
// Centralized, consistent API:
$repository = tribe_tickets( 'rsvp' );
$price = $repository->get_field( $ticket_id, 'price' );
$new_sales = $repository->adjust_sales( $ticket_id, 1 );
```

### Benefits

1. **Single Source of Truth** - All RSVP data access goes through one well-tested class
2. **Atomic Operations** - Methods like `adjust_sales()` prevent race conditions during concurrent requests
3. **Field Aliases** - Use semantic names (`price`, `stock`) instead of meta keys (`_price`, `_stock`)
4. **Better Testing** - Repositories are easy to mock and test
5. **Performance** - Repository methods can optimize queries and implement caching
6. **Future-Proof** - Makes migrating to custom tables straightforward
7. **Type Safety** - Methods have clear parameter and return types
8. **Error Handling** - Centralized validation and error handling

---

## Ticket Repository

The RSVP Ticket Repository (`Tribe__Tickets__Repositories__Ticket__RSVP`) handles all data operations for RSVP ticket posts.

**Getting the Repository:**
```php
$repository = tribe_tickets( 'rsvp' );
// Or via container:
$repository = tribe( 'tickets.ticket-repository.rsvp' );
```

---

### Ticket Methods

#### adjust_sales()

Atomically adjust ticket sales and stock counts. This method performs atomic read-modify-write operations to prevent race conditions during concurrent ticket purchases.

**Signature:**
```php
public function adjust_sales( int $ticket_id, int $delta ): int|false
```

**Parameters:**
- `$ticket_id` (int) - The ticket post ID
- `$delta` (int) - Change in sales count
  - Positive = increase sales (decrease stock)
  - Negative = decrease sales (increase stock, e.g., refunds)

**Returns:**
- `int` - New sales count on success
- `false` - On failure (ticket not found or database error)

**Examples:**

Increase sales by 2 (e.g., user purchases 2 tickets):
```php
$repository = tribe_tickets( 'rsvp' );
$new_sales = $repository->adjust_sales( 123, 2 );

if ( false === $new_sales ) {
    // Handle error
    wp_die( 'Failed to adjust sales' );
}

echo "New sales count: $new_sales";
```

Decrease sales by 1 (e.g., refund):
```php
$repository = tribe_tickets( 'rsvp' );
$new_sales = $repository->adjust_sales( 123, -1 );
```

**Use Cases:**
- Processing ticket purchases
- Handling refunds/cancellations
- Managing inventory during checkout
- Any operation that needs atomic stock updates

**Important Notes:**
- This method is **atomic** - both sales and stock are updated in a single transaction
- Prevents race conditions that could lead to overselling
- Sales count cannot go below 0 (clamped to minimum of 0)
- Stock count cannot go below 0 (clamped to minimum of 0)
- Cache is automatically cleared after updates

**Implementation Details:**
```php
// Atomic UPDATE prevents race conditions
UPDATE wp_postmeta
SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + $delta)
WHERE post_id = $ticket_id AND meta_key = 'total_sales'
```

---

#### get_field() {#get_field-ticket}

Get a single field value without loading the full ticket object. Useful for quick lookups when you only need one field.

**Signature:**
```php
public function get_field( int $ticket_id, string $field ): mixed
```

**Parameters:**
- `$ticket_id` (int) - The ticket post ID
- `$field` (string) - Field name (supports aliases like 'price', 'event_id', 'stock')

**Returns:**
- `mixed` - Field value on success
- `null` - If ticket or field not found

**Examples:**

Get ticket price:
```php
$repository = tribe_tickets( 'rsvp' );
$price = $repository->get_field( 123, 'price' );

if ( null !== $price ) {
    echo "Ticket price: $" . number_format( $price, 2 );
}
```

Get multiple fields:
```php
$repository = tribe_tickets( 'rsvp' );
$event_id = $repository->get_field( 123, 'event_id' );
$stock = $repository->get_field( 123, 'stock' );
$sales = $repository->get_field( 123, 'sales' );

echo "Event: $event_id, Available: $stock, Sold: $sales";
```

Get capacity settings:
```php
$repository = tribe_tickets( 'rsvp' );
$capacity = $repository->get_field( 123, 'capacity' );
$global_stock_mode = $repository->get_field( 123, 'global_stock_mode' );

if ( 'global' === $global_stock_mode ) {
    $global_cap = $repository->get_field( 123, 'global_stock_cap' );
    echo "Using global stock: $global_cap";
} else {
    echo "Individual capacity: $capacity";
}
```

**Use Cases:**
- Quick field lookups without loading full object
- Checking specific values in conditionals
- Building custom queries or reports
- Performance optimization (only fetch what you need)

---

#### get_event_id()

Get the event ID associated with a ticket. This is a convenience method that wraps `get_field()` with type casting.

**Signature:**
```php
public function get_event_id( int $ticket_id ): int|false
```

**Parameters:**
- `$ticket_id` (int) - The ticket post ID

**Returns:**
- `int` - Event post ID on success
- `false` - If ticket not found or has no event association

**Examples:**

Basic usage:
```php
$repository = tribe_tickets( 'rsvp' );
$event_id = $repository->get_event_id( 123 );

if ( false !== $event_id ) {
    $event = get_post( $event_id );
    echo "Ticket is for: " . $event->post_title;
}
```

Validate ticket belongs to specific event:
```php
$repository = tribe_tickets( 'rsvp' );
$ticket_event_id = $repository->get_event_id( $ticket_id );

if ( $ticket_event_id !== $expected_event_id ) {
    wp_die( 'This ticket does not belong to this event' );
}
```

**Use Cases:**
- Validating ticket-event relationships
- Building event-specific ticket lists
- Permission checks
- Linking tickets to events in custom queries

---

#### duplicate()

Duplicate an existing ticket with optional field overrides. All ticket data and metadata are copied, except sales count.

**Signature:**
```php
public function duplicate( int $ticket_id, array $overrides = [] ): int|false
```

**Parameters:**
- `$ticket_id` (int) - ID of ticket to duplicate
- `$overrides` (array) - Optional field overrides using field aliases or post fields
  - Supports any ticket field alias (see [Field Aliases](#ticket-field-aliases))
  - Supports post fields: `title`, `description`, `status`, etc.

**Returns:**
- `int` - New ticket post ID on success
- `false` - If original ticket not found or duplication fails

**Examples:**

Simple duplication:
```php
$repository = tribe_tickets( 'rsvp' );
$new_ticket_id = $repository->duplicate( 123 );

if ( false !== $new_ticket_id ) {
    echo "Created duplicate ticket: $new_ticket_id";
}
```

Duplicate with new name and capacity:
```php
$repository = tribe_tickets( 'rsvp' );
$new_ticket_id = $repository->duplicate( 123, [
    'title'    => 'VIP Ticket (Copy)',
    'capacity' => 50,
    'price'    => 99.00
] );
```

Duplicate for different event:
```php
$repository = tribe_tickets( 'rsvp' );
$new_ticket_id = $repository->duplicate( 123, [
    'event_id' => 456,  // Attach to different event
    'title'    => 'General Admission - Conference 2024'
] );
```

Reset stock for new season:
```php
$repository = tribe_tickets( 'rsvp' );
$new_ticket_id = $repository->duplicate( 123, [
    'title' => 'Early Bird - 2025',
    'stock' => 100,
    'start_date' => '2025-01-01 00:00:00',
    'end_date'   => '2025-01-31 23:59:59'
] );
```

**Use Cases:**
- Creating ticket templates
- Copying tickets between events
- Creating ticket variations (VIP, Early Bird, etc.)
- Seasonal ticket resets
- Testing scenarios

**Important Notes:**
- Sales count (`sales`) is **not** copied (always starts at 0)
- All other fields are copied by default
- Overrides use field aliases for convenience
- Original ticket is unchanged
- Post meta is copied from the source ticket

---

### Ticket Field Aliases

Field aliases allow you to use semantic names instead of meta key names when working with ticket data.

| Alias | Meta Key | Description | Example Value |
|-------|----------|-------------|---------------|
| `event_id` | `_tribe_rsvp_for_event` | Event post ID this ticket belongs to | `456` |
| `price` | `_price` | Ticket price | `25.00` |
| `stock` | `_stock` | Available ticket quantity | `10` |
| `sales` | `total_sales` | Number of tickets sold | `15` |
| `manage_stock` | `_manage_stock` | Whether stock management is enabled | `yes` or `no` |
| `start_date` | `_ticket_start_date` | When ticket sales start | `2025-01-01 00:00:00` |
| `end_date` | `_ticket_end_date` | When ticket sales end | `2025-12-31 23:59:59` |
| `show_description` | `_tribe_ticket_show_description` | Show description on ticket form | `yes` or `no` |
| `show_not_going` | `_tribe_rsvp_show_not_going` | Show "Not Going" option | `yes` or `no` |
| `capacity` | `_tribe_ticket_capacity` | Maximum ticket capacity | `100` |
| `global_stock_mode` | `_global_stock_mode` | Stock mode (own/global/capped) | `own`, `global`, or `capped` |
| `global_stock_cap` | `_global_stock_cap` | Cap when using global stock | `50` |

**Usage Examples:**

Using aliases with `get_field()`:
```php
$repository = tribe_tickets( 'rsvp' );

// Instead of: get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );
$event_id = $repository->get_field( $ticket_id, 'event_id' );

// Instead of: get_post_meta( $ticket_id, '_price', true );
$price = $repository->get_field( $ticket_id, 'price' );

// Instead of: get_post_meta( $ticket_id, 'total_sales', true );
$sales = $repository->get_field( $ticket_id, 'sales' );
```

Using aliases with `duplicate()`:
```php
$repository = tribe_tickets( 'rsvp' );
$new_ticket = $repository->duplicate( 123, [
    'event_id'   => 789,  // Use alias instead of '_tribe_rsvp_for_event'
    'price'      => 30.00,  // Use alias instead of '_price'
    'stock'      => 50,  // Use alias instead of '_stock'
    'capacity'   => 50,  // Use alias instead of '_tribe_ticket_capacity'
] );
```

Using aliases with repository `set_args()`:
```php
$repository = tribe_tickets( 'rsvp' );
$ticket_id = $repository->set_args( [
    'title'             => 'VIP Access',
    'description'       => 'Includes backstage pass',
    'event_id'          => 456,
    'price'             => 99.00,
    'stock'             => 25,
    'capacity'          => 25,
    'manage_stock'      => 'yes',
    'start_date'        => '2025-01-01 00:00:00',
    'end_date'          => '2025-12-31 23:59:59',
    'show_description'  => 'yes',
    'show_not_going'    => 'no',
    'global_stock_mode' => 'own'
] )->create();
```

---

## Attendee Repository

The RSVP Attendee Repository (`Tribe__Tickets__Repositories__Attendee__RSVP`) handles all data operations for RSVP attendee posts.

**Getting the Repository:**
```php
$repository = tribe_attendees( 'rsvp' );
// Or via container:
$repository = tribe( 'tickets.attendee-repository.rsvp' );
```

---

### Attendee Methods

#### get_field() {#get_field-attendee}

Get a single field value from an attendee without loading the full attendee object.

**Signature:**
```php
public function get_field( int $attendee_id, string $field ): mixed
```

**Parameters:**
- `$attendee_id` (int) - The attendee post ID
- `$field` (string) - Field name (supports aliases like 'email', 'attendee_status', 'full_name')

**Returns:**
- `mixed` - Field value on success
- `null` - If attendee or field not found

**Examples:**

Get attendee status:
```php
$repository = tribe_attendees( 'rsvp' );
$status = $repository->get_field( 789, 'attendee_status' );

if ( 'yes' === $status ) {
    echo "Attendee is coming";
} elseif ( 'no' === $status ) {
    echo "Attendee declined";
}
```

Get attendee contact info:
```php
$repository = tribe_attendees( 'rsvp' );
$name = $repository->get_field( 789, 'full_name' );
$email = $repository->get_field( 789, 'email' );

echo "Attendee: $name ($email)";
```

Get attendee's ticket and event:
```php
$repository = tribe_attendees( 'rsvp' );
$ticket_id = $repository->get_field( 789, 'ticket_id' );
$event_id = $repository->get_field( 789, 'event_id' );
$order_id = $repository->get_field( 789, 'order_id' );

echo "Attendee #789 - Ticket: $ticket_id, Event: $event_id, Order: $order_id";
```

**Use Cases:**
- Quick attendee data lookups
- Building attendee lists
- Email notifications
- Check-in validation
- Custom reports

---

#### bulk_update()

Update multiple attendees with the same field values in one method call. More efficient than calling `update()` in a loop.

**Signature:**
```php
public function bulk_update( array $attendee_ids, array $updates ): array
```

**Parameters:**
- `$attendee_ids` (array) - Array of attendee post IDs to update
- `$updates` (array) - Associative array of field names and values to update
  - Supports field aliases (see [Attendee Field Aliases](#attendee-field-aliases))

**Returns:**
- `array` - Results indexed by attendee ID
  - `true` = update succeeded
  - `false` = update failed (attendee not found or error)

**Examples:**

Change status for multiple attendees:
```php
$repository = tribe_attendees( 'rsvp' );
$attendee_ids = [ 789, 790, 791 ];

$results = $repository->bulk_update( $attendee_ids, [
    'attendee_status' => 'yes'
] );

foreach ( $results as $attendee_id => $success ) {
    if ( $success ) {
        echo "Updated attendee $attendee_id\n";
    } else {
        echo "Failed to update attendee $attendee_id\n";
    }
}

// Output:
// Updated attendee 789
// Updated attendee 790
// Failed to update attendee 791
```

Mark deleted product on attendees:
```php
$repository = tribe_attendees( 'rsvp' );
$attendees = $this->get_attendees_by_ticket_id( $ticket_id );
$attendee_ids = wp_list_pluck( $attendees, 'attendee_id' );

$results = $repository->bulk_update( $attendee_ids, [
    'deleted_product' => $ticket_title
] );
```

Bulk opt-out attendees:
```php
$repository = tribe_attendees( 'rsvp' );
$attendee_ids = [ 101, 102, 103, 104 ];

$results = $repository->bulk_update( $attendee_ids, [
    'optout' => '1'
] );

$success_count = count( array_filter( $results ) );
echo "Opted out $success_count attendees";
```

**Use Cases:**
- Bulk status changes
- Mass email opt-out
- Updating attendees when ticket is deleted
- Batch operations during imports
- Administrative bulk actions

**Important Notes:**
- Each attendee is updated individually (not a single SQL query)
- Updates are processed in order
- If one fails, others continue
- Returns detailed results for each attendee
- Triggers standard update actions/filters for each attendee

---

#### get_status_counts()

Get attendee counts grouped by RSVP status for an event. Useful for displaying attendance statistics.

**Signature:**
```php
public function get_status_counts( int $event_id ): array
```

**Parameters:**
- `$event_id` (int) - The event post ID

**Returns:**
- `array` - Associative array with status as key and count as value
  - Keys: `'yes'`, `'no'` (and any custom statuses)
  - Values: Integer counts

**Examples:**

Display attendance summary:
```php
$repository = tribe_attendees( 'rsvp' );
$counts = $repository->get_status_counts( 456 );

echo "Going: " . ( $counts['yes'] ?? 0 ) . "\n";
echo "Not Going: " . ( $counts['no'] ?? 0 ) . "\n";

// Output:
// Going: 10
// Not Going: 5
```

Calculate attendance percentage:
```php
$repository = tribe_attendees( 'rsvp' );
$counts = $repository->get_status_counts( 456 );

$yes_count = $counts['yes'] ?? 0;
$total = array_sum( $counts );

if ( $total > 0 ) {
    $percentage = ( $yes_count / $total ) * 100;
    echo "Attendance rate: " . round( $percentage, 1 ) . "%";
}
```

Show status breakdown in admin:
```php
$repository = tribe_attendees( 'rsvp' );
$counts = $repository->get_status_counts( $event_id );

echo "<ul>";
foreach ( $counts as $status => $count ) {
    $label = ucfirst( $status );
    echo "<li>$label: $count</li>";
}
echo "</ul>";

// Output:
// <ul>
//   <li>Yes: 45</li>
//   <li>No: 12</li>
// </ul>
```

**Use Cases:**
- Displaying attendance statistics
- Event capacity planning
- Admin dashboard widgets
- Email summaries to organizers
- Reports and analytics

**Performance:**
- Uses optimized SQL query with JOINs and GROUP BY
- Returns results in a single query
- No post object loading overhead

---

### Attendee Field Aliases

Field aliases allow you to use semantic names instead of meta key names when working with attendee data.

| Alias | Meta Key | Description | Example Value |
|-------|----------|-------------|---------------|
| `ticket_id` | `_tribe_rsvp_product` | Ticket post ID | `123` |
| `event_id` | `_tribe_rsvp_event` | Event post ID | `456` |
| `post_id` | `_tribe_rsvp_event` | Event post ID (alias for event_id) | `456` |
| `security_code` | `_tribe_rsvp_security_code` | Unique security/QR code | `abc123xyz` |
| `order_id` | `_tribe_rsvp_order` | RSVP order hash | `5f8a9b2c...` |
| `optout` | `_tribe_rsvp_optout` | Email opt-out status | `1` or `0` |
| `user_id` | `_tribe_rsvp_attendee_user_id` | WordPress user ID of attendee | `42` |
| `price_paid` | `_tribe_rsvp_attendee_price_paid` | Price paid (usually 0 for RSVP) | `0.00` |
| `full_name` | `_tribe_rsvp_attendee_full_name` | Full name of attendee | `John Doe` |
| `email` | `_tribe_rsvp_attendee_email` | Email address | `john@example.com` |
| `attendee_status` | `_tribe_rsvp_status` | RSVP response | `yes`, `no` |

**Usage Examples:**

Using aliases with `get_field()`:
```php
$repository = tribe_attendees( 'rsvp' );

// Instead of: get_post_meta( $attendee_id, '_tribe_rsvp_status', true );
$status = $repository->get_field( $attendee_id, 'attendee_status' );

// Instead of: get_post_meta( $attendee_id, '_tribe_rsvp_attendee_email', true );
$email = $repository->get_field( $attendee_id, 'email' );

// Instead of: get_post_meta( $attendee_id, '_tribe_rsvp_product', true );
$ticket_id = $repository->get_field( $attendee_id, 'ticket_id' );
```

Using aliases with `bulk_update()`:
```php
$repository = tribe_attendees( 'rsvp' );
$results = $repository->bulk_update(
    [ 789, 790, 791 ],
    [
        'attendee_status' => 'yes',  // Use alias instead of '_tribe_rsvp_status'
        'optout'          => '0',    // Use alias instead of '_tribe_rsvp_optout'
    ]
);
```

Using aliases with repository `set_args()`:
```php
$repository = tribe_attendees( 'rsvp' );
$attendee_id = $repository->set_args( [
    'ticket_id'       => 123,
    'event_id'        => 456,
    'order_id'        => $order_hash,
    'attendee_status' => 'yes',
    'full_name'       => 'Jane Smith',
    'email'           => 'jane@example.com',
    'user_id'         => get_current_user_id(),
    'security_code'   => wp_generate_password( 20, false ),
    'optout'          => '0',
    'price_paid'      => '0.00'
] )->create();
```

---

## Migration Guide

This guide shows how to migrate from direct WordPress database access to the repository pattern.

### Pattern 1: Ticket Creation

**Before:**
```php
// RSVP.php:1568 - Old approach with 20+ function calls
$args = [
    'post_status'  => 'publish',
    'post_type'    => $this->ticket_object,
    'post_author'  => get_current_user_id(),
    'post_excerpt' => $ticket->description,
    'post_title'   => $ticket->name,
    'menu_order'   => $ticket->menu_order ?? -1,
];

$ticket->ID = wp_insert_post( $args );
add_post_meta( $ticket->ID, $this->get_event_key(), $post_id );
update_post_meta( $ticket->ID, '_price', $ticket->price );
update_post_meta( $ticket->ID, '_stock', $ticket->stock );
update_post_meta( $ticket->ID, '_tribe_ticket_capacity', $ticket->capacity );
update_post_meta( $ticket->ID, 'total_sales', 0 );
update_post_meta( $ticket->ID, '_manage_stock', 'yes' );
update_post_meta( $ticket->ID, '_ticket_start_date', $start_date );
update_post_meta( $ticket->ID, '_ticket_end_date', $end_date );
// ... 15+ more meta updates
```

**After:**
```php
// Single repository call with all data
$repository = tribe_tickets( 'rsvp' );

$ticket_id = $repository->set_args( [
    'title'        => $ticket->name,
    'description'  => $ticket->description,
    'event_id'     => $post_id,
    'price'        => $ticket->price,
    'stock'        => $ticket->stock,
    'capacity'     => $ticket->capacity,
    'sales'        => 0,
    'manage_stock' => 'yes',
    'start_date'   => $start_date,
    'end_date'     => $end_date,
    'menu_order'   => $ticket->menu_order ?? -1,
] )->create()->ID;
```

**Benefits:**
- 20+ function calls reduced to 1
- Atomic operation (all or nothing)
- Field validation in one place
- Consistent field aliases
- Easy to mock in tests
- Better error handling

---

### Pattern 2: Ticket Retrieval

**Before:**
```php
// RSVP.php:1921 - Manual data hydration with 10+ get_post_meta calls
$product = get_post( $ticket_id );

if ( ! $product ) {
    return null;
}

$return = new Tribe__Tickets__Ticket_Object();
$return->description = $product->post_excerpt;
$return->ID = $ticket_id;
$return->name = $product->post_title;
$return->price = get_post_meta( $ticket_id, '_price', true );
$return->stock = (int) get_post_meta( $ticket_id, '_stock', true );
$return->qty_sold = (int) get_post_meta( $ticket_id, 'total_sales', true );
$return->start_date = get_post_meta( $ticket_id, '_ticket_start_date', true );
$return->end_date = get_post_meta( $ticket_id, '_ticket_end_date', true );
$return->capacity = get_post_meta( $ticket_id, '_tribe_ticket_capacity', true );
// ... 5+ more get_post_meta() calls
```

**After:**
```php
// Repository returns fully hydrated object
$repository = tribe_tickets( 'rsvp' );
$ticket = $repository->by( 'id', $ticket_id )->first();

// All fields already populated in $ticket object
// No need for manual hydration
```

**Benefits:**
- 11+ function calls reduced to 1
- Consistent caching strategy
- Repository handles data hydration automatically
- Easier to optimize queries
- Cleaner, more maintainable code

---

### Pattern 3: Stock Management (Atomic)

**Before (Race Condition):**
```php
// RSVP.php:2950 - NOT ATOMIC - Vulnerable to race conditions!
$sales = (int) get_post_meta( $ticket_id, 'total_sales', true ) + $quantity;  // ← READ
update_post_meta( $ticket_id, 'total_sales', $sales );  // ← WRITE (not atomic!)

$stock = (int) get_post_meta( $ticket_id, '_stock', true ) - $quantity;  // ← READ
$stock = max( $stock, 0 );
update_post_meta( $ticket_id, '_stock', $stock );  // ← WRITE (not atomic!)

// Problem: If two users purchase at the same time:
// User A reads stock=10
// User B reads stock=10
// User A writes stock=9
// User B writes stock=9  <-- Should be 8! Oversold by 1.
```

**After (Atomic):**
```php
// Single atomic SQL UPDATE - prevents race conditions
$repository = tribe_tickets( 'rsvp' );
$new_sales = $repository->adjust_sales( $ticket_id, $quantity );

if ( false === $new_sales ) {
    // Handle error
    wp_die( 'Failed to adjust ticket inventory' );
}

// Both sales and stock updated atomically
// No race conditions possible
```

**Benefits:**
- **Atomic operation** prevents overselling
- Single database query instead of 4
- Thread-safe for concurrent requests
- Guaranteed data consistency
- Proper error handling
- Automatic cache invalidation

**SQL Implementation:**
```sql
-- Atomic UPDATE with GREATEST() to prevent negative values
UPDATE wp_postmeta
SET meta_value = GREATEST(0, CAST(meta_value AS SIGNED) + $delta)
WHERE post_id = $ticket_id AND meta_key = 'total_sales'
```

---

### Pattern 4: Attendee Meta Fields

**Before:**
```php
// event-tickets-plus/src/Tribe/Meta/RSVP.php:70
$attendee_meta = $meta[ $product_id ][ $order_attendee_id ];

update_post_meta(
    $attendee_id,
    Tribe__Tickets_Plus__Meta::META_KEY,
    $attendee_meta_to_save
);
```

**After:**
```php
$attendee_meta = $meta[ $product_id ][ $order_attendee_id ];

$repository = tribe_attendees( 'rsvp' );
$repository->by( 'id', $attendee_id )
           ->set( 'attendee_meta', $attendee_meta_to_save )
           ->save();
```

**Benefits:**
- Uses existing field alias from ET+ decorator
- Consistent with ET core patterns
- Ready for custom table migration
- Maintains action hooks
- Better testability

---

### Pattern 5: Check-in

**Before:**
```php
// Tickets.php:1077 - Multiple update_post_meta calls
update_post_meta( $attendee_id, $this->checkin_key, 1 );
update_post_meta( $attendee_id, '_tribe_qr_status', 1 );

$checkin_details = [
    'source' => 'qr',
    'date'   => current_time( 'mysql' ),
    'author' => get_current_user_id(),
];
update_post_meta( $attendee_id, $this->checkin_key . '_details', $checkin_details );
```

**After:**
```php
// Single save() call with all updates
$repository = tribe_attendees( 'rsvp' );

$checkin_details = [
    'source' => 'qr',
    'date'   => current_time( 'mysql' ),
    'author' => get_current_user_id(),
];

$repository->by( 'id', $attendee_id )
           ->set( 'check_in', 1 )
           ->set( 'qr_status', 1 )
           ->set( 'check_in_details', $checkin_details )
           ->save();
```

**Benefits:**
- Single `save()` call updates all fields atomically
- Cleaner, more readable code
- Easier to test (mock one method instead of three)
- Repository handles validation
- Maintains backwards compatibility with hooks

---

### Pattern 6: Bulk Updates

**Before:**
```php
// RSVP.php:1708 - Loop with individual updates
$attendees = $this->get_attendees_by_ticket_id( $ticket_id );

foreach ( $attendees as $attendee ) {
    update_post_meta(
        $attendee['attendee_id'],
        $this->deleted_product,
        esc_html( $post_to_delete->post_title )
    );
}
```

**After:**
```php
// Bulk update in single method call
$attendees = $this->get_attendees_by_ticket_id( $ticket_id );
$attendee_ids = wp_list_pluck( $attendees, 'attendee_id' );

$repository = tribe_attendees( 'rsvp' );
$results = $repository->bulk_update( $attendee_ids, [
    'deleted_product' => $post_to_delete->post_title
] );

// Check results
$success_count = count( array_filter( $results ) );
$fail_count = count( $attendee_ids ) - $success_count;
```

**Benefits:**
- Cleaner, more declarative code
- Easier to optimize (could become bulk SQL UPDATE in future)
- Consistent error handling with per-attendee results
- Can be wrapped in transaction
- Better performance at scale

---

### Pattern 7: Field-Specific Lookups

**Before:**
```php
// RSVP.php:2696 - Direct meta access
$post_id = get_post_meta( $product_id, $this->get_event_key(), true );

if ( empty( $post_id ) ) {
    return false;
}
```

**After:**
```php
// Semantic helper method
$repository = tribe_tickets( 'rsvp' );
$post_id = $repository->get_event_id( $product_id );

if ( false === $post_id ) {
    return false;
}

// Or more generic:
$post_id = $repository->get_field( $product_id, 'event_id' );
```

**Benefits:**
- Semantic method name conveys intent
- Field alias support (no meta key knowledge needed)
- Cacheable (repository can implement caching layer)
- Mockable in tests
- Type-safe return value (int or false)
- Consistent error handling

---

## Best Practices

### When to Use get_field() vs Loading Full Object

**Use `get_field()` when:**
- You only need 1-2 specific fields
- Building conditional logic based on single values
- Performance is critical (avoid loading unnecessary data)
- Working in loops where full objects aren't needed

```php
// Good: Only need one field
$repository = tribe_tickets( 'rsvp' );
if ( 'yes' === $repository->get_field( $ticket_id, 'manage_stock' ) ) {
    // Check stock levels
}
```

**Use `by()->first()` when:**
- You need multiple fields from the same ticket/attendee
- Building display/output that requires many fields
- The object will be passed to other functions
- You need the complete WP_Post object

```php
// Good: Need full object
$repository = tribe_tickets( 'rsvp' );
$ticket = $repository->by( 'id', $ticket_id )->first();

echo $ticket->post_title;
echo $ticket->post_excerpt;
echo get_post_meta( $ticket->ID, '_price', true );
```

---

### Importance of Atomic Stock Operations

**Always use `adjust_sales()` for stock changes:**

```php
// ✅ CORRECT - Atomic operation
$repository = tribe_tickets( 'rsvp' );
$new_sales = $repository->adjust_sales( $ticket_id, $quantity );

// ❌ WRONG - Race condition possible
$sales = (int) get_post_meta( $ticket_id, 'total_sales', true );
update_post_meta( $ticket_id, 'total_sales', $sales + $quantity );
```

**Why it matters:**
- Prevents overselling during concurrent purchases
- Maintains data integrity
- Thread-safe for multiple simultaneous requests
- Guaranteed consistency between sales and stock

---

### Error Handling Patterns

**Always check return values:**

```php
$repository = tribe_tickets( 'rsvp' );
$new_sales = $repository->adjust_sales( $ticket_id, 2 );

if ( false === $new_sales ) {
    // Handle error appropriately
    if ( is_admin() ) {
        wp_die( 'Failed to adjust ticket sales. Please try again.' );
    } else {
        wp_send_json_error( [ 'message' => 'Purchase failed' ] );
    }
}

// Success - proceed with purchase
$this->send_confirmation_email( $ticket_id, $new_sales );
```

**Validate data before repository calls:**

```php
$repository = tribe_tickets( 'rsvp' );

// Validate ticket exists
$event_id = $repository->get_event_id( $ticket_id );
if ( false === $event_id ) {
    wp_die( 'Invalid ticket ID' );
}

// Validate event is published
$event = get_post( $event_id );
if ( ! $event || 'publish' !== $event->post_status ) {
    wp_die( 'Event not available' );
}

// Proceed with operation
$new_sales = $repository->adjust_sales( $ticket_id, $quantity );
```

---

### Testing Repository-Based Code

**Mock repositories in unit tests:**

```php
use Tribe__Tickets__Repositories__Ticket__RSVP as RSVP_Repository;

class My_Feature_Test extends \Codeception\TestCase\WPTestCase {

    public function test_purchase_validates_stock() {
        // Create mock repository
        $repository = $this->createMock( RSVP_Repository::class );

        // Set up expectations
        $repository->expects( $this->once() )
                   ->method( 'get_field' )
                   ->with( 123, 'stock' )
                   ->willReturn( 5 );

        $repository->expects( $this->once() )
                   ->method( 'adjust_sales' )
                   ->with( 123, 2 )
                   ->willReturn( 2 );

        // Inject mock
        tribe_singleton( 'tickets.ticket-repository.rsvp', $repository );

        // Test your code
        $result = $this->purchase_tickets( 123, 2 );

        $this->assertTrue( $result );
    }
}
```

**Integration test with real repository:**

```php
class RSVP_Integration_Test extends \Codeception\TestCase\WPTestCase {

    public function test_adjust_sales_updates_stock_atomically() {
        // Create test ticket
        $ticket_id = $this->factory->post->create( [
            'post_type' => 'tribe_rsvp_tickets'
        ] );
        update_post_meta( $ticket_id, '_stock', 10 );
        update_post_meta( $ticket_id, 'total_sales', 0 );

        // Test repository
        $repository = tribe_tickets( 'rsvp' );
        $new_sales = $repository->adjust_sales( $ticket_id, 3 );

        // Verify results
        $this->assertEquals( 3, $new_sales );
        $this->assertEquals( 3, get_post_meta( $ticket_id, 'total_sales', true ) );
        $this->assertEquals( 7, get_post_meta( $ticket_id, '_stock', true ) );
    }
}
```

---

## Event Tickets Plus Extensions

Event Tickets Plus (ETP) extends the base RSVP repositories with additional field aliases for meta field configuration.

### ETP Ticket Repository Decorator

**Location:** `event-tickets-plus/src/Tribe/Repositories/Ticket/RSVP.php`

The ETP decorator adds support for attendee meta fields (custom questions):

```php
namespace Tribe\Tickets\Plus\Repositories\Ticket;

class RSVP extends Tribe__Repository__Decorator {

    public function __construct() {
        // Get base ET repository
        $this->decorated = tribe( 'tickets.ticket-repository.rsvp' );

        // Add ET+ specific field aliases
        $this->decorated->add_update_field_alias( 'meta_fields_config', '_tribe_tickets_meta' );
        $this->decorated->add_update_field_alias( 'meta_enabled', '_tribe_tickets_meta_enabled' );
    }
}
```

### Additional Field Aliases (ET+)

| Alias | Meta Key | Description | Example Value |
|-------|----------|-------------|---------------|
| `meta_fields_config` | `_tribe_tickets_meta` | Serialized array of custom fields config | `[ ['type' => 'text', ...], ... ]` |
| `meta_enabled` | `_tribe_tickets_meta_enabled` | Whether custom fields are enabled | `yes` or `no` |

### Usage Examples

**Get custom field configuration:**
```php
// Only available when Event Tickets Plus is active
$repository = tribe_tickets( 'rsvp' );
$meta_config = $repository->get_field( $ticket_id, 'meta_fields_config' );

if ( $meta_config ) {
    $fields = maybe_unserialize( $meta_config );
    foreach ( $fields as $field ) {
        echo $field['label'] . ": " . $field['type'] . "\n";
    }
}
```

**Enable custom fields on ticket:**
```php
// Only available when Event Tickets Plus is active
$repository = tribe_tickets( 'rsvp' );

$ticket_id = $repository->set_args( [
    'title'              => 'VIP Ticket',
    'event_id'           => 456,
    'price'              => 99.00,
    'meta_enabled'       => 'yes',
    'meta_fields_config' => [
        [
            'type'     => 'text',
            'label'    => 'T-Shirt Size',
            'required' => 'yes',
        ],
        [
            'type'     => 'text',
            'label'    => 'Dietary Restrictions',
            'required' => 'no',
        ],
    ],
] )->create()->ID;
```

**Duplicate ticket with custom fields:**
```php
// Only available when Event Tickets Plus is active
$repository = tribe_tickets( 'rsvp' );

$new_ticket = $repository->duplicate( 123, [
    'title'        => 'Early Bird (Copy)',
    'meta_enabled' => 'yes',  // Preserve custom fields
] );

// meta_fields_config is automatically copied from original
```

---

## Summary

The RSVP Repository API provides a robust, type-safe, and performant abstraction layer for working with RSVP ticket and attendee data. By using repositories instead of direct database access, you get:

- **Atomic operations** that prevent race conditions
- **Field aliases** for semantic, maintainable code
- **Centralized validation** and error handling
- **Better testing** through mockable interfaces
- **Future-proof architecture** ready for custom tables
- **Improved performance** through optimized queries

**Key Takeaways:**
1. Always use `adjust_sales()` for stock changes to prevent overselling
2. Use field aliases instead of meta keys for cleaner code
3. Use `get_field()` for single values, `by()->first()` for full objects
4. Always check return values and handle errors appropriately
5. Mock repositories in unit tests for faster, isolated testing
6. Use `bulk_update()` for efficient mass operations

For questions or issues, please consult the Event Tickets team documentation or reach out on Slack.

---

**Related Documentation:**
- [Event Tickets Repository Base Classes](../repositories/base.md)
- [Custom Table Migration Guide](../migrations/custom-tables.md)
- [Testing Strategies](../testing/repositories.md)

**Version History:**
- **Phase 1 (4.10.6)**: Initial repository implementation with core methods and field aliases
