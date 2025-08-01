# Event Tickets REST API Endpoints

This document provides a comprehensive reference for all Event Tickets REST API endpoints.

## Base Path

All endpoints are prefixed with: `/wp-json/tec/v1`

## Available Endpoints

### Tickets Collection Endpoints

#### GET /tickets

Retrieve a paginated list of tickets with filtering and sorting capabilities.

**Authentication**: Not required (guest access allowed)

**Query Parameters**:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number (min: 1) |
| `per_page` | integer | 10 | Items per page (min: 1, max: 100) |
| `search` | string | - | Search tickets by text |
| `events` | array[integer] | - | Filter by event IDs |
| `orderby` | string | date | Sort field: date, id, include, relevance, slug, include_slugs, title |
| `order` | string | desc | Sort direction: asc, desc |
| `status` | string | publish | Post status filter |
| `include` | array[integer] | - | Include specific ticket IDs |
| `exclude` | array[integer] | - | Exclude specific ticket IDs |
| `show_hidden` | boolean | false | Include hidden tickets |

**Response Headers**:

- `X-WP-Total`: Total number of tickets
- `X-WP-TotalPages`: Total number of pages
- `Link`: RFC 5988 pagination links

**Response**: Array of ticket objects

#### POST /tickets

Create a new ticket.

**Authentication**: Required 🔒

**Request Body**: See [Ticket Request Body Definition](definitions/ticket.md#request-body)

**Response**: Created ticket object (201 status)

### Single Ticket Endpoints

#### GET /tickets/{id}

Retrieve a single ticket by ID.

**Authentication**: Not required (guest access allowed)

**Path Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Ticket ID |

**Response**: Ticket object

#### PUT /tickets/{id}

Update an existing ticket.

**Authentication**: Required 🔒

**Path Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Ticket ID |

**Request Body**: See [Ticket Request Body Definition](definitions/ticket.md#request-body)

**Response**: Updated ticket object

#### DELETE /tickets/{id}

Move a ticket to trash.

**Authentication**: Required 🔒

**Path Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Ticket ID |

**Response**: Trashed ticket object

## Operation IDs

For OpenAPI/Swagger integration:

- `getTickets` - List tickets
- `createTicket` - Create ticket
- `getTicket` - Get single ticket
- `updateTicket` - Update ticket
- `deleteTicket` - Delete ticket

## Implementation Details

### ORM Integration

All endpoints use the tickets ORM via the `tec_tc_tickets()` function, accessed through the `With_Tickets_ORM` trait.

### Permission Checks

Permission validation is handled by the abstract `Post_Entity_Endpoint` class:

- Read operations: Guest access allowed
- Write operations: Requires authentication and appropriate capabilities

### Response Formats

All endpoints return JSON responses following the ticket schema defined in the [Ticket Definition](definitions/ticket.md).

## Error Responses

Standard error responses across all endpoints:

```json
{
  "code": "rest_invalid_param",
  "message": "Invalid parameter(s): per_page",
  "data": {
    "status": 400,
    "params": {
      "per_page": "per_page must be between 1 and 100"
    }
  }
}
```

## Next Steps

- View detailed [Tickets Endpoints](endpoints/tickets.md) documentation
- Review [Ticket Definition](definitions/ticket.md) for data structures
- Learn about [Creating Custom Endpoints](creating-endpoints.md)