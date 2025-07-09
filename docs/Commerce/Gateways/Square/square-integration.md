# Square Payment Gateway Integration

## Overview

The Square payment gateway integration enables seamless synchronization between your WordPress-based Tickets and Square's ecosystem, including Point of Sale (POS) systems. This document provides a technical overview of how the integration works, focusing on webhook notifications, inventory synchronization, and POS sales handling.

## Architecture Overview

### Key Components

1. **Gateway** (`Gateway.php`) - Main gateway class implementing payment processing
2. **Webhooks** (`Webhooks.php`, `REST/Webhook_Endpoint.php`) - Handles incoming Square notifications
3. **Items Sync** (`Syncs/Items_Sync.php`) - Syncs events and tickets as Square catalog items
4. **Inventory Sync** (`Syncs/Inventory_Sync.php`) - Manages inventory count synchronization
5. **Order Management** (`Order.php`) - Processes orders from Square including POS sales
6. **WhoDat** (`WhoDat.php`) - The Events Calendar's hosted solution for OAuth and webhook forwarding
7. **Listener** (`Listener.php`) - Listens to local WordPress events and processes them. When appropriate will schedule a background task to sync the change to Square.

## WhoDat: The Events Calendar's Hosted Solution

### What is WhoDat?

WhoDat (`https://whodat.theeventscalendar.com/`) is The Events Calendar's hosted intermediary service that handles:

1. **OAuth Flow Management**
   - Manages Square OAuth authentication without exposing application credentials
   - Handles both sandbox and live Square environments
   - Manages scope updates when Square requires new permissions

2. **Webhook Forwarding**
   - Acts as a stable webhook endpoint for Square
   - Forwards webhooks to customer WordPress sites
   - Adds security layer with signature verification

### How WhoDat Works

#### Customer Onboarding Flow

```mermaid
WordPress Site → WhoDat → Square OAuth → WhoDat → WordPress Site
```

1. WhoDat constructs Square OAuth URL with proper scopes
2. User authorizes on Square
3. Square redirects to WhoDat with authorization code
4. WhoDat exchanges code for tokens and redirects to WordPress
5. WordPress stores encrypted access tokens

#### Webhook Forwarding Flow

```mermaid
Square → WhoDat → Customer WordPress Site
```

1. Square sends webhooks to WhoDat's stable endpoint
2. WhoDat adds `X-WhoDat-Hash` header for verification
3. WhoDat forwards webhook to registered WordPress URL
4. WordPress verifies both WhoDat signature and webhook secret

## How Square Notifies Us About Sales

### Webhook Events and Actions

Square notifies our system through webhooks, with each event triggering specific actions:

#### 1. Order Events (Primary for POS Sales)

**`order.created` / `order.updated`**

- **Action**: Creates or updates local WordPress order
- **Process**:
  - Retrieves full order details from Square API
  - Creates/updates order with line items
  - Marks POS orders with `META_ORDER_CREATED_BY = 'square-pos'`
  - Stores payment and refund IDs
  - Syncs order status (OPEN, COMPLETED, CANCELED)
  - Unschedules any pending order pull actions

#### 2. Payment Events

**`payment.created` / `payment.updated`**

- **Action**: Updates order payment status
- **Process**:
  - Finds order by payment ID
  - Updates payment metadata
  - Changes order status based on payment state
  - Logs payment details for reconciliation

#### 3. Refund Events

**`refund.created` / `refund.updated`**

- **Action**: Processes refunds when status is COMPLETED
- **Process**:
  - Finds order by payment ID
  - Stores original order ID before refund
  - Updates order status to "refunded"
  - Maintains refund audit trail
  - Only processes COMPLETED refunds

#### 4. Inventory Events

**`inventory.count.updated`**

- **Action**: Verifies inventory synchronization
- **Process**:
  - Compares Square count with WordPress
  - If mismatch detected, schedules background sync
  - WordPress inventory overwrites Square after delay
  - Fires `tec_tickets_commerce_square_ticket_out_of_sync` action

#### 5. Customer Events

**`customer.deleted`**

