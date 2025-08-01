# Endpoints Controller

Documentation for the Event Tickets REST API Endpoints Controller system.

## Overview

The Endpoints Controller (`TEC\Tickets\REST\TEC\V1\Endpoints`) is the central registry for all ticket-related REST API components. It extends the base `Endpoints_Controller` from the common library and manages:

- Endpoint registration
- Tag registration
- Definition registration
- Lifecycle management

## Architecture

```
Controller (Main REST controller)
    └── Endpoints (Endpoints controller)
        ├── Endpoints (HTTP handlers)
        ├── Tags (OpenAPI tags)
        └── Definitions (Data schemas)
```

## Implementation

### Basic Structure

```php
namespace TEC\Tickets\REST\TEC\V1;

use TEC\Common\REST\TEC\V1\Abstracts\Endpoints_Controller;

class Endpoints extends Endpoints_Controller {
    /**
     * Returns the endpoints to register.
     *
     * @return Endpoint_Interface[]
     */
    public function get_endpoints(): array {
        return [
            Endpoints\Tickets::class,
            Endpoints\Ticket::class,
        ];
    }
    
    /**
     * Returns the tags to register.
     *
     * @return Tag_Interface[]
     */
    public function get_tags(): array {
        return [
            Tags\Tickets_Tag::class,
        ];
    }
    
    /**
     * Returns the definitions to register.
     *
     * @return Definition_Interface[]
     */
    public function get_definitions(): array {
        return [
            Documentation\Ticket_Definition::class,
            Documentation\Ticket_Request_Body_Definition::class,
        ];
    }
}
```

## Core Methods

### get_endpoints()

Returns an array of endpoint classes implementing `Endpoint_Interface`.

**Purpose**: Registers REST routes with WordPress

**Example**:
```php
public function get_endpoints(): array {
    return [
        Tickets::class,      // Handles /tickets
        Ticket::class,       // Handles /tickets/{id}
        Attendees::class,    // Handles /attendees
        Attendee::class,     // Handles /attendees/{id}
    ];
}
```

### get_tags()

Returns an array of tag classes implementing `Tag_Interface`.

**Purpose**: Groups endpoints in OpenAPI documentation

**Example**:
```php
public function get_tags(): array {
    return [
        Tickets_Tag::class,   // Groups ticket endpoints
        Attendees_Tag::class, // Groups attendee endpoints
    ];
}
```

### get_definitions()

Returns an array of definition classes implementing `Definition_Interface`.

**Purpose**: Registers data schemas for OpenAPI

**Example**:
```php
public function get_definitions(): array {
    return [
        // Response schemas
        Ticket_Definition::class,
        Attendee_Definition::class,
        
        // Request body schemas
        Ticket_Request_Body_Definition::class,
        Attendee_Request_Body_Definition::class,
    ];
}
```

## Registration Flow

1. **Controller Registration**
   ```php
   // In main Controller::do_register()
   $this->container->register( Endpoints::class );
   ```

2. **Automatic Discovery**
   - The base class automatically calls getter methods
   - Instantiates each class via the container
   - Registers with WordPress REST API

3. **Route Registration**
   ```php
   // Happens automatically for each endpoint
   register_rest_route( 'tec/v1', $endpoint->get_route(), $args );
   ```

## Extending the Controller

### Adding New Endpoints

```php
class Custom_Endpoints extends Endpoints {
    public function get_endpoints(): array {
        $endpoints = parent::get_endpoints();
        
        // Add custom endpoints
        $endpoints[] = Custom_Ticket_Endpoint::class;
        $endpoints[] = Ticket_Variations_Endpoint::class;
        
        return $endpoints;
    }
}
```

### Conditional Registration

```php
public function get_endpoints(): array {
    $endpoints = [
        Tickets::class,
        Ticket::class,
    ];
    
    // Conditionally add endpoints
    if ( $this->should_register_attendees() ) {
        $endpoints[] = Attendees::class;
        $endpoints[] = Attendee::class;
    }
    
    return $endpoints;
}

protected function should_register_attendees(): bool {
    return (bool) tribe_get_option( 'tickets_rest_api_attendees', false );
}
```

### Priority Management

```php
public function get_definitions(): array {
    $definitions = [];
    
    // Register in priority order
    $definitions[] = Base_Ticket_Definition::class;      // Priority: 10
    $definitions[] = Extended_Ticket_Definition::class;  // Priority: 20
    $definitions[] = Custom_Fields_Definition::class;    // Priority: 30
    
    return $definitions;
}
```

