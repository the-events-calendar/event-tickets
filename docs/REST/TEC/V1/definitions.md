# Event Tickets REST API Definitions

This document provides an overview of the data structures and schemas used in the Event Tickets REST API.

## Overview

The Event Tickets API uses OpenAPI 3.0 compatible schemas for consistent data structures. All definitions extend base schemas from the common TEC REST API library.

## Available Definitions

### Ticket

The main ticket entity representation.

- **Type**: `Ticket`
- **Extends**: `TEC_Post_Entity`
- **Documentation**: [Ticket Definition](definitions/ticket.md)

### Ticket Request Body

The request body structure for creating and updating tickets.

- **Type**: `Ticket_Request_Body`
- **Extends**: `TEC_Post_Entity_Request_Body`
- **Documentation**: [Ticket Definition](definitions/ticket.md#request-body)

## Schema Composition

All ticket definitions use the `allOf` pattern to compose schemas:

```yaml
Ticket:
  allOf:
    - $ref: '#/components/schemas/TEC_Post_Entity'
    - type: object
      properties:
        # Ticket-specific properties
```

This approach ensures consistency with other TEC REST API entities while adding ticket-specific fields.

## Common Properties

### Inherited from TEC_Post_Entity

All ticket entities inherit these base properties:

- `id` - Unique identifier
- `title` - Ticket name
- `slug` - URL-friendly identifier
- `status` - Post status (publish, draft, etc.)
- `content` - Extended description
- `date` - Creation date
- `date_gmt` - Creation date (GMT)
- `modified` - Last modified date
- `modified_gmt` - Last modified date (GMT)

### Ticket-Specific Properties

Properties unique to ticket entities:

- `description` - Short description
- `price` - Regular price
- `sale_price` - Discounted price
- `on_sale` - Sale status indicator
- `event_id` - Associated event
- `stock` - Available quantity
- `sold` - Number sold
- `sku` - Stock keeping unit
- `type` - Ticket type identifier

## Property Types

The API uses strongly-typed parameters:

- **Text**: String values
- **Number**: Decimal numbers (prices)
- **Positive_Integer**: Non-negative integers (IDs, quantities)
- **Boolean**: True/false values
- **Date**: Date strings (Y-m-d)
- **Date_Time**: DateTime strings (Y-m-d H:i:s)

## Validation Rules

### Price Fields

- Must be numeric
- Can include decimals (e.g., 25.99)
- Cannot be negative

### Date Fields

- **Date format**: `YYYY-MM-DD`
- **DateTime format**: `YYYY-MM-DD HH:MM:SS`
- Sale price dates must be valid ranges

### Stock Management

- When `manage_stock` is true, `stock` is required
- `stock` must be a positive integer
- `sold` is read-only, managed by the system

## Example Structures

### Minimal Ticket Creation

```json
{
  "title": "General Admission",
  "event_id": 123,
  "price": 50.00
}
```

### Full Ticket Response

```json
{
  "id": 456,
  "title": "VIP Experience",
  "slug": "vip-experience",
  "status": "publish",
  "content": "Enjoy exclusive VIP perks...",
  "description": "Premium event access with perks",
  "price": 150.00,
  "regular_price": 150.00,
  "sale_price": 120.00,
  "on_sale": true,
  "sale_price_start_date": "2025-02-01",
  "sale_price_end_date": "2025-02-28",
  "event_id": 123,
  "manage_stock": true,
  "stock": 50,
  "sold": 12,
  "sku": "VIP-2025",
  "type": "default",
  "show_description": true,
  "start_date": "2025-01-01 00:00:00",
  "end_date": "2025-12-31 23:59:59",
  "date": "2025-01-01T10:00:00",
  "date_gmt": "2025-01-01T15:00:00",
  "modified": "2025-01-15T14:30:00",
  "modified_gmt": "2025-01-15T19:30:00"
}
```

## OpenAPI Integration

All definitions are accessible via the OpenAPI endpoint:

```
GET /wp-json/tec/v1/openapi
```

Reference definitions in OpenAPI specs:

```yaml
$ref: 'https://example.com/wp-json/tec/v1/openapi#/components/schemas/Ticket'
```

## Extending Definitions

### Filter Hooks

Customize ticket definitions using WordPress filters:

```php
// Modify ticket-specific properties
add_filter('tec_rest_swagger_ticket_definition', function($documentation, $definition) {
    // Add custom properties
    return $documentation;
}, 10, 2);

// Modify any TEC REST definition
add_filter('tec_rest_swagger_definition', function($documentation, $definition) {
    // Add custom logic
    return $documentation;
}, 10, 2);
```

### Custom Properties

Add properties via the PropertiesCollection:

```php
$properties[] = new Text(
    'custom_field',
    fn() => __('Custom field description', 'my-plugin'),
);
```

## Next Steps

- Review detailed [Ticket Definition](definitions/ticket.md)
- Learn about [Parameter Types](../../../common/docs/REST/TEC/V1/parameter-types.md)
- Explore [Creating Custom Definitions](creating-endpoints.md#definitions)