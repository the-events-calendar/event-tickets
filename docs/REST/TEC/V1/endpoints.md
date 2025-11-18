# Event Tickets REST API Endpoints

This document provides a comprehensive reference for all Event Tickets REST API endpoints.

## Base URL

```bash
https://yoursite.com/wp-json/tec/v1
```

## Activation Requirements

The Tickets REST API endpoints are only available when Tickets Commerce is enabled. The controller checks for Commerce availability before registering endpoints.

## Available Endpoints

### Tickets

#### Collection Endpoint

- **Path**: `/tickets`
- **Class**: `TEC\Tickets\REST\TEC\V1\Endpoints\Tickets`
- **Interfaces**: `Collection_Endpoint` (Readable_Endpoint, Creatable_Endpoint)
- **Operations**: GET, POST
- **Description**: Manage ticket collections
- **ORM**: Uses `tribe_tickets()` via `With_Tickets_ORM` trait
- **Post Type**: `tec_tc_ticket`
- **Operation IDs**: `getTickets` (GET), `createTicket` (POST)

##### GET /tickets

Retrieve a paginated list of tickets with filtering and sorting capabilities.

**Authentication**: Not required (guest access allowed)

**Query Parameters**:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | 1 | Page number (min: 1) |
| `per_page` | integer | 10 | Items per page (min: 1, max: 100) |
| `search` | string | - | Search tickets by text |
| `event` | integer | - | Filter by event ID (parent post) |
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

##### POST /tickets

Create a new ticket.

**Authentication**: Required 🔒

**Request Body**: See [Ticket Request Body Definition](definitions/ticket.md#request-body)

**Response**: Created ticket object (201 status)

#### Single Entity Endpoint

- **Path**: `/tickets/{id}`
- **Class**: `TEC\Tickets\REST\TEC\V1\Endpoints\Ticket`
- **Interface**: `RUD_Endpoint` (Readable_Endpoint, Updatable_Endpoint, Deletable_Endpoint)
- **Operations**: GET, PUT/PATCH, DELETE
- **Description**: Manage individual tickets
- **ORM**: Uses `tribe_tickets()` via `With_Tickets_ORM` trait
- **Post Type**: `tec_tc_ticket`
- **Operation IDs**: `getTicket` (GET), `updateTicket` (PUT), `deleteTicket` (DELETE)

##### GET /tickets/{id}

Retrieve a single ticket by ID.

**Authentication**: Not required (guest access allowed)

**Path Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Ticket ID (validated with Positive_Integer) |

**Response**: Ticket object

##### PUT/PATCH /tickets/{id}

Update an existing ticket.

**Authentication**: Required 🔒

**Path Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Ticket ID (validated with Positive_Integer) |

**Request Body**: See [Ticket Request Body Definition](definitions/ticket.md#request-body)

**Response**: Updated ticket object

##### DELETE /tickets/{id}

Move a ticket to trash or permanently delete.

**Authentication**: Required 🔒

**Path Parameters**:

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | integer | Ticket ID (validated with Positive_Integer) |

**Query Parameters**:

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `force` | boolean | false | Permanently delete instead of trash |

**Response**: Deleted ticket object with `deleted` flag

## Operation IDs

For OpenAPI/Swagger integration:

- `getTickets` - List tickets
- `createTicket` - Create ticket
- `getTicket` - Get single ticket
- `updateTicket` - Update ticket
- `deleteTicket` - Delete ticket

## Implementation Details

### Controller Registration

The Tickets API is registered through:

- **Main Controller**: `TEC\Tickets\REST\TEC\V1\Controller`
- **Endpoints Controller**: `TEC\Tickets\REST\TEC\V1\Endpoints`
- **Conditional Registration**: Only when Tickets Commerce is enabled

### Traits Used

The Tickets API endpoints use several specialized traits:

#### Core Traits
- **`With_Tickets_ORM`**: Provides access to `tribe_tickets()` ORM and ticket repository
- **`Read_Archive_Response`**: Standard archive read operations
- **`Create_Entity_Response`**: Standard entity creation
- **`Read_Entity_Response`**: Standard single entity read
- **`Update_Entity_Response`**: Standard entity update
- **`Delete_Entity_Response`**: Standard entity deletion

#### Ticket-Specific Traits
- **`With_Filtered_Ticket_Params`**: Filters and validates ticket-specific parameters
- **`With_Parent_Post_Read_Check`**: Validates access to parent events
- **`With_TC_Provider`**: Access to Tickets Commerce provider
- **`With_Ticket_Upsert`**: Unified ticket creation/update logic

### Tags

All Tickets endpoints are tagged with:

- **Tag Class**: `TEC\Tickets\REST\TEC\V1\Tags\Tickets_Tag`
- **Tag Name**: "Tickets"
- **Description**: "Operations for managing event tickets"

### Permission Checks

Permission validation is handled by the abstract `Post_Entity_Endpoint` class:

- **Read operations**: Guest access allowed (`guest_can_read = true`)
- **Write operations**: Requires authentication and appropriate capabilities
- **Parent Post Validation**: Uses `With_Parent_Post_Read_Check` to ensure event is accessible

### ORM Integration

All endpoints use the tickets ORM system:

- **Primary ORM**: `tribe_tickets()` function
- **Repository**: `tribe('tickets.ticket-repository')`
- **Access Method**: Via `With_Tickets_ORM` trait

### Response Formats

All endpoints return JSON responses following the ticket schema defined in the [Ticket Definition](definitions/ticket.md).

## Error Responses

Standard error responses across all endpoints:

### 400 Bad Request
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

### 404 Not Found
```json
{
  "code": "rest_post_invalid_id",
  "message": "Invalid ticket ID.",
  "data": {
    "status": 404
  }
}
```

### 403 Forbidden
```json
{
  "code": "rest_forbidden",
  "message": "Sorry, you are not allowed to do that.",
  "data": {
    "status": 403
  }
}
```

## Quick Reference

| Endpoint | GET | POST | PUT/PATCH | DELETE |
|----------|-----|------|-----------|--------|
| `/tickets` | ✓ | ✓ | - | - |
| `/tickets/{id}` | ✓ | - | ✓ | ✓ |

## Response Format

All endpoints return JSON responses with appropriate HTTP status codes:

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `403` - Forbidden
- `404` - Not Found
- `410` - Gone (deleted resource)

## Pagination

Collection endpoints support pagination:

- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 10, max: 100)

Response headers include:

- `X-WP-Total` - Total number of items
- `X-WP-TotalPages` - Total number of pages
- `Link` - RFC 5988 pagination links

## Next Steps

- View detailed [Tickets Endpoints](endpoints/tickets.md) documentation
- Review [Ticket Definition](definitions/ticket.md) for data structures
- Learn about [Creating Custom Endpoints](creating-endpoints.md)