# Creating Event Tickets REST API Endpoints

This guide explains how to create custom endpoints for the Event Tickets REST API, following the established patterns and architecture.

## Overview

The Event Tickets REST API is built on the TEC REST API V1 framework. All endpoints follow consistent patterns for:

- Authentication and permissions
- Request/response handling
- OpenAPI documentation
- ORM integration

## Architecture Components

### 1. Controller

The main controller (`TEC\Tickets\REST\TEC\V1\Controller`) manages endpoint registration:

```php
namespace TEC\Custom\REST\TEC\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

class Controller extends Controller_Contract {
    public function do_register(): void {
        if ( ! tec_tickets_commerce_is_enabled() ) {
            return;
        }
        
        $this->container->register( Endpoints::class );
    }
}
```

### 2. Endpoints Controller

Manages endpoint, tag, and definition registration:

```php
namespace TEC\Custom\REST\TEC\V1;

use TEC\Common\REST\TEC\V1\Abstracts\Endpoints_Controller;

class Endpoints extends Endpoints_Controller {
    public function get_endpoints(): array {
        return [
            Custom_Tickets::class,
            Custom_Ticket::class,
        ];
    }
    
    public function get_tags(): array {
        return [
            Custom_Tag::class,
        ];
    }
    
    public function get_definitions(): array {
        return [
            Custom_Ticket_Definition::class,
        ];
    }
}
```

## Creating a Custom Endpoint

### Step 1: Define the Endpoint Class

For a collection endpoint (list/create):

```php
namespace TEC\Custom\REST\TEC\V1\Endpoints;

use TEC\Common\REST\TEC\V1\Abstracts\Post_Entity_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\Readable_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\Creatable_Endpoint;
use TEC\Common\REST\TEC\V1\Traits\Read_Archive_Response;
use TEC\Common\REST\TEC\V1\Traits\Create_Entity_Response;
use TEC\Tickets\REST\TEC\V1\Traits\With_Tickets_ORM;

class Custom_Tickets extends Post_Entity_Endpoint implements Readable_Endpoint, Creatable_Endpoint {
    use Read_Archive_Response;
    use Create_Entity_Response;
    use With_Tickets_ORM;
    
    public function get_base_path(): string {
        return '/custom-tickets';
    }
    
    public function get_post_type(): string {
        return 'tec_tc_ticket'; // Or your custom post type
    }
    
    public function guest_can_read(): bool {
        return true; // Or implement custom logic
    }
    
    public function get_model_class(): string {
        return Custom_Ticket_Model::class;
    }
}
```

For a single entity endpoint (read/update/delete):

```php
namespace TEC\Custom\REST\TEC\V1\Endpoints;

use TEC\Common\REST\TEC\V1\Abstracts\Post_Entity_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\RUD_Endpoint;
use TEC\Common\REST\TEC\V1\Traits\Read_Entity_Response;
use TEC\Common\REST\TEC\V1\Traits\Update_Entity_Response;
use TEC\Common\REST\TEC\V1\Traits\Delete_Entity_Response;

class Custom_Ticket extends Post_Entity_Endpoint implements RUD_Endpoint {
    use Read_Entity_Response;
    use Update_Entity_Response;
    use Delete_Entity_Response;
    use With_Tickets_ORM;
    
    public function get_base_path(): string {
        return '/custom-tickets/%s';
    }
    
    public function get_path_parameters(): PathArgumentCollection {
        $collection = new PathArgumentCollection();
        
        $collection[] = new Positive_Integer(
            'id',
            fn() => __( 'The ID of the custom ticket', 'event-tickets' ),
        );
        
        return $collection;
    }
}
```

### Step 2: Define Query Arguments

For read operations:

```php
public function read_args(): QueryArgumentCollection {
    $collection = new QueryArgumentCollection();
    
    $collection[] = new Positive_Integer(
        'page',
        fn() => __( 'The collection page number.', 'event-tickets' ),
        1,
        1
    );
    
    $collection[] = new Text(
        'custom_filter',
        fn() => __( 'Filter by custom field.', 'event-tickets' ),
    );
    
    return $collection;
}
```

### Step 3: Create OpenAPI Schema

```php
public function read_schema(): OpenAPI_Schema {
    $schema = new OpenAPI_Schema(
        fn() => __( 'Retrieve Custom Tickets', 'event-tickets' ),
        fn() => __( 'Returns a list of custom tickets', 'event-tickets' ),
        $this->get_operation_id( 'read' ),
        $this->get_tags(),
        null,
        $this->read_args()
    );
    
    // Add response definitions
    $schema->add_response(
        200,
        fn() => __( 'Returns the list of custom tickets', 'event-tickets' ),
        $this->get_response_headers(),
        'application/json',
        $this->get_response_body(),
    );
    
    return $schema;
}
```

### Step 4: Create Custom ORM Trait

```php
namespace TEC\Custom\REST\TEC\V1\Traits;

use Tribe__Repository__Interface;

trait With_Custom_Tickets_ORM {
    public function get_orm(): Tribe__Repository__Interface {
        return tribe( 'tickets.custom' );
    }
}
```

## Creating Custom Definitions

### Step 1: Define the Schema

