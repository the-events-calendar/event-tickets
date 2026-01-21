# WPML Integration for Event Tickets

High-level overview of how the WPML (WordPress Multilingual Plugin) integration works with Event Tickets.

## Purpose

This integration ensures Event Tickets works correctly in multilingual WordPress sites using WPML. It handles:
- **Ticket translations**: Tickets can be translated to different languages
- **Page translations**: Checkout and success pages redirect to correct language versions
- **Data synchronization**: Ticket meta fields sync across translations
- **Cart persistence**: Shopping cart works across language switches
- **Attendee aggregation**: Attendee queries include all language translations

## Architecture Overview

The integration is organized into logical folders, each handling a specific concern:

```
WPML/
├── Core/           # Foundation: WPML adapter and language switching utilities
├── Pages/          # Special page translation (checkout, success)
├── Meta/           # Meta field synchronization
├── Cart/           # Cart/checkout language context fixes
├── Tickets/        # Ticket-specific operations (language assignment, attendee aggregation)
└── Integration.php # Main entry point - registers all services
```

## Component Responsibilities

### Core/ - Foundation Layer

**Wpml_Adapter** (`Core/Wpml_Adapter.php`)
- Adapter pattern wrapping WPML functionality
- Provides clean API for translation operations
- Methods: `translate_post_id()`, `translate_page_id()`, `get_translation_ids()`, `is_original_post()`
- Used by all other components

**Language_Switcher** (`Core/Language_Switcher.php`)
- Utility for safe language switching with automatic restoration
- Methods: `with_language()`, `with_all_languages()`, `get_current_language()`
- Ensures language context is always restored after operations

### Pages/ - Page Translation

**Special_Page_Translator** (`Pages/Special_Page_Translator.php`)
- Translates checkout and success page IDs and URLs
- Hooks: `tec_tickets_commerce_checkout_page_id`, `tec_tickets_commerce_success_url`, etc.
- Ensures users are redirected to correct language version

**Page_Translation_Helper** (`Pages/Page_Translation_Helper.php`)
- Reusable helper for page translation operations
- Methods: `translate_page_url()`, `is_translated_page()`
- Used by `Special_Page_Translator`

### Meta/ - Meta Field Synchronization

**Meta_Sync** (`Meta/Meta_Sync.php`)
- Syncs ticket meta fields to all translations when updated
- Handles meta fields updated **after** `wp_update_post()` (WPML syncs too early)
- Listens to `updated_postmeta` hook
- Only syncs from original ticket to translations (prevents loops)

**Relationship_Meta_Translator** (`Meta/Relationship_Meta_Translator.php`)
- Translates relationship meta fields (like event IDs) when WPML copies them
- Listens to `wpml_after_copy_custom_field` hook
- Translates event IDs in ticket meta to point to correct language version

### Cart/ - Cart & Checkout Fixes

**Checkout_Cart_Fix** (`Cart/Checkout_Cart_Fix.php`)
- Fixes cart loading when checkout page is in different language than cart items
- Problem: Cart stores original (EN) ticket IDs, but checkout loads in translated (ES) language
- Solution: Temporarily switches to 'all' language context during cart loading
- Hooks: `template_redirect`, `wp_footer`, `shutdown`

### Tickets/ - Ticket Operations

**Ticket_Language_Assigner** (`Tickets/Ticket_Language_Assigner.php`)
- Assigns WPML language details to tickets when created
- Listens to `tribe_tickets_ticket_added` hook
- Ensures tickets have correct language metadata

**Attendee_Aggregator** (`Tickets/Attendee_Aggregator.php`)
- Aggregates attendee queries across all language translations
- Expands ticket/event IDs in queries to include all translations
- Hooks: `tribe_repository_tc_attendees_pre_get_posts`, `tec_tickets_attendees_filter_by_event`
- Ensures attendees are found regardless of which language ticket was used

## Key Flows

### 1. Ticket Creation Flow

```
1. User creates ticket in English event
   ↓
2. Ticket_Language_Assigner assigns language metadata
   ↓
3. User translates event to Spanish
   ↓
4. WPML creates Spanish ticket translation
   ↓
5. Relationship_Meta_Translator translates event ID in ticket meta
   ↓
6. Meta_Sync syncs price, stock, dates, etc. to Spanish ticket
```

