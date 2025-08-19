# Event Tickets REST API Documentation Index

Complete documentation for the Event Tickets REST API V1.

## Documentation Structure

### Main Documentation

- [README](README.md) - Overview and getting started with Event Tickets API
- [Creating Endpoints](creating-endpoints.md) - Step-by-step guide for extending the API
- [Endpoints Controller](endpoints-controller.md) - Managing endpoint registration
- [Endpoints Overview](endpoints.md) - List of all ticket endpoints
- [Definitions Overview](definitions.md) - Ticket data structures

### Endpoint Documentation

Located in `endpoints/`:

- [Tickets Endpoints](endpoints/tickets.md) - Comprehensive ticket CRUD operations
  - GET /tickets - List tickets with filtering
  - POST /tickets - Create new tickets
  - GET /tickets/{id} - Retrieve single ticket
  - PUT /tickets/{id} - Update ticket
  - DELETE /tickets/{id} - Delete ticket

### Definition Documentation

Located in `definitions/`:

- [Ticket Definition](definitions/ticket.md) - Complete ticket data structure
  - Response schema
  - Request body schema
  - Property details
  - Validation rules

## Quick Links

### For Developers

1. [Getting Started](README.md#getting-started)
2. [Authentication](README.md#authentication)
3. [Creating Your First Custom Endpoint](creating-endpoints.md)
4. [Extending Ticket Definitions](definitions/ticket.md#filter-hooks)

### API Reference

1. [All Ticket Endpoints](endpoints.md)
2. [Ticket Collection Operations](endpoints/tickets.md#collection-endpoints)
3. [Single Ticket Operations](endpoints/tickets.md#single-ticket-endpoints)
4. [Error Handling](endpoints/tickets.md#error-handling)
5. [OpenAPI Documentation](README.md#architecture)

### Code Examples

1. [Basic Ticket Creation](endpoints/tickets.md#create-ticket)
2. [Ticket Filtering](endpoints/tickets.md#filtering)
3. [Stock Management](definitions/ticket.md#inventory-properties)
4. [Sale Price Configuration](definitions/ticket.md#pricing-properties)

## Architecture Overview

```
Event Tickets REST API
├── Controller (Main registration)
├── Endpoints Controller
│   ├── Tickets (Collection endpoint)
│   └── Ticket (Single endpoint)
├── Definitions
│   ├── Ticket_Definition
│   └── Ticket_Request_Body_Definition
├── Tags
│   └── Tickets_Tag
└── Traits
    └── With_Tickets_ORM
```

## Key Features

### Ticket Management
- Full CRUD operations
- Bulk filtering and search
- Pagination support
- Stock tracking
- Sale price management

### Integration Points
- WordPress REST API standards
- OpenAPI 3.0 documentation
- ORM integration (`tec_tc_tickets()`)
- Hook system for customization

### Security
- Permission-based access control
- Guest read access
- Authenticated write operations
- Input validation and sanitization

## Common Use Cases

### 1. List Available Tickets for an Event

```bash
GET /wp-json/tec/v1/tickets?events[]=123&show_hidden=false
```

### 2. Create a New Ticket

```bash
POST /wp-json/tec/v1/tickets
{
  "title": "General Admission",
  "event_id": 123,
  "price": 50.00,
  "manage_stock": true,
  "stock": 100
}
```

### 3. Update Ticket Pricing

```bash
PUT /wp-json/tec/v1/tickets/456
{
  "sale_price": 40.00,
  "sale_price_start_date": "2025-03-01 00:00:00",
  "sale_price_end_date": "2025-03-31 23:59:59"
}
```

### 4. Check Ticket Availability

```bash
GET /wp-json/tec/v1/tickets/456
```

## Extension Points

### Custom Endpoints
- [Creating Custom Endpoints](creating-endpoints.md)
- [Endpoint Interfaces](../../../common/docs/REST/TEC/V1/interfaces.md)
- [Abstract Classes](../../../common/docs/REST/TEC/V1/abstract-classes.md)

### Custom Definitions
- [Creating Definitions](creating-endpoints.md#creating-custom-definitions)
- [Parameter Types](../../../common/docs/REST/TEC/V1/parameter-types.md)
- [PropertiesCollection Usage](definitions/ticket.md#filter-hooks)

### Filters and Actions
- `tec_rest_swagger_ticket_definition` - Modify ticket schema
- `tec_tickets_rest_endpoints` - Filter registered endpoints
- `tec_tickets_rest_ticket_prepare_postarr` - Validate ticket data

## Testing

### Manual Testing
```bash
# List tickets
curl -X GET https://example.com/wp-json/tec/v1/tickets

# Get OpenAPI spec
curl -X GET https://example.com/wp-json/tec/v1/openapi
```

### Automated Testing
- Unit tests for endpoints
- Integration tests for full flow
- Snapshot tests for OpenAPI schemas

## Troubleshooting

### Common Issues

1. **Endpoints not appearing**
   - Check if commerce is enabled
   - Verify endpoint registration
   - Check REST API permalinks

2. **Permission errors**
   - Verify authentication
   - Check user capabilities
   - Review permission methods

3. **Validation failures**
   - Check required fields
   - Verify date formats
   - Review stock constraints

### Debug Mode

Enable WordPress debug mode for detailed error messages:

```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

## Resources

### Internal Documentation
- [TEC Common REST API](../../../common/docs/REST/TEC/V1/)
- [Event Tickets ORM](../../ORM/)
- [Commerce Documentation](../../Commerce/)

### External Resources
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [OpenAPI Specification](https://swagger.io/specification/)
- [JSON Schema Reference](https://json-schema.org/)

## Version History

- **5.26.0** - Initial Event Tickets REST API implementation
  - Ticket endpoints (collection and single)
  - Stock management support
  - Sale price functionality
  - OpenAPI documentation

## Contributing

When contributing to the Event Tickets REST API:

1. Follow existing patterns and conventions
2. Add comprehensive OpenAPI documentation
3. Include unit and integration tests
4. Update relevant documentation
5. Use proper type hints and parameter validation

For questions or support, consult the development team or refer to the main TEC REST API documentation.