- **Action**: Handles customer data removal
- **Process**:
  - Updates or anonymizes related orders
  - Maintains data compliance

#### 6. OAuth Events

**`oauth.authorization.revoked`**

- **Action**: Cleans up Square connection
- **Process**:
  - Marks disconnection as remote-initiated
  - Clears merchant signup data
  - Stops sync processes

### Webhook Security

- **Dual Authentication**:
  - URL parameter: `tec-tc-key` (64-character secret)
  - Header: `X-WhoDat-Hash` (HMAC signature from WhoDat)
- **Idempotency**: Each event has unique `event_id` to prevent duplicate processing
- **Event Storage**: All events logged to database for audit trail

## Inventory Synchronization

### Source of Truth

**WordPress is the single source of truth for inventory**, not Square. This is a critical architectural decision that ensures WordPress maintains control over ticket availability.

### Two-Phase Sync System

The synchronization works in two coordinated phases:

#### Phase 1: Items Sync (Catalog Creation)

**Purpose**: Creates Square catalog items for events and tickets

**What Gets Synced**:

- **Events** → Square "ITEM" objects with product_type: "EVENT"
- **Tickets** → Square "ITEM_VARIATION" objects (children of event items)

**Process**:

1. Queries events with tickets that haven't been synced
2. Batches up to 100 events at a time
3. Transforms data:
   - Event: Name, description, location settings
   - Ticket: Name, SKU, price, currency, sellable status
4. Sends to Square's `catalog/batch-upsert` endpoint
5. Stores Square IDs for future reference

#### Phase 2: Inventory Sync (Stock Management)

**Purpose**: Manages inventory counts after catalog items exist

**Process**:

1. Automatically triggered when Items Sync completes
2. Queries events that have been item-synced
3. Creates inventory changes based on:
   - Current WordPress stock/capacity
   - Stock mode (global, capped, unlimited)
   - Unlimited tickets = 900,000,000+ in Square
4. Sends to Square's `inventory/changes/batch-create` endpoint

### Sync Triggers

#### WordPress → Square (Primary Direction)

1. **Manual Triggers**
   - Ticket created/updated (`tec_tickets_ticket_upserted`)
   - Stock changes (`tec_tickets_ticket_stock_changed`)
   - Sale start/end dates (`tec_tickets_ticket_start_date_trigger`, `tec_tickets_ticket_end_date_trigger`)
   - Post saved (`save_post`)
   - Post deleted/trashed (`wp_trash_post`, `before_delete_post`)

2. **Automatic Batch Sync**
   - Initial sync when Square first connected
   - Continues in 2-minute intervals via Action Scheduler
   - Processes all ticketable post types

#### Square → WordPress (Limited)

Square changes only flow to WordPress for:

- Order creation (including POS sales)
- Order updates
- Refunds
- Inventory verification (triggers re-sync if mismatch)

### POS Sales and Inventory

**Yes, synchronization is required for POS sales** to work properly:

1. **Prerequisites for POS Sales**
   - Events and tickets must be synced to Square first (Items Sync)
   - Each ticket must have a Square catalog ID
   - Inventory counts must be pushed to Square (Inventory Sync)
   - Without sync, tickets won't appear in Square POS

2. **POS Sale Flow**

    ```mermaid
    Square POS Sale → Webhook → WordPress Order → Inventory Adjustment
    ```

   - Customer purchases ticket via Square POS terminal
   - Square sends `order.created` webhook via WhoDat
   - WordPress creates local order with:
     - Order marked as created by `square-pos`
     - Line items matching Square order
     - Customer information synced
   - Local inventory automatically decremented

3. **Inventory Conflict Resolution**
   - If Square's inventory differs from WordPress after sale
   - Background task scheduled (few minutes delay)
   - WordPress inventory count pushed back to Square
   - Ensures WordPress remains the authoritative source
   - Prevents overselling from either system

### Inventory States

- **IN_STOCK**: Available for purchase
- **SOLD**: No inventory remaining
- **WASTE**: Manually removed inventory
- **NONE**: Not tracking inventory
- **Unlimited**: -1 in WordPress = 900,000,000+ in Square