### 2. Purchase Flow (Multilingual)

```
1. User views Spanish event page
   ↓
2. Adds ticket to cart (stores ticket ID)
   ↓
3. Redirects to checkout
   ↓
4. Special_Page_Translator redirects to Spanish checkout page
   ↓
5. Checkout_Cart_Fix switches to 'all' language to load cart items
   ↓
6. User completes purchase
   ↓
7. Special_Page_Translator redirects to Spanish success page
```

### 3. Meta Synchronization Flow

```
1. User updates ticket price in English
   ↓
2. Ticket.php updates _price meta AFTER wp_update_post()
   ↓
3. Meta_Sync detects _price update via updated_postmeta hook
   ↓
4. Triggers WPML sync: do_action('wpml_sync_custom_field')
   ↓
5. WPML copies _price to all translations
```

### 4. Attendee Query Flow

```
1. Admin queries attendees for Spanish ticket
   ↓
2. Attendee_Aggregator intercepts query
   ↓
3. Expands ticket ID to include all translations (EN + ES)
   ↓
4. Query returns attendees from all language versions
   ↓
5. Results aggregated correctly
```

## Important Concepts

### Language Context Switching

WPML filters `get_post()` by current language. This causes issues when:
- Cart stores EN ticket IDs but checkout loads in ES language
- Solution: `Language_Switcher::with_all_languages()` temporarily bypasses filtering

### Original vs Translation

- **Original ticket**: Created in default language, source of truth
- **Translation tickets**: Created by WPML, linked to original
- Meta sync only flows: Original → Translations (prevents loops)
- `Wpml_Adapter::is_original_post()` determines if ticket is original

### Late Meta Updates

Some ticket meta fields are updated **after** `wp_update_post()`:
- WPML syncs during `after_save_post` (too early)
- `Meta_Sync` catches these via `updated_postmeta` hook
- Triggers manual sync to ensure translations get updated values

### Page Translation Strategy

- Checkout/Success pages are regular WordPress pages
- Each language has its own page translation
- `Special_Page_Translator` ensures:
  - Page IDs are translated via filters
  - URLs point to correct language version
  - Current page detection works for all translations

## Dependencies

All components depend on:
- **Wpml_Adapter**: Injected via constructor (dependency injection)
- **Language_Switcher**: Static utility class, no dependencies

## Service Registration

`Integration.php` registers all services in order:
1. `Wpml_Adapter` (singleton, used by all)
2. Other services (singletons, auto-inject `Wpml_Adapter`)
3. Each service's `register()` method hooks into WordPress

## Common Patterns

### Checking WPML Availability
```php
if ( ! $this->wpml->is_available() ) {
    return;
}
```

### Translating Post IDs
```php
$translated_id = $this->wpml->translate_post_id( $original_id, 'page', $current_language, true );
```

### Safe Language Switching
```php
Language_Switcher::with_language( 'es', function() {
    // Code that needs Spanish context
} );
```

### Checking if Original
```php
if ( ! $this->wpml->is_original_post( $post_id, 'post_tec_tc_ticket' ) ) {
    return; // Only sync from original
}
```

## Troubleshooting Quick Reference

| Issue | Component to Check |
|-------|-------------------|
| Checkout redirects to wrong language | `Pages/Special_Page_Translator` |
| Cart items not loading | `Cart/Checkout_Cart_Fix` |
| Meta fields not syncing | `Meta/Meta_Sync` |
| Event ID wrong in ticket | `Meta/Relationship_Meta_Translator` |
| Attendees missing | `Tickets/Attendee_Aggregator` |
| Ticket has no language | `Tickets/Ticket_Language_Assigner` |

## File Organization Rationale

- **Core/**: Foundation utilities used by everything else
- **Pages/**: Isolated page translation logic
- **Meta/**: Isolated meta synchronization logic
- **Cart/**: Isolated cart/checkout fixes
- **Tickets/**: Ticket-specific operations

This organization follows **Separation of Concerns** - each folder has a single, clear responsibility.


