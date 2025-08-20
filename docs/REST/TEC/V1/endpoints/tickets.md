# Tickets Endpoints

Detailed documentation for ticket-specific endpoints in the Event Tickets REST API.

## Overview

The tickets endpoints provide comprehensive ticket management capabilities through RESTful operations. These endpoints are implemented in:

- `TEC\Tickets\REST\TEC\V1\Endpoints\Tickets` - Collection operations
- `TEC\Tickets\REST\TEC\V1\Endpoints\Ticket` - Single ticket operations

## Collection Endpoints

### List Tickets

```
GET /wp-json/tec/v1/tickets
```

Retrieves a paginated collection of tickets with advanced filtering options.

#### Authentication

Not required - guests can read tickets.

#### Query Parameters

##### Pagination

- **page** (integer)
  - Default: `1`
  - Minimum: `1`
  - Description: Current page number

- **per_page** (integer)
  - Default: `10`
  - Minimum: `1`
  - Maximum: `100`
  - Description: Number of tickets per page

##### Filtering

- **search** (string)
  - Description: Search tickets by title or content
  - Example: `?search=VIP`

- **events** (array[integer])
  - Description: Filter tickets by event IDs
  - Example: `?events[]=123&events[]=456`

- **status** (string)
  - Default: `publish`
  - Description: Filter by post status
  - Example: `?status=draft`

- **include** (array[integer])
  - Description: Include only specific ticket IDs
  - Example: `?include[]=10&include[]=20`

- **exclude** (array[integer])
  - Description: Exclude specific ticket IDs
  - Example: `?exclude[]=30&exclude[]=40`

- **show_hidden** (boolean)
  - Default: `false`
  - Description: Include tickets marked as hidden
  - Example: `?show_hidden=true`

##### Sorting

- **orderby** (string)
  - Default: `date`
  - Options: `date`, `id`, `include`, `relevance`, `slug`, `include_slugs`, `title`
  - Description: Field to sort by

- **order** (string)
  - Default: `desc`
  - Options: `asc`, `desc`
  - Description: Sort direction

#### Response Headers

```
X-WP-Total: 150
X-WP-TotalPages: 15
Link: <https://example.com/wp-json/tec/v1/tickets?page=2>; rel="next"
```

#### Example Request

```bash
curl -X GET "https://example.com/wp-json/tec/v1/tickets?events[]=123&per_page=20&orderby=title&order=asc" \
  -H "Accept: application/json"
```

#### Example Response

```json
[
  {
    "id": 100,
    "title": "Early Bird Ticket",
    "slug": "early-bird-ticket",
    "status": "publish",
    "description": "Special pricing for early registrants",
    "price": 45.00,
    "regular_price": 45.00,
    "sale_price": null,
    "on_sale": false,
    "event_id": 123,
    "manage_stock": true,
    "stock": 50,
    "sold": 25,
    "sku": "EB-2025",
    "type": "default",
    "show_description": true,
    "start_date": "2025-01-01 00:00:00",
    "end_date": "2025-03-31 23:59:59",
    "sale_price_start_date": null,
    "sale_price_end_date": null,
    "date": "2025-01-01T10:00:00",
    "date_gmt": "2025-01-01T15:00:00",
    "modified": "2025-01-15T14:30:00",
    "modified_gmt": "2025-01-15T19:30:00"
  }
]
```

### Create Ticket

```
POST /wp-json/tec/v1/tickets
```

Creates a new ticket.

#### Authentication

Required 🔒 - User must have appropriate capabilities.

#### Request Body

Content-Type: `application/json`

##### Required Fields

- **title** (string): Ticket name
- **event_id** (integer): Associated event ID
- **price** (number): Ticket price

##### Optional Fields

- **description** (string): Ticket description
- **content** (string): Extended description
- **status** (string): Post status (default: `publish`)
- **sale_price** (number): Discounted price
- **sale_price_start_date** (string): Sale start datetime (Y-m-d H:i:s)
- **sale_price_end_date** (string): Sale end datetime (Y-m-d H:i:s)
- **manage_stock** (boolean): Enable stock management
- **stock** (integer): Available quantity
- **show_description** (boolean): Display description
- **type** (string): Ticket type
- **start_date** (string): Ticket sale start
- **end_date** (string): Ticket sale end
- **sku** (string): Stock keeping unit

#### Example Request