```php
namespace TEC\Custom\REST\TEC\V1\Documentation;

use TEC\Common\REST\TEC\V1\Abstracts\Definition;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;

class Custom_Ticket_Definition extends Definition {
    public function get_type(): string {
        return 'Custom_Ticket';
    }
    
    public function get_priority(): int {
        return 20; // Higher priority loads later
    }
    
    public function get_documentation(): array {
        $properties = new PropertiesCollection();
        
        $properties[] = (
            new Text(
                'custom_field',
                fn() => __( 'Custom field description', 'event-tickets' ),
            )
        )->set_example( 'Example value' );
        
        return [
            'allOf' => [
                [
                    '$ref' => '#/components/schemas/Ticket',
                ],
                [
                    'type'        => 'object',
                    'description' => __( 'A custom ticket type', 'event-tickets' ),
                    'title'       => 'Custom Ticket',
                    'properties'  => $properties,
                ],
            ],
        ];
    }
}
```

## Advanced Patterns

### Custom Authentication

Override permission methods:

```php
public function can_read( WP_REST_Request $request ): bool {
    // Custom permission logic
    $ticket_id = $request->get_param( 'id' );
    return current_user_can( 'read_custom_ticket', $ticket_id );
}

public function can_create( WP_REST_Request $request ): bool {
    return current_user_can( 'create_custom_tickets' );
}
```

### Custom Response Formatting

Override response methods:

```php
protected function prepare_item_for_response( $item, WP_REST_Request $request ): array {
    $data = parent::prepare_item_for_response( $item, $request );
    
    // Add custom fields
    $data['custom_data'] = get_post_meta( $item->ID, '_custom_data', true );
    
    return $data;
}
```

### Custom Validation

Add validation in request preparation:

```php
protected function prepare_postarr( array $postarr, WP_REST_Request $request ): array {
    $postarr = parent::prepare_postarr( $postarr, $request );
    
    // Custom validation
    if ( isset( $postarr['meta_input']['_custom_field'] ) ) {
        $value = $postarr['meta_input']['_custom_field'];
        if ( ! $this->validate_custom_field( $value ) ) {
            throw new InvalidArgumentException( 'Invalid custom field value' );
        }
    }
    
    return $postarr;
}
```

## Best Practices

### 1. Use Type-Safe Parameters

Always use parameter type classes:

```php
// Good
$collection[] = new Positive_Integer( 'event_id', $description );

// Avoid
$collection[] = [
    'name' => 'event_id',
    'type' => 'integer',
];
```

### 2. Leverage Traits

Create reusable traits for common functionality:

```php
trait With_Ticket_Filtering {
    protected function add_ticket_filters( QueryArgumentCollection $collection ): void {
        $collection[] = new Boolean(
            'show_sold_out',
            fn() => __( 'Include sold out tickets', 'event-tickets' ),
            false,
        );
    }
}
```

### 3. Follow Naming Conventions

- Endpoints: `Custom_Tickets` (plural for collections)
- Definitions: `Custom_Ticket_Definition`
- Operation IDs: `getCustomTickets`, `createCustomTicket`

### 4. Document Everything

Add comprehensive OpenAPI documentation:

```php
$parameter = new Text(
    'field_name',
    fn() => __( 'Clear description of the field purpose', 'event-tickets' ),
);

$parameter->set_example( 'realistic-example' )
          ->set_pattern( '^[A-Z0-9-]+$' )
          ->set_min_length( 3 )
          ->set_max_length( 50 );
```

## Testing Your Endpoint

### 1. Unit Tests

```php
class Custom_Tickets_Test extends WP_UnitTestCase {
    public function test_endpoint_registration() {
        $endpoints = tribe( Endpoints::class )->get_endpoints();
        $this->assertContains( Custom_Tickets::class, $endpoints );
    }
    
    public function test_read_permission() {
        $endpoint = tribe( Custom_Tickets::class );
        $request = new WP_REST_Request( 'GET', '/tec/v1/custom-tickets' );
        
        $this->assertTrue( $endpoint->can_read( $request ) );
    }
}
```

### 2. Integration Tests

```bash
# Test endpoint availability
curl -X GET https://example.com/wp-json/tec/v1/custom-tickets

# Test OpenAPI documentation
curl -X GET https://example.com/wp-json/tec/v1/openapi
```

### 3. Snapshot Tests

Use snapshot testing for OpenAPI schemas:

```php
public function test_openapi_schema_snapshot() {
    $endpoint = tribe( Custom_Tickets::class );
    $schema = $endpoint->read_schema()->jsonSerialize();
    
    $this->assertMatchesJsonSnapshot( $schema );
}
```

## Common Pitfalls

### 1. Forgetting ORM Registration

Ensure your ORM is registered:

```php
// In service provider
$this->container->singleton( 'tickets.custom', Custom_Tickets_ORM::class );
```

### 2. Missing Permission Checks

Never override base permission methods without proper checks:

```php
// Wrong
public function can_read(): bool {
    return true;
}

// Correct
public function can_read( WP_REST_Request $request ): bool {
    return parent::can_read( $request ) && $this->custom_check( $request );
}
```

### 3. Incorrect Path Parameters

Ensure path parameters match the pattern:

```php
// Path: /custom-tickets/%s/items/%d
public function get_path_parameters(): PathArgumentCollection {
    $collection = new PathArgumentCollection();
    
    $collection[] = new Text( 'ticket_slug', $description );
    $collection[] = new Positive_Integer( 'item_id', $description );
    
    return $collection;
}
```

## Resources

- [TEC REST API Common Documentation](../../../common/docs/REST/TEC/V1/)
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Event Tickets ORM Documentation](../../ORM/)