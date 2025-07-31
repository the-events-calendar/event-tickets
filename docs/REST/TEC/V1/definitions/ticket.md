# Ticket Definition

Comprehensive documentation for the Ticket data structure in the Event Tickets REST API.

## Overview

The Ticket definition represents a ticketing product that can be purchased for events. It extends the base `TEC_Post_Entity` schema with ticket-specific properties for pricing, inventory, and availability management.

## Schema Location

- **Class**: `TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Definition`
- **Type**: `Ticket`
- **OpenAPI Reference**: `#/components/schemas/Ticket`

## Full Schema

```yaml
Ticket:
  allOf:
    - $ref: '#/components/schemas/TEC_Post_Entity'
    - type: object
      title: Ticket
      description: A ticket
      properties:
        description:
          type: string
          description: The description of the ticket
          example: "This is a description of the ticket"
        on_sale:
          type: boolean
          description: Whether the ticket is on sale
          example: true
          nullable: true
        sale_price:
          type: number
          description: The sale price of the ticket
          example: 20.00
          nullable: true
        price:
          type: number
          description: The price of the ticket
          example: 25.00
        regular_price:
          type: number
          description: The regular price of the ticket
          example: 25.00
        show_description:
          type: boolean
          description: Whether to show the ticket description
          example: true
        start_date:
          type: string
          format: date-time
          pattern: ^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$
          description: The start sale date of the ticket
          example: "2025-05-01 00:00:00"
          nullable: true
        end_date:
          type: string
          format: date-time
          pattern: ^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$
          description: The end sale date of the ticket
          example: "2025-06-04 23:59:59"
          nullable: true
        sale_price_start_date:
          type: string
          format: date
          pattern: ^[0-9]{4}-[0-9]{2}-[0-9]{2}$
          description: The start date for the sale price
          example: "2025-06-01"
          nullable: true
        sale_price_end_date:
          type: string
          format: date
          pattern: ^[0-9]{4}-[0-9]{2}-[0-9]{2}$
          description: The end date for the sale price
          example: "2025-06-30"
          nullable: true
        event_id:
          type: integer
          minimum: 1
          description: The ID of the event this ticket is associated with
          example: 123
        manage_stock:
          type: boolean
          description: Whether stock is being managed for this ticket
          example: true
        stock:
          type: integer
          minimum: 1
          description: The stock quantity available
          example: 100
          nullable: true
        type:
          type: string
          description: The type of ticket
          example: "default"
        sold:
          type: integer
          minimum: 0
          description: The number of tickets sold
          example: 42
          readOnly: true
        sku:
          type: string
          description: The SKU of the ticket
          example: "TICKET-123"
          nullable: true
```

## Property Details

### Pricing Properties

#### price
- **Type**: number
- **Required**: Yes (for creation)
- **Description**: The current selling price
- **Example**: `25.00`

#### regular_price
- **Type**: number
- **Description**: The standard price (before any sales)
- **Example**: `25.00`
- **Note**: Automatically set from `price` if not provided

#### sale_price
- **Type**: number
- **Nullable**: Yes
- **Description**: Discounted price during sale periods
- **Example**: `20.00`

#### on_sale
- **Type**: boolean
- **Nullable**: Yes
- **Description**: Indicates if sale pricing is active
- **Computed**: Based on sale price and date ranges

### Availability Properties

#### start_date
- **Type**: string (datetime)
- **Format**: `YYYY-MM-DD HH:MM:SS`
- **Nullable**: Yes
- **Description**: When ticket sales begin
- **Example**: `2025-05-01 00:00:00`

#### end_date
- **Type**: string (datetime)
- **Format**: `YYYY-MM-DD HH:MM:SS`
- **Nullable**: Yes
- **Description**: When ticket sales end
- **Example**: `2025-06-04 23:59:59`

### Sale Period Properties

#### sale_price_start_date
- **Type**: string (date)
- **Format**: `YYYY-MM-DD`
- **Nullable**: Yes
- **Description**: Start of sale price period
- **Example**: `2025-06-01`

#### sale_price_end_date
- **Type**: string (date)
- **Format**: `YYYY-MM-DD`
- **Nullable**: Yes
- **Description**: End of sale price period
- **Example**: `2025-06-30`

### Inventory Properties

#### manage_stock
- **Type**: boolean
- **Description**: Enable inventory tracking
- **Example**: `true`

#### stock
- **Type**: integer
- **Minimum**: 1
- **Nullable**: Yes (when not managing stock)
- **Description**: Available quantity
- **Example**: `100`

#### sold
- **Type**: integer
- **ReadOnly**: Yes
- **Description**: Quantity sold (system-managed)
- **Example**: `42`

### Identification Properties

#### event_id
- **Type**: integer
- **Required**: Yes
- **Description**: Associated event ID
- **Example**: `123`

#### sku
- **Type**: string
- **Nullable**: Yes
- **Description**: Stock keeping unit
- **Example**: `TICKET-123`

#### type
- **Type**: string
- **Description**: Ticket type identifier
- **Example**: `default`
- **Note**: Used for categorization and display logic

### Display Properties