## Technical Implementation Details

### Rate Limiting

- Exponential backoff for API rate limits
- Random delays between requests
- Maximum delay: 2 hours for heavily limited requests

### Data Integrity

The Integrity Controller ensures:

- Orphaned Square items are detected
- Sync status verification
- Cleanup of items no longer in WordPress
- Consistency maintenance between systems

### Background Processing

- Uses WordPress Action Scheduler
- Asynchronous processing for performance
- Automatic retries for failed operations
- Batch processing for efficiency

## Configuration Requirements

### For Square Integration

1. Square merchant account must be connected
2. OAuth authentication completed
3. Webhook registration active

### For Inventory Sync

1. Inventory sync enabled in settings
2. Tickets must have Square catalog IDs
3. Initial sync completed

### Supported Currencies

- USD, CAD, GBP, AUD, JPY, EUR

## Common Scenarios

### Scenario 1: Initial Square Setup

1. Merchant clicks "Connect with Square" in WordPress admin
2. Redirected to WhoDat for OAuth flow
3. WhoDat handles Square authentication
4. Tokens stored in WordPress
5. Webhook registration via WhoDat

### Scenario 2: Online Ticket Sale

1. Customer purchases ticket on WordPress site
2. Order created in WordPress with status "completed"
3. Inventory decremented locally
4. Background task pushes inventory update to Square
5. Square inventory matches WordPress

### Scenario 3: POS Ticket Sale

1. Cashier rings up ticket at Square POS terminal
2. Square processes payment and creates order
3. Square sends `order.created` webhook to WhoDat
4. WhoDat forwards to WordPress with signature
5. WordPress creates order:
   - Marked as `square-pos` source
   - Matches Square order ID
   - Syncs line items and customer
6. Local inventory adjusted
7. If mismatch detected later, WordPress pushes correct count

### Scenario 4: Refund Processing

1. Refund initiated in Square (POS or Dashboard)
2. Square sends `refund.created` webhook
3. WordPress receives via WhoDat
4. System waits for `refund.updated` with COMPLETED status
5. Order updated:
   - Status changed to "refunded"
   - Original order ID preserved
   - Refund ID stored
6. Inventory potentially restored based on settings

### Scenario 5: Inventory Mismatch

1. `inventory.count.updated` webhook received
2. System compares Square count with WordPress
3. If different, schedules background check
4. After delay, re-checks inventory
5. If still different, pushes WordPress count to Square
6. Fires `tec_tickets_commerce_square_ticket_out_of_sync` action

## Troubleshooting

### Common Issues

1. **Webhooks Not Received**
   - Check WhoDat registration status
   - Verify webhook URL accessibility
   - Confirm authentication keys

2. **Inventory Out of Sync**
   - Check Action Scheduler for pending tasks
   - Verify sync enabled in settings
   - Look for rate limit errors

3. **POS Sales Not Appearing**
   - Confirm webhook registration
   - Check for order creation errors
   - Verify Square catalog IDs exist

### Debug Information

- Webhook events logged in database
- Sync history tracked in post meta
- Rate limit exceptions logged
- Order creation errors captured

## Best Practices

1. **Always complete initial sync** before accepting POS sales
2. **Monitor webhook health** - auto-renewal happens every 12 hours
3. **Handle rate limits gracefully** - system implements automatic backoff
4. **Trust WordPress inventory** - it's the source of truth
5. **Test webhooks** in sandbox environment first

## API Endpoints

### Public Endpoints

- `/wp-json/tribe/tickets/v1/commerce/square/webhooks` - Webhook receiver

### Internal Endpoints

- Various admin AJAX endpoints for configuration
- REST endpoints for order management

## Security Considerations

1. All webhook requests authenticated
2. WhoDat signature verification required
3. URL secret keys rotated regularly

## Future Considerations

The integration is designed to be extensible, with clear separation between:

- Webhook handling
- Inventory management
- Order processing
- Payment handling

This allows for future enhancements while maintaining backward compatibility.
