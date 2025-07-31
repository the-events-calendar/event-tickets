# Event Tickets REST API Documentation

## Overview

The Event Tickets REST API provides a robust interface for managing tickets programmatically. Built on the TEC REST API V1 architecture, it enables full CRUD operations on ticket entities with comprehensive validation and error handling.

## Architecture

The API follows RESTful principles and is organized into:

- **Endpoints**: Handle HTTP requests and responses
- **Definitions**: Define data structures and schemas
- **ORM Integration**: Uses the tickets ORM (`tec_tc_tickets()`) for data operations
- **OpenAPI Documentation**: Auto-generated API documentation via OpenAPI 3.0

## Base URL

```
/wp-json/tec/v1
```

## Authentication

Authentication follows WordPress REST API standards. Endpoints requiring authentication are marked with the 🔒 icon.

- Guest users can read tickets
- Creating, updating, and deleting tickets requires authentication

## Endpoints

### Tickets Collection
- `GET /tickets` - List tickets
- `POST /tickets` 🔒 - Create a ticket

### Single Ticket
- `GET /tickets/{id}` - Retrieve a ticket
- `PUT /tickets/{id}` 🔒 - Update a ticket
- `DELETE /tickets/{id}` 🔒 - Delete a ticket

## Key Features

1. **Stock Management**: Built-in inventory tracking with optional stock limits
2. **Pricing Flexibility**: Support for regular and sale prices with date ranges
3. **Event Association**: Tickets are linked to specific events
4. **Date Controls**: Start and end sale dates for availability windows
5. **SKU Support**: Optional SKU tracking for inventory systems

## Error Handling

The API returns standard HTTP status codes:

- `200` - Success (read, update operations)
- `201` - Created (create operations)
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized
- `404` - Not Found
- `410` - Gone (already deleted)

## Example Request

```bash
curl -X GET https://example.com/wp-json/tec/v1/tickets \
  -H "Accept: application/json"
```

## Example Response

```json
[
  {
    "id": 123,
    "title": "General Admission",
    "description": "Access to main event",
    "price": 25.00,
    "stock": 100,
    "sold": 42,
    "event_id": 456
  }
]
```

## Getting Started

1. Ensure Event Tickets Commerce is enabled
2. Authenticate if performing write operations
3. Use the appropriate endpoint for your operation
4. Handle responses and errors appropriately

## Additional Resources

- [Creating Endpoints](creating-endpoints.md) - Guide for extending the API
- [Endpoints Reference](endpoints.md) - Detailed endpoint documentation
- [Definitions Reference](definitions.md) - Data structure documentation