#### description
- **Type**: string
- **Description**: Short ticket description
- **Example**: `This is a description of the ticket`

#### show_description
- **Type**: boolean
- **Description**: Display description on frontend
- **Example**: `true`

## Request Body Schema

For creating and updating tickets, use the `Ticket_Request_Body` definition.

```yaml
Ticket_Request_Body:
  allOf:
    - $ref: '#/components/schemas/TEC_Post_Entity_Request_Body'
    - type: object
      title: Ticket Request Body
      description: The request body for the ticket endpoint
      properties:
        event_id:
          type: integer
          minimum: 1
          description: The ID of the event this ticket is associated with
          example: 123
        price:
          type: number
          description: The price of the ticket
          example: 25.05
        sale_price:
          type: number
          description: The sale price of the ticket
          example: 20.05
        sale_price_start_date:
          type: string
          format: date-time
          pattern: ^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$
          description: The start date for the sale price
          example: "2025-06-01 00:00:00"
        sale_price_end_date:
          type: string
          format: date-time
          pattern: ^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$
          description: The end date for the sale price
          example: "2025-06-30 23:59:59"
        manage_stock:
          type: boolean
          description: Whether stock is being managed for this ticket
          example: true
        stock:
          type: integer
          minimum: 1
          description: The stock quantity available
          example: 100
        show_description:
          type: boolean
          description: Whether to show the ticket description
          example: true
        type:
          type: string
          description: The type of ticket
          example: "default"
        start_date:
          type: string
          format: date-time
          pattern: ^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$
          description: The start sale date of the ticket
          example: "2025-05-01 00:00:00"
        end_date:
          type: string
          format: date-time
          pattern: ^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$
          description: The end sale date of the ticket
          example: "2025-06-04 23:59:59"
        sku:
          type: string
          description: The SKU of the ticket
          example: "TICKET-123"
```

## Usage Examples

### Minimal Ticket

```json
{
  "id": 100,
  "title": "General Admission",
  "event_id": 456,
  "price": 50.00,
  "regular_price": 50.00,
  "manage_stock": false,
  "type": "default"
}
```

### Ticket with Sale Price

```json
{
  "id": 101,
  "title": "Early Bird Special",
  "event_id": 456,
  "price": 75.00,
  "regular_price": 75.00,
  "sale_price": 60.00,
  "on_sale": true,
  "sale_price_start_date": "2025-01-01",
  "sale_price_end_date": "2025-02-28"
}
```

### Ticket with Stock Management

```json
{
  "id": 102,
  "title": "VIP Package",
  "event_id": 456,
  "price": 150.00,
  "manage_stock": true,
  "stock": 50,
  "sold": 18,
  "sku": "VIP-2025-001"
}
```

### Complete Ticket Example

```json
{
  "id": 103,
  "title": "Premium Workshop Pass",
  "slug": "premium-workshop-pass",
  "status": "publish",
  "content": "<p>Full access to all workshop sessions...</p>",
  "description": "All-inclusive workshop access with materials",
  "price": 299.00,
  "regular_price": 299.00,
  "sale_price": 249.00,
  "on_sale": true,
  "sale_price_start_date": "2025-03-01",
  "sale_price_end_date": "2025-03-31",
  "event_id": 789,
  "manage_stock": true,
  "stock": 30,
  "sold": 12,
  "sku": "WORKSHOP-PREMIUM",
  "type": "workshop",
  "show_description": true,
  "start_date": "2025-03-01 00:00:00",
  "end_date": "2025-05-31 23:59:59",
  "date": "2025-01-15T10:00:00",
  "date_gmt": "2025-01-15T15:00:00",
  "modified": "2025-02-20T14:30:00",
  "modified_gmt": "2025-02-20T19:30:00"
}
```

## Validation Rules

### Required Fields (Creation)

1. `title` - Ticket name
2. `event_id` - Valid event ID
3. `price` - Numeric price value

### Business Logic

1. **Sale Price Validation**
   - `sale_price` must be less than `regular_price`
   - Sale date range must be valid (start before end)

2. **Stock Management**
   - When `manage_stock` is true, `stock` is required
   - `stock` must be positive integer
   - System prevents overselling

3. **Date Validation**
   - `start_date` must be before `end_date`
   - Sale price dates must fall within ticket availability dates

## Filter Hooks

### Customizing the Definition

```php
add_filter('tec_rest_swagger_ticket_definition', function($documentation, $definition) {
    // Add custom properties
    $documentation['allOf'][1]['properties']['custom_field'] = [
        'type' => 'string',
        'description' => 'Custom field for tickets',
    ];
    return $documentation;
}, 10, 2);
```

### Adding Validation

```php
add_filter('tec_tickets_rest_ticket_prepare_postarr', function($postarr, $request) {
    // Custom validation logic
    if (isset($postarr['meta_input']['_price'])) {
        // Validate price constraints
    }
    return $postarr;
}, 10, 2);
```

## Related Documentation

- [Tickets Endpoints](../endpoints/tickets.md) - API operations
- [Parameter Types](../../../../common/docs/REST/TEC/V1/parameter-types.md) - Type system
- [Creating Endpoints](../creating-endpoints.md) - Extending the API