## Lifecycle Hooks

### Registration Hooks

```php
class Endpoints extends Endpoints_Controller {
    protected function on_register(): void {
        // Called when endpoints are registered
        add_action( 'rest_api_init', [ $this, 'setup_routes' ], 20 );
    }
    
    protected function on_unregister(): void {
        // Called when endpoints are unregistered
        remove_action( 'rest_api_init', [ $this, 'setup_routes' ], 20 );
    }
}
```

### Filtering Registration

```php
public function get_endpoints(): array {
    /**
     * Filters the ticket endpoints to register.
     *
     * @param array $endpoints Array of endpoint class names.
     */
    return apply_filters( 
        'tec_tickets_rest_endpoints', 
        [
            Tickets::class,
            Ticket::class,
        ]
    );
}
```

## Best Practices

### 1. Use Class Constants

```php
class Endpoints extends Endpoints_Controller {
    const ENDPOINTS = [
        Tickets::class,
        Ticket::class,
    ];
    
    public function get_endpoints(): array {
        return self::ENDPOINTS;
    }
}
```

### 2. Validate Interfaces

```php
public function get_endpoints(): array {
    $endpoints = [
        Tickets::class,
        Ticket::class,
    ];
    
    // Validate in development
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        foreach ( $endpoints as $endpoint ) {
            if ( ! is_subclass_of( $endpoint, Endpoint_Interface::class ) ) {
                throw new InvalidArgumentException( 
                    sprintf( '%s must implement Endpoint_Interface', $endpoint )
                );
            }
        }
    }
    
    return $endpoints;
}
```

### 3. Group Related Components

```php
class Endpoints extends Endpoints_Controller {
    // Ticket-related components
    protected function get_ticket_endpoints(): array {
        return [
            Tickets::class,
            Ticket::class,
        ];
    }
    
    protected function get_ticket_definitions(): array {
        return [
            Ticket_Definition::class,
            Ticket_Request_Body_Definition::class,
        ];
    }
    
    // Attendee-related components
    protected function get_attendee_endpoints(): array {
        return [
            Attendees::class,
            Attendee::class,
        ];
    }
    
    public function get_endpoints(): array {
        return array_merge(
            $this->get_ticket_endpoints(),
            $this->get_attendee_endpoints()
        );
    }
}
```

## Debugging

### List Registered Endpoints

```php
add_action( 'rest_api_init', function() {
    $controller = tribe( \TEC\Tickets\REST\TEC\V1\Endpoints::class );
    
    error_log( 'Registered endpoints:' );
    foreach ( $controller->get_endpoints() as $endpoint_class ) {
        $endpoint = tribe( $endpoint_class );
        error_log( sprintf( 
            '- %s: %s', 
            $endpoint_class, 
            $endpoint->get_base_path() 
        ) );
    }
}, 100 );
```

### Verify OpenAPI Registration

```bash
# Check if definitions are registered
curl -X GET https://example.com/wp-json/tec/v1/openapi \
  | jq '.components.schemas | keys'

# Check if tags are registered
curl -X GET https://example.com/wp-json/tec/v1/openapi \
  | jq '.tags[].name'
```

## Common Issues

### 1. Endpoints Not Appearing

**Problem**: Endpoints registered but not showing in REST API

**Solution**:
```php
// Ensure commerce is enabled
public function do_register(): void {
    if ( ! tec_tickets_commerce_is_enabled() ) {
        return;
    }
    
    $this->container->register( Endpoints::class );
}
```

### 2. Definition Not in OpenAPI

**Problem**: Definition class created but not in OpenAPI output

**Solution**:
```php
// Check definition is registered
public function get_definitions(): array {
    return [
        // Must be included here
        My_Custom_Definition::class,
    ];
}

// Verify priority
class My_Custom_Definition extends Definition {
    public function get_priority(): int {
        return 10; // Lower numbers load first
    }
}
```

### 3. Tag Not Grouping Endpoints

**Problem**: Tag created but endpoints not grouped

**Solution**:
```php
// In endpoint class
public function get_tags(): array {
    return [ tribe( My_Custom_Tag::class ) ];
}

// Tag must be registered in controller
public function get_tags(): array {
    return [
        My_Custom_Tag::class,
    ];
}
```

## Related Documentation

- [Creating Endpoints](creating-endpoints.md) - Build custom endpoints
- [REST API Overview](README.md) - General concepts
- [Common Library Docs](../../../common/docs/REST/TEC/V1/abstract-classes.md) - Base classes