```bash
curl -X POST "https://example.com/wp-json/tec/v1/tickets" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "VIP Access",
    "event_id": 123,
    "price": 150.00,
    "sale_price": 120.00,
    "sale_price_start_date": "2025-02-01 00:00:00",
    "sale_price_end_date": "2025-02-28 23:59:59",
    "manage_stock": true,
    "stock": 20,
    "description": "Premium event experience",
    "sku": "VIP-2025"
  }'
```

#### Response

Status: `201 Created`

```json
{
  "id": 101,
  "title": "VIP Access",
  "slug": "vip-access",
  "status": "publish",
  "event_id": 123,
  "price": 150.00,
  "sale_price": 120.00,
  "on_sale": true,
  "manage_stock": true,
  "stock": 20,
  "sold": 0,
  "sku": "VIP-2025",
  "date": "2025-01-20T12:00:00",
  "date_gmt": "2025-01-20T17:00:00"
}
```

## Single Ticket Endpoints

### Get Ticket

```
GET /wp-json/tec/v1/tickets/{id}
```

Retrieves a single ticket by ID.

#### Authentication

Not required - guests can read tickets.

#### Path Parameters

- **id** (integer): Ticket ID

#### Example Request

```bash
curl -X GET "https://example.com/wp-json/tec/v1/tickets/101" \
  -H "Accept: application/json"
```

### Update Ticket

```
PUT /wp-json/tec/v1/tickets/{id}
```

Updates an existing ticket.

#### Authentication

Required 🔒 - User must have edit capabilities.

#### Path Parameters

- **id** (integer): Ticket ID

#### Request Body

Same structure as create, all fields optional.

#### Example Request

```bash
curl -X PUT "https://example.com/wp-json/tec/v1/tickets/101" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "price": 175.00,
    "stock": 30
  }'
```

### Delete Ticket

```
DELETE /wp-json/tec/v1/tickets/{id}
```

Moves a ticket to trash.

#### Authentication

Required 🔒 - User must have delete capabilities.

#### Path Parameters

- **id** (integer): Ticket ID

#### Example Request

```bash
curl -X DELETE "https://example.com/wp-json/tec/v1/tickets/101" \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Response

Returns the trashed ticket object with status `200 OK`.

## Error Handling

### Common Error Responses

#### 400 Bad Request

```json
{
  "code": "rest_invalid_param",
  "message": "Invalid parameter(s): event_id",
  "data": {
    "status": 400,
    "params": {
      "event_id": "event_id is required"
    }
  }
}
```

#### 401 Unauthorized

```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to create tickets.",
  "data": {
    "status": 401
  }
}
```

#### 404 Not Found

```json
{
  "code": "rest_ticket_invalid_id",
  "message": "Invalid ticket ID.",
  "data": {
    "status": 404
  }
}
```

#### 410 Gone

```json
{
  "code": "rest_already_trashed",
  "message": "The ticket has already been deleted.",
  "data": {
    "status": 410
  }
}
```

## Advanced Usage

### Batch Operations

For multiple ticket operations, make parallel requests:

```javascript
const ticketIds = [101, 102, 103];
const updates = ticketIds.map(id => 
  fetch(`/wp-json/tec/v1/tickets/${id}`, {
    method: 'PUT',
    headers: {
      'Authorization': 'Bearer TOKEN',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ stock: 100 })
  })
);

await Promise.all(updates);
```

### Filtering by Multiple Events

```bash
curl -X GET "https://example.com/wp-json/tec/v1/tickets?events[]=123&events[]=456&events[]=789"
```

### Search with Pagination

```bash
curl -X GET "https://example.com/wp-json/tec/v1/tickets?search=VIP&page=2&per_page=50"
```

## Implementation Notes

1. **ORM Usage**: All database operations use `tec_tc_tickets()` ORM
2. **Permission Checks**: Handled by parent `Post_Entity_Endpoint` class
3. **Response Formatting**: Uses model's `to_array()` method
4. **Stock Management**: Automatically tracks sold count when orders are placed
5. **Date Handling**: All dates in Y-m-d H:i:s format, converted to ISO 8601 in responses

## Related Documentation

- [Ticket Definition](../definitions/ticket.md) - Data structure details
- [Creating Endpoints](../creating-endpoints.md) - Extend the API
- [REST API Overview](../README.md) - General API information