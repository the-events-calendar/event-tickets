# Changelog

### [5.27.4] 2026-01-28

* Tweak - Amend small typos in text domains. Props to @DAnn2012! [ET-2596]
* Language - 1 new strings added, 38 updated, 0 fuzzied, and 0 obsoleted.

### [5.27.3] 2025-12-18

* Security - Strengthen the user access level in the system information opt-in functionality. [SVUL-35]

### [5.27.2] 2025-12-09

* Fix - Fixed - Error in block editor that would prevent saving events with Tickets directly. [TECTRIA-1464]
* Language - 0 new strings added, 2 updated, 0 fuzzied, and 0 obsoleted.

### [5.27.1] 2025-12-03

* Fix - Resolved block editor JavaScript errors in WordPress 6.9 by properly importing `sprintf` from `@wordpress/i18n`. [ET-2595]
* Language - 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted.

### [5.27.0] 2025-11-18

* Version - Event Tickets 5.27.0 is only compatible with The Events Calendar 6.15.12 or higher.
* Version - Event Tickets 5.27.0 is only compatible with Event Tickets Plus 6.9.0 or higher.
* Tweak - Added filters: `tec_tickets_panels`
* Tweak - Added actions: `tec_tickets_ticket_pre_save`
* Tweak - Changed views: `v2/commerce/checkout/cart/items`, `v2/tickets/item`, `v2/tickets/item/quantity`, `v2/tickets/item/quantity/number`
* Tweak - Updates Shepherd library to 0.0.9 from 0.0.6 including various improvements. Shepherd's changelog can be found here https://github.com/stellarwp/shepherd/blob/0.0.9/CHANGELOG.md
* Tweak - Supports conditional ticket quantity control availability. [ETP-1061]
* Language - 1 new strings added, 52 updated, 2 fuzzied, and 3 obsoleted.

### [5.26.7] 2025-10-28

* Fix - Add logic to only show the purchase button during checkout after billing info is filled out. [ET-2592]
* Fix - Correct Stripe payment amount formatting by standardizing all currency values to two decimals before creating payment intents, preventing incorrect low charge amounts.[ET-2558]
* Fix - Prevent ticket overselling by adding database-locked stock validation before payment intent creation. [ET-1942]
* Tweak - Changed views: `v2/commerce/gateway/stripe/card-element`, `v2/commerce/gateway/stripe/payment-element`
* Tweak - Added actions: `tec_tickets_commerce_insufficient_stock_detected`, `tec_conditional_content_header_notice`
* Tweak - Added filters: `tec_tickets_commerce_gateway_value_formatter_{$gateway_key}_currency_map`
* Tweak - Add upsell link for Seating in License page. [ET-2556]
* Tweak - Modify the existing inline upsell to utilize the new modular logic in common. [ET-2590]
* Tweak - Tweak logic when fetching ticket data. [ET-2555]
* Language - 6 new strings added, 66 updated, 1 fuzzied, and 0 obsoleted.

### [5.26.6] 2025-10-14

* Security - Enhanced authorization validation for order processing endpoints. [SVUL-24]
* Language - 1 new strings added, 31 updated, 0 fuzzied, and 0 obsoleted.

### [5.26.5] 2025-09-16

* Fix - Adjusted how cart total is handled on page refresh to avoid coupons not being applied. [ETP-1060]
* Fix - Correctly invalidate ticket caches to deal with ETP order-of-operation issue. [ETP-1044]
* Language - 0 new strings added, 8 updated, 0 fuzzied, and 0 obsoleted.

### [5.26.4] 2025-09-10

* Security - Added user permission check when refreshing panels after ajax calls. [SVUL-20]
* Language - 1 new strings added, 14 updated, 0 fuzzied, and 0 obsoleted.

### [5.26.3] 2025-09-09

* Tweak - Added fees and coupons to emails. [ET-2547]
* Tweak - Changed views: `emails/purchase-receipt/body`, `emails/template-parts/body/order/order-total`, `emails/template-parts/body/order/ticket-totals`, `emails/template-parts/body/order/ticket-totals/coupons-row`, `emails/template-parts/body/order/ticket-totals/fees-row`, `emails/template-parts/body/order/ticket-totals/header-row`, `emails/template-parts/body/order/ticket-totals/total-row`, `emails/template-parts/header/head/series-pass-styles`, `emails/template-parts/header/head/styles`.
* Language - 1 new strings added, 13 updated, 0 fuzzied, and 1 obsoleted.

### [5.26.2] 2025-09-02

* Fix - Remove duplicate page title from the All Tickets page. [ET-2545]
* Fix - Completed Order email can handle again multiple recipients separated by comma. [ET-2551]
* Tweak - Adds a notice about the 2% fee when using the free Square payment gateway integration. [ET-2548]
* Language - 1 new strings added, 33 updated, 0 fuzzied, and 0 obsoleted.

### [5.26.1] 2025-08-26

* Fix - Correct some logic for loading the RSVP importer. Ensure the class it extends is available.
* Performance - Cache Views v2 ticket models preferably during updates to speed up frontend. [ETP-1021]
* Language - 0 new strings added, 24 updated, 0 fuzzied, and 0 obsoleted.

### [5.26.0.1] 2025-08-20

* Fix - Ensures the Actions Scheduler Logs table is present before attempting to use it. [TCMN-190]

### [5.26.0] 2025-08-19

* Version - Event Tickets 5.26.0 is only compatible with The Events Calendar 6.15.0 and higher.
* Performance - Improving TicketsCommerce Checkout by offloading tasks to Shepherd. [TCMN-185]
* [EXPERIMENTAL] Feature - Introduced new REST endpoints for managing your Tickets. Note: This API is for experimental use only and requires the X-TEC-EEA header. It may be subject to breaking changes in upcoming releases.
* Language - 74 new strings added, 4 updated, 3 fuzzied, and 0 obsoleted.

### [5.25.1.1] 2025-07-30

* Fix - Adds support for SCA (Strong Customer Authentication) for the Square Payment Gateway in TicketsCommerce. [ET-2542]
* Language - 0 new strings added, 3 updated, 0 fuzzied, and 0 obsoleted.

### [5.25.1] 2025-07-22

* Fix - Correct background color on "Get Tickets" button when dealing with series passes. [ET-2534]
* Language - 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted.

### [5.25.0] 2025-07-16

* Version - Event Tickets 5.25.0 is only compatible with Event Tickets Plus 6.7.0 or higher.
* Feature - Add in support for uncheckin through the REST API. [ETP-1000]
* Feature - For Offline checkin support added the optional `$details` parameter to be able to set the checkin time and device_id. [ETP-1003]
* Fix - Add "Orders" link in the admin page row actions menu for pages with Tickets Commerce tickets. [ET-2450]
* Fix - Add bail checks if Tickets Commerce is disabled to avoid a fatal on an event's Attendee page. [ET-2310]
* Fix - Add conditional to only show ticket description toggle if there is a description. [ET-2530]
* Fix - Added logic so deleted attendees will not count as deleted tickets. [ET-1002]
* Fix - Add extra check that items added to an order should be an array. Props to @TomGroot! [ET-2510]
* Fix - Ensure pending reservations are properly canceled when the Seat Selection page is closed. [SL-296]
* Fix - Fixed manual quantity input for tickets to respect shared capacity. [ET-2492]
* Fix - Fixed My Tickets link not working on Pages due to canonical redirect. [ET-2517]
* Fix - Fix the calculations when tickets are moved between events, so the correct number of available tickets is shown on list-based views. [ETP-994]
* Fix - Make sure add_submenu_page is called correctly to avoid deprecation messages. [TEC-5529]
* Fix - Make sure that the sales of tickets with unlimited capacity are tracked. [ET-2513]
* Fix - Prevents fatal error when activating WooCommerce through WP-CLI when Event Tickets plugin was already active. [ET-2532]
* Fix - Remove unused JS for TicketsCommerce settings and resolve stripe checkout template warning. [ET-2493]
* Tweak - Fixed sort order in the move attendees dialog to display posts alphabetically by title instead of by post ID. [ET-2305]
* Tweak - Added actions: `tribe_log`
* Tweak - Changed views: `v2/commerce/checkout/cart/item/details`, `v2/commerce/checkout/cart/item/details/toggle`, `v2/commerce/gateway/stripe/payment-element`
* Language - 1 new strings added, 93 updated, 1 fuzzied, and 1 obsoleted.

### [5.24.2] 2025-06-18

* Version - Event Tickets 5.24.2 is only compatible with Event Tickets Plus 6.6.1 or higher.
* Fix - Ensure wizard does not install The Events Calendar unless requested. [ET-2524]
* Fix - Correct an issue where seating timer was getting interrupted during checkout. [ET-2519]
* Fix - Ensure Tickets can be added to Posts using Block Editor. [ET-2516]
* Fix - Correctly formats the query for cleaning up stale webhook entries properly. [ET-2206]
* Language - 1 new strings added, 4 updated, 0 fuzzied, and 0 obsoleted.

### [5.24.1.1] 2025-06-12

* Version - Event Tickets 5.24.1.1 is only compatible with Event Tickets Plus 6.6.0 or higher.
* Fix - Prevent issues with NULL or empty TEXT column values. Improve database schema migration robustness and compatibility. Bump schema version from 1.1.0 to 1.2.0. [ET-2515]
* Language - 12 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted.

### [5.24.1] 2025-06-11

* Fix - Change the create table syntax to be compatible with MySQL.
* Fix - Fix incorrect ticket counts when using multiple providers on one ticketed post.
* Fix - Ensure the Tickets_Handler class can properly handle multiple ticket providers in connection queries.
* Tweak - Add new columns to `tec_ticket_groups` custom table and corresponding model properties for use by Ticket Presets.
* Tweak - Added actions: `tec_tickets_admin_tickets_page_before_register_tabs`, `tec_tickets_admin_tickets_page_after_register_tabs`, `tribe_tickets_metabox_end`, `tribe_events_tickets_bottom_start`
* Tweak - Changed views: `blocks/attendees/title`, `blocks/rsvp`, `blocks/rsvp/content`, `blocks/rsvp/details`, `blocks/rsvp/form`, `blocks/rsvp/form/details`, `blocks/rsvp/form/email`, `blocks/rsvp/form/form`, `blocks/rsvp/form/name`, `blocks/rsvp/form/opt-out`, `blocks/rsvp/form/quantity-input`, `blocks/rsvp/form/quantity`, `blocks/rsvp/form/submit-login`, `blocks/rsvp/loader`, `blocks/rsvp/status`, `blocks/rsvp/status/going`, `blocks/tickets`, `blocks/tickets/extra-available-quantity`, `blocks/tickets/extra-available`, `blocks/tickets/extra-price`, `blocks/tickets/item`, `blocks/tickets/quantity-number`, `blocks/tickets/registration/attendee/fields/select`, `blocks/tickets/registration/attendee/fields/text`, `blocks/tickets/registration/summary/ticket-price`, `components/attendees-list/attendees`, `components/attendees-list/attendees/attendee`, `components/attendees-list/title`, `emails/template-parts/body/order/ticket-totals/ticket-price`, `emails/template-parts/body/order/ticket-totals/ticket-quantity`, `emails/template-parts/body/series-events-list`, `emails/template-parts/header/head/series-pass-styles`, `modal/cart`, `modal/item-total`, `modal/registration-js`, `modal/registration`, `registration-js/attendees/fields/birth`, `registration-js/attendees/fields/checkbox`, `registration-js/attendees/fields/datetime`, `registration-js/attendees/fields/email`, `registration-js/attendees/fields/number`, `registration-js/attendees/fields/radio`, `registration-js/attendees/fields/select`, `registration-js/attendees/fields/telephone`, `registration-js/attendees/fields/text`, `registration-js/attendees/fields/url`, `registration-js/content`, `registration-js/mini-cart`, `registration/attendees/fields/checkbox`, `registration/attendees/fields/radio`, `registration/attendees/fields/select`, `registration/attendees/fields/text`, `seating/iframe-view`, `seating/tickets-block`, `shortcodes/my-attendance-list`, `tickets/email`, `tickets/orders-rsvp`, `tickets/rsvp`, `tickets/tpp`, `tickets/view-link`, `v2/commerce/checkout/cart`, `v2/commerce/checkout/cart/empty`, `v2/commerce/checkout/cart/empty/description`, `v2/commerce/checkout/cart/empty/title`, `v2/commerce/checkout/cart/footer`, `v2/commerce/checkout/cart/footer/total`, `v2/commerce/checkout/cart/header`, `v2/commerce/checkout/cart/item`, `v2/commerce/checkout/cart/item/details`, `v2/commerce/checkout/cart/item/details/description`, `v2/commerce/checkout/cart/item/details/extra`, `v2/commerce/checkout/cart/item/details/title`, `v2/commerce/checkout/cart/item/details/toggle`, `v2/commerce/checkout/cart/item/price`, `v2/commerce/checkout/cart/item/quantity`, `v2/commerce/checkout/cart/item/sub-total`, `v2/commerce/checkout/cart/items`, `v2/commerce/checkout/cart/ticket`, `v2/commerce/checkout/fields`, `v2/commerce/checkout/footer`, `v2/commerce/checkout/footer/gateway-error`, `v2/commerce/checkout/gateways`, `v2/commerce/checkout/header`, `v2/commerce/checkout/header/links`, `v2/commerce/checkout/header/links/back`, `v2/commerce/checkout/header/title`, `v2/commerce/checkout/must-login`, `v2/commerce/checkout/must-login/login`, `v2/commerce/checkout/must-login/registration`, `v2/commerce/checkout/order-modifiers/coupons`, `v2/commerce/checkout/order-modifiers/fees`, `v2/commerce/checkout/purchaser-info/address`, `v2/commerce/checkout/purchaser-info/city`, `v2/commerce/checkout/purchaser-info/country`, `v2/commerce/checkout/purchaser-info/email`, `v2/commerce/checkout/purchaser-info/name`, `v2/commerce/checkout/purchaser-info/state`, `v2/commerce/checkout/purchaser-info/zip`, `v2/commerce/gateway/paypal/advanced-payments`, `v2/commerce/gateway/paypal/advanced-payments/fields/card-name`, `v2/commerce/gateway/paypal/advanced-payments/fields/card-number`, `v2/commerce/gateway/paypal/advanced-payments/fields/cvv`, `v2/commerce/gateway/paypal/advanced-payments/fields/expiration-date`, `v2/commerce/gateway/paypal/advanced-payments/fields/submit`, `v2/commerce/gateway/paypal/advanced-payments/form`, `v2/commerce/gateway/paypal/advanced-payments/separator`, `v2/commerce/gateway/paypal/buttons`, `v2/commerce/gateway/paypal/checkout-script`, `v2/commerce/gateway/paypal/container`, `v2/commerce/gateway/paypal/order/details/capture-id`, `v2/commerce/gateway/stripe/card-element`, `v2/commerce/gateway/stripe/payment-element`, `v2/commerce/order/description`, `v2/commerce/order/description/order-empty`, `v2/commerce/order/description/order`, `v2/commerce/order/details/date`, `v2/commerce/order/details/email`, `v2/commerce/order/details/order-number`, `v2/commerce/order/details/total`, `v2/commerce/order/footer`, `v2/commerce/order/footer/links`, `v2/commerce/order/footer/links/back-home`, `v2/commerce/order/footer/links/browse-events`, `v2/commerce/order/header`, `v2/commerce/order/header/title-empty`, `v2/commerce/order/header/title`, `v2/commerce/success`, `v2/day/event/cost`, `v2/list/event/cost`, `v2/map/event-cards/event-card/actions/cost`, `v2/month/calendar-body/day/calendar-events/calendar-event/tooltip/cost`, `v2/month/mobile-events/mobile-day/mobile-event/cost`, `v2/photo/event/cost`, `v2/rsvp/ari/form/fields/email`, `v2/rsvp/ari/form/fields/name`, `v2/rsvp/ari/sidebar/quantity/input`, `v2/rsvp/form/fields/email`, `v2/rsvp/form/fields/name`, `v2/tickets`, `v2/tickets/commerce/fields`, `v2/tickets/footer`, `v2/tickets/footer/quantity`, `v2/tickets/footer/return-to-cart`, `v2/tickets/footer/total`, `v2/tickets/item/content`, `v2/tickets/item/content/description-toggle`, `v2/tickets/item/content/inactive`, `v2/tickets/item/content/sale-label`, `v2/tickets/item/content/title`, `v2/tickets/item/extra`, `v2/tickets/item/extra/available`, `v2/tickets/item/extra/available/quantity`, `v2/tickets/item/extra/available/unlimited`, `v2/tickets/item/extra/price`, `v2/tickets/item/inactive`, `v2/tickets/item/opt-out`, `v2/tickets/item/quantity-mini`, `v2/tickets/item/quantity`, `v2/tickets/item/quantity/add`, `v2/tickets/item/quantity/number`, `v2/tickets/item/quantity/remove`, `v2/tickets/item/quantity/unavailable`, `v2/tickets/items`, `v2/tickets/notice`, `v2/tickets/opt-out/hidden`, `v2/tickets/submit`, `v2/tickets/submit/button`, `v2/tickets/title`, `v2/week/grid-body/events-day/event/tooltip/cost`, `v2/week/mobile-events/day/event/cost`
* Language - 0 new strings added, 218 updated, 0 fuzzied, and 0 obsoleted.

### [5.24.0.1] 2025-06-06

* Fix - Fix some hardcoded asset paths. [TEC-5523]
* Fix - Harden PayPal authentication. [ET-2244]
* Fix - Utilize the newer version of our build process to ensure inline svgs are being handled properly. [TCMN-188]

### [5.24.0] 2025-06-03

* Version - Event Tickets 5.24.0 is only compatible with The Events Calendar 6.13.2 or higher.
* Feature - Introduced Help Hub, a centralized support and resource interface for enhanced user guidance and plugin assistance. [ET-2375]
* Feature - Introduced support for Square as a payment gateway for our TicketsCommerce. That comes with support for selling Tickets through Square's POS! [ET-2383]
* Fix - Allow seated attendees to be manually deleted even if the event has been deleted already [ET-2440]
* Fix - The purchaser form on TicketsCommerce checkout will only be displayed on logged out users.
* Tweak - Added actions: `tribe_log`, `tec_tickets_commerce_square_merchant_disconnected`, `tec_tickets_commerce_square_order_before_upsert`, `tec_tickets_commerce_square_order_after_upsert`, `tec_tickets_commerce_square_webhook_event`, `tec_tickets_commerce_square_ticket_out_of_sync`, `tec_tickets_commerce_square_sync_post_reset_status`, `tec_tickets_commerce_square_sync_inventory_changed_`, `tec_tickets_commerce_square_sync_inventory_changed`, `tec_tickets_commerce_square_sync_completed`, `tec_tickets_commerce_square_sync_ticket_id_mapping_`, `tec_tickets_commerce_square_sync_ticket_id_mapping`, `tec_tickets_commerce_square_sync_object_`, `tec_tickets_commerce_square_sync_object`, `tec_tickets_commerce_square_object_synced_`, `tec_tickets_commerce_square_object_synced`, `tec_tickets_commerce_square_sync_request_completed`
* Tweak - Added filters: `tec_tickets_commerce_gateway_webhook_maximum_attempts`, `tec_tickets_commerce_gateway_{$gateway_key}_webhook_maximum_attempts`, `tec_tickets_commerce_square_checkout_localized_data`, `tec_tickets_commerce_gateway_square_js_url`, `tec_tickets_commerce_square_order_customer_id`, `tec_tickets_commerce_square_order_payload`, `tec_tickets_commerce_square_payment_body`, `tec_tickets_commerce_square_create_from_order`, `tec_tickets_commerce_square_webhook_event_types`, `tec_tickets_commerce_square_order_endpoint_error_messages`, `tec_tickets_commerce_square_requests_chance_of_triggering_rate_limit_exception`, `tec_tickets_commerce_square_settings`, `tec_tickets_commerce_square_location_options`, `tec_tickets_commerce_square_sync_ticket_able_post_type_inventory_posts_per_page`, `tec_tickets_commerce_square_sync_inventory_query_args`, `tec_tickets_commerce_square_sync_post_type_posts_per_page`, `tec_tickets_commerce_square_sync_post_type_query_args`, `tec_tickets_commerce_square_sync_reset_post_type_data_schedule_events_to_delete_at_once`, `tec_tickets_commerce_square_sync_reset_post_type_data_schedule_events_to_delete_at_once_all_at_once`, `tec_tickets_commerce_square_event_item_data`, `the_content`, `tec_tickets_commerce_square_event_item_description_max_words`, `tec_tickets_commerce_square_event_data`, `tec_tickets_commerce_square_webhook_endpoint_url`, `tec_tickets_commerce_order_{$order->gateway}_get_value_refunded`, `tec_tickets_commerce_order_get_value_refunded`, `tec_tickets_commerce_order_{$order->gateway}_get_value_captured`, `tec_tickets_commerce_order_get_value_captured`, `tec_tickets_commerce_order_created_by`, `tec_tickets_ticket_about_to_go_to_sale_seconds`, `tec_tickets_commerce_tickets_currency_code`
* Tweak - Changed views: `v2/commerce/checkout`, `v2/commerce/checkout/cart`, `v2/commerce/checkout/cart/empty`, `v2/commerce/checkout/cart/empty/description`, `v2/commerce/checkout/cart/empty/title`, `v2/commerce/checkout/cart/footer`, `v2/commerce/checkout/cart/footer/quantity`, `v2/commerce/checkout/cart/footer/total`, `v2/commerce/checkout/cart/header`, `v2/commerce/checkout/cart/item`, `v2/commerce/checkout/cart/item/details`, `v2/commerce/checkout/cart/item/details/description`, `v2/commerce/checkout/cart/item/details/extra`, `v2/commerce/checkout/cart/item/details/title`, `v2/commerce/checkout/cart/item/details/toggle`, `v2/commerce/checkout/cart/item/price`, `v2/commerce/checkout/cart/item/quantity`, `v2/commerce/checkout/cart/item/sub-total`, `v2/commerce/checkout/cart/items`, `v2/commerce/checkout/cart/ticket`, `v2/commerce/checkout/fields`, `v2/commerce/checkout/footer`, `v2/commerce/checkout/footer/gateway-error`, `v2/commerce/checkout/gateways`, `v2/commerce/checkout/header`, `v2/commerce/checkout/header/links`, `v2/commerce/checkout/header/links/back`, `v2/commerce/checkout/header/title`, `v2/commerce/checkout/must-login`, `v2/commerce/checkout/must-login/login`, `v2/commerce/checkout/must-login/registration`, `v2/commerce/gateway/square/container`, `v2/tickets/item/content/sale-label`
* Tweak - Removed filters: `tec_tickets_commerce_gateway_stripe_webhook_maximum_attempts`
* Tweak - Tickets Commerce Flag Actions for controlling stock and attendees have increased precision to microtime to avoid concurrency problems
* Language - 149 new strings added, 287 updated, 0 fuzzied, and 1 obsoleted.

### [5.23.1] 2025-05-27

* Fix - Ensures symbolic links are followed on Assets Group Paths. [TCMN-187]
* Language - 0 new strings added, 10 updated, 0 fuzzied, and 0 obsoleted.

### [5.23.0] 2025-05-20

* Version - Event Tickets 5.23.0 is only compatible with The Events Calendar 6.13.0 or higher.
* Version - Event Tickets 5.23.0 is only compatible with Event Tickets Plus 6.5.0 or higher.
* Feature - Event Tickets Onboarding Wizard [ET-2339]
* Feature - Event Tickets Settings page revamped to match The Events Calendar
* Fix - Add defensive coding to custom_glance_items_attendees() to avoid a fatal. [ET-2404]
* Fix - Change the asset loading function from `tribe_asset` to `tec_asset` in various parts of the codebase.
* Language - 58 new strings added, 361 updated, 2 fuzzied, and 6 obsoleted.

### [5.22.0.1] 2025-05-14

* Fix - Prevents fatal if QR library in common has not loaded. [TEC-5497]
* Language - 0 new strings added, 38 updated, 0 fuzzied, and 5 obsoleted.

### [5.22.0] 2025-05-13

* Feature - Move QR-Related code from ET to Common [TEC-5426]
* Feature - Move QR code library to Common [TEC-5403]
* Fix - Fix an issue when applying 100% off coupons to Seating tickets. [ET-2409]
* Fix - Prevent instances of the `_load_textdomain_just_in_time` warning by moving all language after the `init` hook
* Tweak - Removed filters: `tec_tickets_qr_code_can_use`, `tribe_tickets_attendees_report_js_config`
* Tweak - Added actions: `tec_tickets_fully_loaded`, `tec_tickets_promoter_fully_loaded`
* Tweak - Removed actions: `tribe_tickets_plugin_loaded`
* Language - 0 new strings added, 5 updated, 0 fuzzied, and 4 obsoleted.

### [5.21.1.1] 2025-04-28

* Version - Event Tickets 5.21.1.1 is only compatible with The Events Calendar 6.11.2.1 or higher.
* Security - Added more safety checks to telemetry opt-ins/opt-outs. [TCMN-186]
* Language - 0 new strings added, 9 updated, 0 fuzzied, and 0 obsoleted.

### [5.21.1] 2025-04-07

* Fix - Ensures when TicketsCommerce is disabled, we don't identify pages as Checkout or Cart page for TicketsCommerce. [ET-2349]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted.

### [5.21.0] 2025-03-25

* Feature - Add the ability to create Coupons that can be applied to ticket checkouts sold through TicketsCommerce. [ET-2189]
* Tweak - Added filters: `tec_tickets_commerce_cart_cookie_expiration`, `tec_tickets_commerce_cart_repo_prepare_data`, `tec_tickets_commerce_cart_add_full_item_params`, `tec_tickets_commerce_paypal_order_unit`, `tec_tickets_commerce_order_modifiers_successful_save_message`, `tec_tickets_checkout_should_skip_item`, `tec_tickets_commerce_cart_transient_expiration`
* Tweak - Removed filters: `tec_tickets_commerce_cart_expiration`, `tec_tickets_commerce_order_modifiers_coupons_enabled`
* Tweak - Added actions: `tec_tickets_commerce_cart_process`
* Fix - Fix the input field for Fees (and Coupons) when the type is Percent and the thousands/decimal separators are set to "." and ","
* Language - 21 new strings added, 106 updated, 5 fuzzied, and 9 obsoleted.

### [5.20.1] 2025-03-13

* Feature - Added Ticket editor support for creating seating ticket with WooCommerce. [SL-209]
* Security - Ensure proper URL encoding for Admin URLS
* Tweak - Added filters: `tec_tickets_seating_frontend_ticket_block_data`
* Tweak - Changed views: `seating/seat-selection-timer`
* Language - 0 new strings added, 11 updated, 0 fuzzied, and 1 obsoleted.

### [5.20.0] 2025-03-06

* Feature - Adds Ticket actions for ticket goes on sale, ticket sale ended and ticket stock changed. [ETP-975]
* Feature - Introduced Waitlist entry points in Ticket and RSVP templates. [ETP-944]
* Tweak - Added actions: `tec_tickets_commerce_decrease_ticket_stock`, `tec_tickets_commerce_increase_ticket_stock`, `tec_tickets_ticket_dates_updated`, `tec_tickets_ticket_stock_added`, `tec_tickets_ticket_stock_changed`, `tec_tickets_ticket_{$prefix}_date_trigger`, `tec_tickets_ticket_upserted`
* Tweak - Added filters: `tec_tickets_rsvp_ids_to_sync`
* Tweak - Adding filters to Tickets and RSVP block for the ability to render components on top of those blocks. [ETP-954]
* Tweak - Changed views: `emails/confirmation`, `emails/spot-available`, `emails/template-parts/body/unsubscribe`, `tickets/my-tickets/user-details`, `v2/rsvp/content`, `v2/tickets`
* Tweak - Enrich ticket management JS hooks.
* Tweak - Firing a hook `tec.tickets.seating.setUsingAssignedSeating` whenever the `isUsingAssignedSeating` property is set. [ETP-973]
* Tweak - Init TicketsCommerce Module as soon as possible. [ETP-972]
* Tweak - Introduced hooks that fire during RSVP creation/update/deletion while in the block editor.
* Tweak - Introduce Waitlist email templates. [ETP-957]
* Tweak - Moved abstract class for custom tables into TCMN.
* Tweak - Move method `provide_events_results_to_ajax` one level higher so that it loads regardless of Tickets Commerce. [ETP-976]
* Fix - Ensure that Capacity and Stock handling now are handled in one single Action by Tickets Commerce and it respects Global Capacity.
* Fix - Restore Ticket's API capability checks to take place in controller and not in internal API. [ET-2313]
* Language - 2 new strings added, 55 updated, 0 fuzzied, and 1 obsoleted.

### [5.19.3] 2025-03-04

* Feature - Add In-App Notifications for Event Tickets [ET-2294]
* Tweak - Added actions: `tec_ian_icon`
* Tweak - Changed views: `tickets/my-tickets/user-details`, `tickets/orders`
* Fix - Replace form element wrapping the Checkout with section to avoid invalid HTML of form into form resulting to broken checkout with PayPal. [ET-2327]
* Fix - Corrected template override path for My Tickets page. [ET-2296]
* Fix - Ensure the Attendee Model for Tickets Commerce doesn't throw fatal errors when Order ID is invalid.
* Language - 14 new strings added, 121 updated, 0 fuzzied, and 1 obsoleted.

### [5.19.2] 2025-02-27

* Feature - Properly support `return_url` on the checkout page, so that payments like Klarna, AliPay and a couple others properly handle failed orders.
* Tweak - Improve how we handle webhooks with Stripe to avoid Orders to be left behind in status due to order of operations on Checkout page.
* Fix - Ensure refunds would put the stock back when handled by either Webhook or directly on checkout page
* Fix - Prevent problems related to Stripe checkout experience causing users to duplicate charges.
* Fix - Template conditional appearance, ensuring the SKU field appears when editting a Ticket created through WooCommerce. [ETP-996]

### [5.19.1.2] 2025-02-20

* Security - Hardened the API around ticket and attendee creation, editing, and deletion. Props to "the sneaky squirrel" for the report! [SVUL-14]
* Language - 0 new strings added, 7 updated, 0 fuzzied, and 0 obsoleted.

### [5.19.1.1] 2025-02-12

* Fix - Updated common library to correct issues with notifications around licensing.
* Fix - Add a callback to remove the `tribe_pue_key_notices` once on upgrade to version 6.5.1.1 [TEC-5384]
* Fix - Adjustments were made to prevent a fatal error when tec_pue_checker_init was triggered too early, attempting to call tribe_is_truthy() before it was available. The license check and active plugin monitoring now run on admin_init to ensure proper loading. [TEC-5384]
* Fix - Update the license checker to ignore empty licenses. [TEC-5385]
* Language - 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted.

### [5.19.1] 2025-02-10

* Tweak - Modify price field in seat report information to include formatted price, not just value. [SL-266]
* Tweak - Refactored a hidden ticket provider field within the classic editor for RSVPs and tickets. [ET-2287]
* Fix - Added version number to the `editor.js` script to stop caching. [ET-2293]
* Fix - Correct row and total calculation in the seat selection modal. [SL-266]
* Fix - When updating a Ticket's price on block editor, while the ticket is on sale, won't overwrite the sale price in  WooCommerce. [ET-2100]
* Fix - Changed the way translations are loaded to work with the latest WordPress language changes.
* Tweak - Removed filters: `tribe_events_tickets_module_name`
* Language - 7 new strings added, 133 updated, 0 fuzzied, and 0 obsoleted.

### [5.19.0] 2025-01-30

* Fix - Update asset, dependencies, customizations to align with WordPress 6.7 and React 18. [TEC-5322]
* Language - 0 new strings added, 12 updated, 0 fuzzied, and 0 obsoleted.

### [5.18.1.1] 2025-01-27

* Security - Prevent bug where order ID spoofing for Tickets Commerce would potentially display order data publicly [SVUL-11]

### [5.18.1] 2025-01-22

* Feature - Include Seating information in Attendee archive REST API response. [SL-264]
* Tweak - Add filter to customize the cart hash cookie name for Tickets Commerce. [ET-2269]
* Tweak - Introduced methods `can_change_to` for Statuses and `can_transition_to` for Orders. [ET-2281]
* Tweak - Introduced various hooks for the order lock system and order completed checkout actions. [ET-2281]
* Tweak - Lazy load attendees report page asset filter to properly localize asset data [ET-2274]
* Tweak - Added filters: `tec_tickets_commerce_attendee_update_args`, `tec_tickets_commerce_attendee_update`, `tec_tickets_commerce_cart_hash_cookie_name`, `tec_tickets_commerce_order_{$gateway_key}_upsert_args`, `tec_tickets_commerce_order_upsert_args`, `tec_tickets_commerce_order_upsert_existing_order_id`, `tec_tickets_commerce_order_{$gateway_key}_update_args`, `tec_tickets_commerce_order_update_args`, `tec_tickets_commerce_order_modifier_valid_statuses`, `tec_tickets_rest_attendee_archive_data`
* Tweak - Removed filters: `tec_tickets_commerce_order_modifier_status_flags`
* Tweak - Added actions: `tec_tickets_commerce_attendee_before_update`, `tec_tickets_commerce_attendee_after_update`, `tec_tickets_commerce_order_locked`, `tec_tickets_commerce_order_unlocked`, `tec_tickets_commerce_order_checkout_completed`
* Tweak - Changed views: `v2/tickets/item`
* Fix - Added a default empty array to `maybe_disable_foreign_key_checks`. [ET-2275]
* Fix - Attendee generation during order status transition becomes aware if attendees have been already generated. [ET-2282]
* Fix - Introduce Order lock mechanism to ensure 2 or more action that could update the order, they dont so at the same time. [ET-2279]
* Fix - Prevent duplicate orders and as a result duplicated attendees when a payment would initially fail at least once. [ET-2280]
* Fix - Reverts aggressive hook update, causing fatals on installation with themes overwriting the template `Single Ticket Item`. [ET-2276]
* Fix - Screen options will no longer be disabled when Event Tickets is active. [ET-2273]
* Fix - Ticket sales will be counted correctly during status transitions of the orders they belong to. [ET-2286]
* Performance - Enhance the performance of order modifier database queries [ET-2268]
* Deprecated - Method `should_payment_intent_be_updated` since its no longer needed. [ET-2281]
* Language - 3 new strings added, 111 updated, 2 fuzzied, and 2 obsoleted.

### [5.18.0.1] 2025-01-07

* Fix - Resolves problem related to duplication of attendees while using Stripe webhooks with Tickets Commerce. [ET-2279]

### [5.18.0] 2024-12-17

* Feature - Added option to detach assigned seating tickets from layout and revert them to regular tickets. [SL-214]
* Feature - Add support for max ticket purchase limit filter with seating tickets. [SL-205]
* Feature - Introducing Booking Fees. A way to set up Fees for your Tickets being sold through TicketsCommerce. [ET-2189]
* Tweak - Added actions: `tec_tickets_commerce_checkout_cart_before_footer_quantity`
* Tweak - Added filters: `tec_tickets_commerce_prepare_order_for_email_send_email_completed_order`, `tec_tickets_commerce_prepare_order_for_email_send_email_purchase_receipt`, `tec_tickets_commerce_paypal_order_get_unit_data_{$type}`, `tec_tickets_commerce_paypal_order_get_unit_data`, `tec_tickets_commerce_stripe_create_from_cart`, `tec_tickets_commerce_order_modifiers_api_role`, `tec_tickets_commerce_order_modifiers_coupons_enabled`, `tec_tickets_commerce_order_modifiers_repository_class`, `tec_tickets_commerce_order_modifiers_model_class`, `tec_tickets_commerce_order_modifiers_page_url`, `tec_tickets_commerce_order_modifier_display_amount`, `tec_tickets_commerce_order_modifier_generate_slug`, `tec_tickets_commerce_order_modifier_status_display`, `tec_tickets_commerce_order_modifier_types`, `tec_tickets_commerce_order_modifiers`, `tec_tickets_commerce_order_modifier_default_type`, `tec_tickets_commerce_order_modifier_status_flags`, `tec_tickets_commerce_single_orders_items_item_should_be_displayed`
* Tweak - Changed views: `v2/commerce/checkout/cart/footer`, `v2/commerce/checkout/cart/footer/total`, `v2/commerce/checkout/order-modifiers/coupons`, `v2/commerce/checkout/order-modifiers/fees`
* Tweak - Removed outdated ticket duration tooltips. [ET-2263]
* Fix - Correctly calculate and set the session expiration date in the seat selection modal. [n/a]
* Fix - Ensure that `number_format` is used with a float value to prevent issues with PHP 8.0+. [ETP-962]
* Fix - Hide seating reservation settings when seating license is not valid. [SL-248]
* Fix - Order updates for asynchronous payment methods in Stripe will update correctly. [ET-2082]
* Fix - The Attendee Registration page is now compatible with Full Site Editor themes. [ET-2266]
* Fix - Users will not be able to RSVP for unpublished events or posts. [ET-2267]
* Language - 127 new strings added, 353 updated, 2 fuzzied, and 5 obsoleted.

### [5.17.0.1] 2024-11-21

* Tweak - Introduced filter `tec_tickets_rest_api_archive_results` that gives the ability to filter out the tickets being provided to the REST API archive.
* Security - Prevent Tickets from showing through REST API to unauthorized requests. [SVUL-9]

### [5.17.0] 2024-11-19

* Version - Event Tickets 5.17.0 is only compatible with Event Tickets Plus 6.1.1 or higher.
* Feature - Add Reservation timer settings for seating tickets. [Sl-213]
* Feature - Enable duplicate layout functionality for seating. [SL-65]
* Feature - Reset Seat Layouts data when a new license is connected.
* Feature - Update Seating assets into using Group Paths. [SL-246]
* Tweak - Added filters: `tec_tickets_seating_checkout_grace_time`
* Tweak - Added proper notice for invalid seating license. [SL-208]
* Tweak - Cache Seating service status checks; better messaging. [SL-239]
* Tweak - Increase payment failure correction timer to 60 seconds. [SL-233]
* Fix - Avoid enqueue seating assets where they are not required. [SL-250]
* Fix - Fixed styling issues for modals and dropdowns. [SL-202][SL-203]
* Fix - Remove default value from sessions table column to avoid database update issues.
* Language - 71 new strings added, 143 updated, 11 fuzzied, and 2 obsoleted.

### [5.16.1] 2024-11-04

* Fix - Attendee Registration page will work with FSE Themes. [ET-2261]
* Fix - Issue preventing ticket creation on an unsaved ticket-able post type, while no Seating license is present. [ET-2264]
* Fix - Include backwards compatibility for deprecated proprieties in the Settings class used in The Events Calendar and Event Tickets [TEC-5312]

### [5.16.0.1] 2024-10-30

* Fix - Resolved a fatal error that prevented the Sessions table from being set up on some databases with stricter settings. [ET-2262]

### [5.16.0] 2024-10-30

* Version - Event Tickets 5.16.0 is only compatible with Event Tickets Plus 6.1.0 or higher.
* Version - Event Tickets 5.16.0 is only compatible with The Events Calendar 6.8.0 or higher.
* Feature - Added per-event Seats tab for managing attendees with assigned seating.
* Feature - Integrate with the new premium Seating Builder SaaS to create Seat Maps and Layouts for assigned seating.
* Feature - Introduced new premium Seating option for selling tickets with assigned seating.
* Tweak - Added actions: `tec_tickets_seating_tab_{$tab}`, `tec_tickets_seating_session_interrupt`, `tec_tickets_seating_invalidate_layouts_cache`, `tec_tickets_seating_invalidate_maps_layouts_cache`, `tec_tickets_seating_delete_reservations_from_attendees`, `tec_tickets_seating_deleted_reservations_from_attendees`, `tec_tickets_seating_reservations_updated`, `tec_tickets_seating_seat_selection_timer`
* Tweak - Added filters: `tec_tickets_seating_active`, `tec_tickets_seating_service_base_url`, `tec_tickets_seating_service_frontend_url`, `tec_tickets_seating_tickets_block_html`, `tec_tickets_seating_session_cookie_expiration_time`, `tec_tickets_seating_selection_timeout`, `tec_tickets_seat_selection_timer_expired_data`, `tec_tickets_seating_fetch_attendees_per_page`, `tec_tickets_seating_ephemeral_token`, `tec_tickets_seating_ephemeral_token_site_url`, `tec_tickets_attendees_page_render_context`, `tec_tickets_attendees_table_sortable_columns`, `tribe_tickets_ticket_inventory`
* Tweak - Added license key field and SaaS connection UI for premium Seating tool.
* Tweak - Added Seat column to Attendees tab and page for attendees with assigned seating.
* Tweak - Added Seat Layout setting to per-event Ticket Settings
* Tweak - Added two new Site Health checks for Seating.
* Tweak - Changed views: `emails/template-parts/header/head/styles`, `seating/iframe-view`, `seating/seat-selection-timer`, `seating/tickets-block-error`, `seating/tickets-block`, `v2/tickets/item`
* Tweak - New compact frontend ticket display for events with assigned seating tickets.
* Tweak - Removed superfluous tool tip from capacity options in block editor.
* Tweak - Show seat assignment on My Tickets page for attendees with assigned seating.
* Tweak - Show seat assignment on tickets for attendees with assigned seating.

### [5.15.0] 2024-10-21

* Fix - Tickets Commerce orders through Stripe no longer will create duplicate attendees. [ET-2256]
* Fix - Order Completed page will no longer throw a fatal when visiting it directly. [ET-2253]
* Fix - If users added an index to the `post_meta` table on `meta_value` using `CONCAT()` should speed up queries for them. [GTRIA-1236]
* Fix - Possible miscounted ticketed or un-ticketed events in the events admin list [ET-2221]
* Fix - Some dates in admin screens were not translated [TEC-4873]
* Fix - Wrong ticket stock when attendees were moved between tickets [ET-2098]
* Fix - Fix issue with svg display in settings page. [TEC-5282]
* Tweak - Modify language around ticket capacity on "Tickets" block to improve clarity.
* Tweak - Added filters: `tec_tickets_admin_tickets_table_default_status`, `tec_tickets_admin_tickets_table_default_sort_by`, `tec_tickets_admin_tickets_table_default_sort_order`, `tec_tickets_admin_tickets_table_columns`, `tec_tickets_admin_tickets_table_default_hidden_columns`, `tec_tickets_admin_tickets_table_sortable_columns`, `tec_tickets_admin_tickets_table_column_default`, `tec_tickets_admin_tickets_table_column_default_{$column_name}`, `tec_tickets_admin_tickets_table_column_name`, `tec_tickets_admin_tickets_table_column_id`, `tec_tickets_admin_tickets_table_event_actions`, `tec_tickets_admin_tickets_table_column_event`, `tec_tickets_admin_tickets_table_column_start_date`, `tec_tickets_admin_tickets_table_column_end_date`, `tec_tickets_admin_tickets_table_column_days_left`, `tec_tickets_admin_tickets_table_column_price`, `tec_tickets_admin_tickets_table_column_sold`, `tec_tickets_admin_tickets_table_column_remaining`, `tec_tickets_admin_tickets_table_column_sales`, `tec_tickets_admin_tickets_table_query_args`, `tec_tickets_admin_tickets_table_status_options`, `tec_tickets_admin_tickets_table_provider_info`, `tec_tickets_admin_tickets_page_url`, `tec_tickets_admin_tickets_screen_options_show_screen`, `tec_tickets_attendees_user_can_export_csv`, `tec_tickets_attendees_table_cache_key`, `tec_tickets_search_attendees_default`
* Tweak - Added actions: `tec_tickets_editor_list_table_title_icon_`, `tec_tickets_ticket_duplicated`, `tec_tickets_tickets_duplicated`
* Language - 0 new strings added, 61 updated, 0 fuzzied, and 0 obsoleted

### [5.14.0] 2024-10-09

* Feature - Added new Tickets Home page to view and manage all tickets in a central location. [ET-2173]
* Fix - Fix attendee search caching, and add search-related filters. [ET-2218]
* Fix - Allow Admin and Editor roles to export Attendees CSV. [ET-2226]
* Fix - Handle duplicating Tickets during event duplication [ECP-1826].
* Fix - Send attendees by email feature will now function correctly. [ET-2223]
* Fix - Event's ticket availability calculations. Total event's availability could be miscalculated depending on the order of the tickets. [ET-2222].
* Tweak - Added filters: `tec_tickets_admin_tickets_table_default_status`, `tec_tickets_admin_tickets_table_default_sort_by`, `tec_tickets_admin_tickets_table_default_sort_order`, `tec_tickets_admin_tickets_table_columns`, `tec_tickets_admin_tickets_table_default_hidden_columns`, `tec_tickets_admin_tickets_table_sortable_columns`, `tec_tickets_admin_tickets_table_column_default`, `tec_tickets_admin_tickets_table_column_default_{$column_name}`, `tec_tickets_admin_tickets_table_column_name`, `tec_tickets_admin_tickets_table_column_id`, `tec_tickets_admin_tickets_table_event_actions`, `tec_tickets_admin_tickets_table_column_event`, `tec_tickets_admin_tickets_table_column_start_date`, `tec_tickets_admin_tickets_table_column_end_date`, `tec_tickets_admin_tickets_table_column_days_left`, `tec_tickets_admin_tickets_table_column_price`, `tec_tickets_admin_tickets_table_column_sold`, `tec_tickets_admin_tickets_table_column_remaining`, `tec_tickets_admin_tickets_table_column_sales`, `tec_tickets_admin_tickets_table_query_args`, `tec_tickets_admin_tickets_table_status_options`, `tec_tickets_admin_tickets_table_provider_info`, `tec_tickets_admin_tickets_page_url`, `tec_tickets_admin_tickets_screen_options_show_screen`, `tec_tickets_attendees_user_can_export_csv`, `tec_tickets_attendees_table_cache_key`, `tec_tickets_search_attendees_default`
* Tweak - Added actions: `tec_tickets_editor_list_table_title_icon_`, `tec_tickets_ticket_duplicated`, `tec_tickets_tickets_duplicated`
* Language - 1 new strings added, 73 updated, 1 fuzzied, and 2 obsoleted

### [5.13.4] 2024-09-26

* Fix - Load the full Payment Element if we have Wallets enabled. [ETP-942]
* Language - 0 new strings added, 0 updated, 0 fuzzied, and 0 obsoleted

### [5.13.3.1] 2024-09-16

* Security - Improve sanitization and escaping for Administration screens of ticket purchases.
* Security - Improve general escaping for ORM queries to prevent legacy Events methods to be used for SQL injections.

### [5.13.3] 2024-09-11

* Feature - Added Individual Order Screen in the Admin to improve the Order Management for Tickets Commerce. [ET-2150]
* Tweak - Tweaked `setupCompactCardElement` method to allow filtering of options using the existing `tec_tickets_commerce_stripe_checkout_localized_data` filter. [ET-2259]
* Tweak - Made a string translatable in `getting-started.php` file. (props to @DAnn2012) [ET-2215]
* Tweak - Added filters: `post_updated_messages`
* Tweak - Added actions: `tribe_tickets_commerce_order_actions_box_start`
* Language - 44 new strings added, 39 updated, 1 fuzzied, and 0 obsoleted

### [5.13.2] 2024-08-16

* Tweak - Start Sale and End Sale date will auto-populate when creating a new ticket. [ET-2103]
* Tweak - Update legacy Wallet Plus plugin notices to the new Tickets Plus plugin.
* Fix - Exporting all Attendees as a CSV file in the new Tickets Attendees Page. [ET-2094]
* Fix - Shared capacity will no longer be affected by any of the unlimited sales tickets on the same event. [ETP-920]

### [5.10.0] 2024-05-14

* Version - Event Tickets 5.10.0 is only compatible with The Events Calendar 6.5.0 and higher
* Fix - Update to remove moment.js library due to security concerns. [TEC-5011]
* Language - 0 new strings added, 35 updated, 0 fuzzied, and 0 obsoleted

### [5.9.2] 2024-05-08

* Feature - Added support for adding Free tickets using Tickets Commerce. [ET-1218]
* Tweak - When using Events Calendar Pro, the duplicate event function will now duplicate tickets as well. [ET-2073]
* Fix - Corrected an issue where PayPal orders had an extra slash on the order table page. [ET-2076]
* Fix - Updated sale label font to be uniform with other Event Tickets elements. [ET-2074]
* Fix - Fixed showing error on Order report export data for Tickets Commerce.
* Tweak - Added filters: `tec_tickets_attendees_page_url`, `tec_tickets_commerce_is_free_ticket_allowed`, `tec_tickets_commerce_value_get_currency_display`, `tec_tickets_attendees_table_column_check_in`, `tec_tickets_attendees_table_query_args`, `tec_tickets_attendees_page_is_enabled`
* Tweak - Changed views: `emails/template-parts/body/order/order-gateway-data`, `emails/template-parts/body/order/order-total`, `emails/template-parts/body/order/payment-info`, `emails/template-parts/body/ticket/number-from-total`, `emails/template-parts/body/tickets-total`, `tickets/attendees-email`, `tickets/email-non-attendance`, `tickets/email-ticket-type-moved`, `tickets/email-tickets-moved`, `tickets/email`, `tickets/my-tickets`, `tickets/my-tickets/attendee-label`, `tickets/my-tickets/orders-list`, `tickets/my-tickets/ticket-information`, `tickets/my-tickets/tickets-list`, `tickets/my-tickets/title`, `tickets/my-tickets/user-details`, `tickets/orders-pp-tickets`, `tickets/orders-rsvp`, `tickets/orders-tc-tickets`, `tickets/orders`, `tickets/rsvp`, `tickets/tpp-return-to-cart`, `tickets/tpp-success`, `tickets/tpp`, `tickets/view-link`, `v2/commerce/gateway/free/button`, `v2/commerce/gateway/free/container`, `v2/commerce/order/details/payment-method`, `v2/commerce/ticket/regular-price`, `v2/commerce/ticket/sale-price`
* Language - 2 new strings added, 68 updated, 0 fuzzied, and 0 obsoleted

### [5.9.1.1] 2024-04-25

* Fix - Corrected the Attendees page when languages other than English are used. [GTRIA-1268]
* Tweak - Added filters: `tec_tickets_attendees_page_url`, `tec_tickets_attendees_table_column_check_in`, `tec_tickets_attendees_table_query_args`, `tec_tickets_attendees_page_is_enabled`
* Tweak - Changed views: `emails/template-parts/body/ticket/number-from-total`, `emails/template-parts/body/tickets-total`, `tickets/attendees-email`, `tickets/email-non-attendance`, `tickets/email-ticket-type-moved`, `tickets/email-tickets-moved`, `tickets/email`, `tickets/my-tickets`, `tickets/my-tickets/attendee-label`, `tickets/my-tickets/orders-list`, `tickets/my-tickets/ticket-information`, `tickets/my-tickets/tickets-list`, `tickets/my-tickets/title`, `tickets/my-tickets/user-details`, `tickets/orders-pp-tickets`, `tickets/orders-rsvp`, `tickets/orders-tc-tickets`, `tickets/orders`, `tickets/rsvp`, `tickets/tpp-return-to-cart`, `tickets/tpp-success`, `tickets/tpp`, `tickets/view-link`
* Language - 0 new strings added, 38 updated, 0 fuzzied, and 0 obsoleted

### [5.9.1] 2024-04-18

* Fix - Avoid error on order report page if no valid tickets are available for that event.
* Fix - Fixed an issue with Ticket repository that was causing all tickets to be fetched for 0 as event ID. [ET-2023]
* Fix - Display recurring events are not supported warning while adding tickets on Community Events. [ECP-1671]
* Fix - The Attendee registration page will no longer generate warnings when viewing it. [ET-906]
* Fix - When an event ticket is removed, it will no longer generate a 404 for the event. [TEC-5041]
* Fix - Remove unwanted slashes from the Tickets Emails subject line. [ET-2061]
* Fix - `Get Tickets` button padding will be consistent in `active` and `focus` states. [ET-2068]
* Fix - Correct the text domain for a couple of text strings so they could be translated appropriately. [ET-2020]
* Fix - QR codes will properly generate when on PHP 8.1 and above. [ET-2062]
* Fix - Changed incorrect file paths in DocBlocks for template overrides for all files in `src/views/tickets`. [ET-2004]
* Fix - Added additional logic to handle when the Gateway ID link is null on the Orders Page for Stripe. [ET-2067]
* Feature - Add new Attendees page. [ET-1707]
* Tweak - Added filters: `tec_tickets_attendees_page_url`, `tec_tickets_attendees_table_column_check_in`, `tec_tickets_attendees_table_query_args`, `tec_tickets_attendees_page_is_enabled`
* Tweak - Changed views: `emails/template-parts/body/ticket/number-from-total`, `emails/template-parts/body/tickets-total`, `tickets/attendees-email`, `tickets/email-non-attendance`, `tickets/email-ticket-type-moved`, `tickets/email-tickets-moved`, `tickets/email`, `tickets/my-tickets`, `tickets/my-tickets/attendee-label`, `tickets/my-tickets/orders-list`, `tickets/my-tickets/ticket-information`, `tickets/my-tickets/tickets-list`, `tickets/my-tickets/title`, `tickets/my-tickets/user-details`, `tickets/orders-pp-tickets`, `tickets/orders-rsvp`, `tickets/orders-tc-tickets`, `tickets/orders`, `tickets/rsvp`, `tickets/tpp-return-to-cart`, `tickets/tpp-success`, `tickets/tpp`, `tickets/view-link`
* Language - 18 new strings added, 163 updated, 1 fuzzied, and 2 obsoleted

### [5.9.0] 2024-04-04

* Feature - Sale Price for Tickets Commerce: Set a sale price for individual tickets for a certain duration of time within Tickets Commerce.
* Feature - Sale Price Design: Display the set sale price so that it is clear that a ticket or purchased ticket is on sale.
* Tweak - Removed filters: `tec_tickets_commerce_order_report_summary_should_include_event_sales_data`
* Tweak - Changed views: `v2/commerce/ticket/price`, `v2/commerce/ticket/regular-price`, `v2/commerce/ticket/sale-price`, `v2/tickets/item/content/sale-label`, `v2/tickets/item/content/title`, `v2/tickets/item/extra/price`
* Language - 12 new strings added, 16 updated, 0 fuzzied, and 0 obsoleted

### [5.8.4] 2024-03-25

* Fix - Events Calendar Pro promo shouldn't show when it's already installed or when not editing an event. [ET-2018]
* Fix - Addressed a problem preventing the export of event attendees by email. [ETP-904]
* Fix - In the block editor, ticket will no longer be deleted when you open the ticket block settings. [ET-2046]
* Fix - Show post excerpt line breaks within ticket emails. [ET-2006]
* Fix - Front-end tickets block button padding is now consistent on hover and when disabled. [ET-2035]
* Fix - Allow blank sender name and email to be stored within Tickets Emails settings. [ET-2008]
* Fix - Corrected an issue where `attendees_table->prepare_items()` was being called multiple times. [ET-2005]
* Fix - Tickets block will be properly registered when creating a new post or page. [ET-2045]
* Fix - Corrected an issue where the Post Tickets ORM method `filter_by_has_tickets` would prepare an empty statement. [ET-2017]
* Tweak - Added additional fields to the Event Tickets Site Health section. [ET-2017]
* Feature - Add the Series Pass email template. [ET-1854]
* Tweak - Adjusted the logic for calculating fees when using Stripe. [ET-2015]
* Tweak - Added filters: `tec_tickets_email_class`
* Tweak - Changed views: `emails/series-pass`, `emails/template-parts/body/additional-content`, `emails/template-parts/body/post-description`, `emails/template-parts/body/series-events-list`, `emails/template-parts/body/series-pass-dates`, `emails/template-parts/body/thumbnail`, `emails/template-parts/header/head/series-pass-styles`, `emails/template-parts/header/head/styles`
* Language - 22 new strings added, 91 updated, 0 fuzzied, and 0 obsoleted

### [5.8.3] 2024-03-12

* Fix - Fixed updating stock data when Tickets Commerce attendees are moved. [ET-2009]
* Fix - Fixed showing duplicate order overview data from TribeCommerce when ETP is disabled. [ET-2011]
* Fix - Stock will be calculated correctly when an order fails and then succeeds while using Tickets Commerce. [ET-1833]
* Fix - Decode any HTML entities that appear in the subject line of outgoing emails. [ET-2007]
* Fix - Fixed multiple issues related to series pass check-ins. [ET-1936]
* Fix - Site Health will no longer fatal when providers are not setup. [ET-2047]
* Tweak - Use dynamic ticket labels within the block editor's Tickets Block. [ET-690]
* Tweak - Added filters: `tec_tickets_move_attendees_ids`, `tec_tickets_attendee_manual_uncheckin_success_data`, `tec_tickets_attendee_uncheckin`
* Security - Added filterable role access to the attendee page (`tec_tickets_attendees_page_role_access`). [SVUL-1]
* Security - Added filterable role access to the orders page (`tec_tickets_report_{$page_slug}_page_role_access`). [SVUL-1]
* Language - 1 new strings added, 56 updated, 2 fuzzied, and 0 obsoleted

### [5.8.2] 2024-02-19

* Feature - Support per Event attendance for Series Pass Attendees for manual and app-based check-ins. [ET-1936]
* Fix - Available number when moving Series Pass Attendees. [ET-2009]
* Language - 5 new strings added, 167 updated, 14 fuzzied, and 9 obsoleted

### [5.8.1] 2024-02-06

* Fix - Removed type casting from filter method of series pass to avoid fatal errors. [ET-2014]
* Fix - Ensure correct attendee information is included in the attendee emails. [ET-1988]
* Fix - Resolve deprecation notices regarding `ArrayAccess::offsetGet()` [ET-1949]
* Fix - Resolve edge case usages for Shortcode with Attendees Listing.
* Fix - Resolved an issue where Order Status was not populated when exporting the Attendee List using Tickets Commerce. [ET-1883]
* Fix - Ticket is removed now when using the delete option from the block editor. [ET-1879]
* Fix - Update button will now show when the opt-out checkbox shows on the My Tickets page. [ET-1980]
* Fix - Update usage of `method_exists()` to comply with PHP 8.1 standards. [ET-1759]
* Tweak - Added additional information to the Site Health Section and added `tec_tickets_site_health_subsections` filter. [ET-1925]
* Tweak - Added Export option to Ticket Commerce Order page and `tec_tc_order_report_export_args` filter. [ET-1872]
* Tweak - Added Print button to Ticket Commerce Orders. [ET-1873]
* Tweak - Customer name appears now as description of a Stripe payment. Added `tec_tickets_commerce_stripe_update_payment_description` filter. [ET-1607]
* Tweak - Declared dynamic properties in Tribe__Tickets__Main, Tribe__Tickets__Tickets_Handler, Tribe__Tickets__REST__V1__Messages to prevent warnings in php 8.2 [ET-1950]
* Tweak - Update default footer text of Tickets Emails to include link to website. [ET-1971]
* Tweak - Added filters: `tec_tc_order_report_export_args`, `tec_tickets_commerce_stripe_update_payment_description`, `tec_tickets_site_health_subsections`
* Tweak - Changed views: `blocks/tickets/footer`, `emails/template-parts/body/footer/credit`, `registration-js/content`, `registration/button-cart`, `tickets/orders`, `v2/tickets/footer/return-to-cart`
* Language - 31 new strings added, 77 updated, 0 fuzzied, and 4 obsoleted

### [5.8.4] 2024-03-25

* Fix - Events Calendar Pro promo shouldn't show when it's already installed or when not editing an event. [ET-2018]
* Fix - Addressed a problem preventing the export of event attendees by email. [ETP-904]
* Fix - In the block editor, ticket will no longer be deleted when you open the ticket block settings. [ET-2046]
* Fix - Show post excerpt line breaks within ticket emails. [ET-2006]
* Fix - Front-end tickets block button padding is now consistent on hover and when disabled. [ET-2035]
* Fix - Allow blank sender name and email to be stored within Tickets Emails settings. [ET-2008]
* Fix - Corrected an issue where `attendees_table->prepare_items()` was being called multiple times. [ET-2005]
* Fix - Tickets block will be properly registered when creating a new post or page. [ET-2045]
* Fix - Corrected an issue where the Post Tickets ORM method `filter_by_has_tickets` would prepare an empty statement. [ET-2017]
* Tweak - Added additional fields to the Event Tickets Site Health section. [ET-2017]
* Feature - Add the Series Pass email template. [ET-1854]
* Tweak - Adjusted the logic for calculating fees when using Stripe. [ET-2015]
* Tweak - Added filters: `tec_tickets_email_class`
* Tweak - Changed views: `emails/series-pass`, `emails/template-parts/body/additional-content`, `emails/template-parts/body/post-description`, `emails/template-parts/body/series-events-list`, `emails/template-parts/body/series-pass-dates`, `emails/template-parts/body/thumbnail`, `emails/template-parts/header/head/series-pass-styles`, `emails/template-parts/header/head/styles`

### [5.8.3] 2024-03-12

* Fix - Fixed updating stock data when Tickets Commerce attendees are moved. [ET-2009]
* Fix - Fixed showing duplicate order overview data from TribeCommerce when ETP is disabled. [ET-2011]
* Fix - Stock will be calculated correctly when an order fails and then succeeds while using Tickets Commerce. [ET-1833]
* Fix - Decode any HTML entities that appear in the subject line of outgoing emails. [ET-2007]
* Fix - Fixed multiple issues related to series pass check-ins. [ET-1936]
* Fix - Site Health will no longer fatal when providers are not setup. [ET-2047]
* Tweak - Use dynamic ticket labels within the block editor's Tickets Block. [ET-690]
* Tweak - Added filters: `tec_tickets_move_attendees_ids`, `tec_tickets_attendee_manual_uncheckin_success_data`, `tec_tickets_attendee_uncheckin`
* Security - Added filterable role access to the attendee page (`tec_tickets_attendees_page_role_access`). [SVUL-1]
* Security - Added filterable role access to the orders page (`tec_tickets_report_{$page_slug}_page_role_access`). [SVUL-1]
* Language - 1 new strings added, 56 updated, 2 fuzzied, and 0 obsoleted

### [5.8.2] 2024-02-19

* Feature - Support per Event attendance for Series Pass Attendees for manual and app-based check-ins. [ET-1936]
* Fix - Available number when moving Series Pass Attendees. [ET-2009]
* Language - 5 new strings added, 167 updated, 14 fuzzied, and 9 obsoleted

### [5.8.1] 2024-02-07

* Fix - Removed type casting from filter method of series pass to avoid fatal errors. [ET-2014]
* Fix - Ensure correct attendee information is included in the attendee emails. [ET-1988]
* Fix - Resolve deprecation notices regarding `ArrayAccess::offsetGet()` [ET-1949]
* Fix - Resolved an issue where Order Status was not populated when exporting the Attendee List using Tickets Commerce. [ET-1883]
* Fix - Ticket is removed now when using the delete option from the block editor. [ET-1879]
* Fix - Update button will now show when the opt-out checkbox shows on the My Tickets page. [ET-1980]
* Fix - Update usage of `method_exists()` to comply with PHP 8.1 standards. [ET-1759]
* Tweak - Added additional information to the Site Health Section and added `tec_tickets_site_health_subsections` filter. [ET-1925]
* Tweak - Added Export option to Ticket Commerce Order page and `tec_tc_order_report_export_args` filter. [ET-1872]
* Tweak - Added Print button to Ticket Commerce Orders. [ET-1873]
* Tweak - Customer name appears now as description of a Stripe payment. Added `tec_tickets_commerce_stripe_update_payment_description` filter. [ET-1607]
* Tweak - Declared dynamic properties in Tribe__Tickets__Main, Tribe__Tickets__Tickets_Handler, Tribe__Tickets__REST__V1__Messages to prevent warnings in php 8.2 [ET-1950]
* Tweak - Update default footer text of Tickets Emails to include link to website. [ET-1971]
* Tweak - Added filters: `tec_tc_order_report_export_args`, `tec_tickets_commerce_stripe_update_payment_description`, `tec_tickets_site_health_subsections`
* Tweak - Changed views: `blocks/tickets/footer`, `emails/template-parts/body/footer/credit`, `registration-js/content`, `registration/button-cart`, `tickets/orders`, `v2/tickets/footer/return-to-cart`

### [5.8.0] 2024-01-22

* Version - Event Tickets 5.8.0 is only compatible with The Events Calendar 6.3.0 and higher.
* Version - Event Tickets 5.8.0 is only compatible with Event Tickets Plus 5.9.0 and higher.
* Feature - New ticket type field for all Ticket Post Types.
* Feature - New type of Ticket: Series Passes to start enabling Recurring Event Ticketing.
* Enhancement - Improved the Design and UX of the Attendees and Orders page.
* Tweak - Added filters: `tec_tickets_ticket_panel_data`, `tec_tickets_ticket_type_default_header_description`, `tec_tickets_enabled_ticket_forms_{$post_type}`, `tec_tickets_allow_tickets_on_recurring_events`, `tec_tickets_commerce_order_report_summary_should_include_event_sales_data`, `tec_tickets_repository_filter_by_event_id`, `tec_recurring_tickets_enabled`, `tec_tickets_flexible_tickets_editor_data`, `tec_tickets_find_ticket_type_host_posts_query_args`, `tec_tickets_attendees_filter_by_event`, `tec_tickets_attendees_filter_by_event_not_in`, `tec_tickets_attendees_event_details_top_label`, `tec_tickets_editor_configuration_localized_data`, `tec_tickets_panel_list_helper_text`, `tec_tickets_normalize_occurrence_id`, `tec_tickets_is_ticket_editable_from_post`, `tec_tickets_my_tickets_link_ticket_count_by_type`, `tec_tickets_editor_list_ticket_types`, `tec_tickets_editor_list_table_data`, `tec_tickets_editor_list_table_data_{$ticket_type}`, `tribe_tickets_block_show_unlimited_availability`, `tec_tickets_get_event_capacity`
* Tweak - Removed filters: `tec_tickets_hide_view_link`
* Tweak - Added actions: `tec_flexible_tickets_activated`, `tec_tickets_panels_before`, `tec_tickets_panels_after`, `tec_tickets_ticket_update`, `tec_tickets_ticket_add`, `tec_tickets_list_row_edit`, `tec_tickets_editor_list_table_title_icon_{$ticket_type}`, `tec_tickets_editor_list_table_before`, `tec_tickets_ticket_form_main_start`, `tec_tickets_ticket_form_main_start_{$ticket_type}`
* Tweak - Changed views: `blocks/attendees/view-link`, `emails/template-parts/body/order/attendees-table/header-row`, `tickets/email`, `tickets/my-tickets`, `tickets/my-tickets/orders-list`, `tickets/my-tickets/tickets-list`, `tickets/my-tickets/title`, `tickets/orders`, `tickets/view-link`, `v2/tickets/item`, `v2/tickets/items`, `v2/tickets/series-pass/header`
* Language - 62 new strings added, 212 updated, 9 fuzzied, and 15 obsoleted

### [5.7.1] 2023-12-13

* Tweak - Prevented Single Attendee endpoint from throwing a notice on PHP 8+. [ET-1935]
* Tweak - Attendees listed on the `Your Tickets` section will now match the order they were entered in. [ET-1924]
* Tweak - Notify users of Wallet Plus availability on attendees page. [ET-1938]
* Tweak - Notify users of Wallet Plus availability on Tickets Emails settings page. [ET-1937]
* Tweak - Add attendee name to attendees list after purchase or registration. [ET-1939]
* Tweak - Added filter `tec_tickets_commerce_gateway_stripe_webhook_valid_key_polling_attempts` to allow the modification of how many attempts Tickets Commerce will poll for Stripe webhooks for validation.
* Fix - Tickets Commerce Stripe webhooks properly handles internally validating the Secret Signing key. [ET-1511]
* Fix - Tickets Commerce Stripe Charge and Payment Intent webhooks no longer create duplicated success emails for a single ticket purchase. [ET-1792]
* Language - 3 new strings added, 42 updated, 0 fuzzied, and 0 obsoleted

### [5.7.0] 2023-11-16

* Version - Event Tickets 5.7.0 is only compatible with The Events Calendar 6.2.7 and higher.
* Version - Event Tickets 5.7.0 is only compatible with Event Tickets Plus 5.8.0 and higher.
* Feature - Include all the features to have Wallet Plus compatibility into Event Tickets.
* Tweak - Add tickets to the Tickets Commerce success page. [ETWP-30]
* Tweak - Add tickets to the RSVP block confirmation state. [ETWP-62]
* Language - 30 new strings added, 30 updated, 0 fuzzied, and 0 obsoleted

### [5.6.8.1] 2023-11-09

* Version - Event Tickets 5.6.8.1 is only compatible with The Events Calendar 6.2.6.1 and higher
* Fix - Update a common library to prevent possible fatals. [TEC-4978]
* Language - 0 new strings added, 9 updated, 0 fuzzied, and 0 obsoleted

### [5.6.8] 2023-11-08

* Tweak - Added tabs for navigating between Attendees and Orders in the Tickets Commerce admin. [ET-1867]
* Tweak - Added action `tec_tickets_commerce_reports_tabbed_view_before_register_tab` and `tec_tickets_commerce_reports_tabbed_view_after_register_tab` allow adding third-party tabs. [ET-1867]
* Tweak - Added filter `tec_tickets_commerce_reports_tabbed_page_title` and `tec_tickets_commerce_reports_tabbed_view_tab_map` allow granular control over how Tickets Commerce tabs behave. [ET-1867]

### [5.6.7] 2023-11-01

* Feature - Tickets Commerce orders report page design update. [ET-1810]
* Tweak - Re-styled Empty RSVP Block in Block Editor to match frontend design. Styles will be the same in block editor and in the user interface [ET-1818]
* Tweak - Re-styled Inactive RSVP Block in Block Editor to match frontend design. Styles will be the same in block editor and in the user interface [ET-1823]
* Tweak - Re-styled Active RSVP Block in Block Editor to match frontend design. Styles will be the same in block editor and in the user interface [ET-1825]
* Tweak - Re-styled Create and Edit RSVP Tickets in Block Editor.  [ET-1836]
* Tweak - Re-styled Inactive Tickets Block in Block Editor. Empty state now has a new design [ET-1817]
* Tweak - Re-styled Inactive Tickets Block with tickets. Inactive state with tickets has a new design [ET-1822]
* Tweak - Re-styled Active Tickets Block with tickets. Add information icons and tooltips.  [ET-1824]
* Tweak - Re-styled Create and Edit Tickets in Block Editor.  [ET-1835]
* Tweak - Re-styled Tickets Settings in Block Editor.  [ET-1834]
* Tweak - Using react-number-format to display price.  [ET-1885]
* Tweak - Declared dynamic properties for Attendees page to avoid deprecation warnings.
* Fix - Orders title in admin page.  [ET-1868]
* Fix - Typo on My Tickets when using Ticket Commerce only. [ET-1909]
* Language - 3 new strings added, 60 updated, 0 fuzzied, and 1 obsoleted

### [5.6.6.1] 2023-10-12

* Fix - Correct a problem that can cause a fatal when plugins are deactivated in a certain order. [TEC-4951]

### [5.6.6] 2023-10-11

* Version - Event Tickets 5.6.6 is only compatible with Event Tickets Plus 5.7.6 and higher.
* Tweak - Include a QRCode Library in Event Tickets to improve conformity across other addons. [ETWP-29]
* Fix - The Attendee Registration feature will now use the modal by default.  [ETP-882]
* Fix - Restore possibility to move Tickets and Attendees to Single Events that are part of a Series. [ET-1862]
* Fix - When using the block editor, the Attendee Information modal will be properly sized. [ETP-883]
* Fix - Ticket property invalidation to ensure capacity, inventory and availability are correctly invalidated. [ET-1887]
* Fix - Fix order status sometimes showing incorrectly for sites in languages other than English. [ET-1875]
* Fix - When transferring tickets from one event to another using Tickets Commerce, the capacity and stock levels for each ticket should be correctly updated. [ETP-866]
* Fix - Avoid showing duplicate warning related to recurring event on ticket meta box for new event submissions using Community Events. [ECP-1538]
* Fix - Prevent fatal related to Events Calendar Pro on older versions triggering internal service provider to be loaded when it shouldn't be. [ET-1886]
* Language - 1 new strings added, 13 updated, 0 fuzzied, and 0 obsoleted

### [5.6.5.1] 2023-09-28

* Version - Event Tickets 5.6.5.1 is only compatible with The Events Calendar 6.2.2.1 and higher.
* Fix - Fix - Correct issue where Telemetry would register active plugins multiple times. [TEC-4920]

### [5.6.5] 2023-09-13

* Version - Event Tickets 5.6.5 is only compatible with The Events Calendar 6.2.2 and higher.
* Tweak - Ticket names over 125 characters will now be truncated when being sent to Paypal. [ET-1865]
* Tweak - Validate check-in data before updating with attendee update REST endpoint. [ET-1863]
* Tweak - Implement new design for Attendees Page, Ticket Overview. [ET-1840]
* Tweak - Added notice regarding the availability of Paystack for Tickets Commerce. [ET-1763]
* Tweak - Improve performance of the post admin list. [ET-1870]
* Tweak - Removed some deprecated filter_vars to avoid PHP 8.1 warnings. [ET-1800]
* Fix - Corrected wallet settings names in Tickets Commerce Stripe checkout code. [ET-1866]
* Fix - Incorrect ticket count in Ticket email within the Tickets Emails feature. [ET-1832]
* Fix - Added support for filtering attendees by TicketsCommerce order status. [ET-1863]
* Fix - Prevent Fatal error around Promoter usage of Firebase\JWT\JWT for encryption. [ET-1876]
* Fix - Prevent some button background styles from being overridden by theme editors. [ET-1815]
* Language - 15 new strings added, 67 updated, 1 fuzzied, and 1 obsoleted

### [5.6.4] 2023-08-16

* Fix - Fixed translation issues with translating month names in other languages while displaying ticket available message. [ET-1820]
* Fix - Ensure the Attendees page displays correctly when accessed through the Events Manager. [ECP-1527]
* Tweak - The Attendee Registration page will now display properly when using Divi with dynamic CSS enabled. [ETP-864]
* Tweak - Include Event/Post title alongside Ticket name on PayPal order notification emails. [ET-1770]
* Fix - Include Commerce tickets in cached results; correctly fetch posts without tickets. [ET-1808]
* Tweak - Cache Tickets objects for performance improvements. [ET-1808]
* Tweak - Remove some PHP 8.1 deprecation warnings. [ET-1830]
* Fix - Prevention of creating tickets in Classic Editor for recurring events when using custom tables. [ET-1826]
* Fix - Prevention of creating tickets in Block Editor for recurring events when using custom tables. [ET-1827]
* Tweak - Add a notice in the Tickets Commerce Paypal settings for non-https sites. [ET-1773]
* Fix - Footer links in Tickets Emails template using the wrong color. [ET-1784]
* Tweak - Capitalize payment provider names in Tickets Emails. [ET-1776]
* Fix - Removal of double-escaped characters in Tickets Emails sender's name. [ET-1777]
* Language - 2 new strings added, 89 updated, 0 fuzzied, and 0 obsoleted

### [5.6.3] 2023-07-18

* Feature - Integrated Yoast Duplicate Post for seamless duplication of tickets, while cloning events. [ET-760]
* Tweak - Add notice about the availability of Paystack for Tickets Commerce. [ET-1764]
* Tweak - Improve performance in admin due to unnecessary Tickets Commerce calls being made. [ET-1736]
* Tweak - Refactored CSS for Tickets Emails to better conform to email client CSS standards. [ET-1802]
* Tweak - Added filters: `tec_tickets_filter_event_id`, `tec_tickets_hide_attendee_list_optout`
* Tweak - Changed views: `emails/template-parts/header/head/styles`
* Fix - Updating total shared capacity should properly update each ticket capacity and stock. [ETP-854]
* Fix - Fixed get tickets link anchor from event listings for new ticket views. [ET-1768]
* Fix - Ticketed Commerce events will now be accurately categorized and counted under the ticketed tab in the Dashboard Event List. [ET-1774]
* Fix - The attendee export functionality for old converted recurring events has been improved to accurately export attendees. [ET-1739]
* Fix - The Attendee List will now be correctly displayed when the 'Show attendees list on event page' option is enabled within the classic editor.  [ETP-623]
* Fix - Fixed moving attendees from deleted tickets to new tickets of same type. [ET-1577]
* Language - 1 new strings added, 77 updated, 0 fuzzied, and 0 obsoleted

### [5.6.2] 2023-06-29

* Tweak - Introduced new the filters `tec_tickets_commerce_order_page_title` and `tec_tickets_attendees_order_view_title` to allow customizing the Order Report Page title [ET-1737]
* Tweak - Added filters: `tec_tickets_commerce_order_page_title`, `tec_tickets_attendees_order_view_title`
* Tweak - Removed filters: `tribe_tickets_attendees_show_view_title`
* Fix - Updated the page heading when on the Orders Report page. [ET-1737]
* Fix - When no providers are enabled, a warning will display above the `New Ticket` and `New RSVP` area explaining that at least one must be enabled. [ET-1696]
* Fix - Corrected an issue with the `New ticket` button having invalid HTML. [ET-1631]
* Fix - Resolved an issue that caused compatibility problems between specific themes and the Attendee Registration page. [ET-1767]
* Language - 3 new strings added, 41 updated, 1 fuzzied, and 0 obsoleted

### [5.6.1.2] 2023-06-23

* Fix - Ensure there is backwards compatibility with Extensions and Pods.

### [5.6.1.1] 2023-06-22

* Fix - Prevent Telemetry from being initialized and triggering a Fatal when the correct conditionals are not met.

### [5.6.1] 2023-06-22

* Version - Event Tickets 5.6.1 is only compatible with The Events Calendar 6.1.2 and higher.
* Version - Event Tickets 5.6.1 is only compatible with Event Tickets Plus 5.7.1 and higher.
* Version - Event Tickets 5.6.1 is only compatible with Community Events 4.9.3 and higher.
* Fix - Lock our container usage(s) to the new Service_Provider contract in tribe-common. This prevents conflicts and potential fatals with other plugins that use a di52 container.
* Fix - Email templates overrides now works as expected. [ET-1780]

### [5.6.0.2] 2023-06-21

* Fix - Prevent Attendee list from throwing a notice on PHP 8+.
* Fix - Adjusted our PHP Exception usage to protect against third-party code causing fatals when attempting to access objects that have not been initialized.

### [5.6.0.1] 2023-06-20

* Fix - Increase the reliability of Telemetry initialization for Event Tickets loading [TEC-4836]
* Fix - Resolved issues with Attendee Registration not being bound correctly on loading. [ET-1771]
* Tweak - Added actions: `tec_telemetry_modal`
* Tweak - Changed views: `blocks/tickets/submit`

### [5.6.0] 2023-06-15

* Feature - Introduction of Tickets Emails, the new and improved solution for managing Event Tickets related emails.
* Tweak - Display order details link for TicketsCommerce providers on Orders page Gateway ID column. [ET-1729]
* Tweak - Add support for Gmail JSON LD markup of Ticket Emails. [ET-1601][ET-1637]
* Tweak - Removed Freemius integration in favor of Telemetry an in-house info solution.
* Tweak - Added filters: `tec_tickets_emails_dispatcher`, `tec_tickets_emails_{$email_slug}_dispatcher`, `tec_tickets_emails_dispatcher_headers`, `tec_tickets_emails_dispatcher_{$email_slug}_headers`, `tec_tickets_emails_dispatcher_attachments`, `tec_tickets_emails_dispatcher_{$email_slug}_attachments`, `tec_tickets_emails_dispatcher_to`, `tec_tickets_emails_dispatcher_{$email_slug}_to`, `tec_tickets_emails_dispatcher_subject`, `tec_tickets_emails_dispatcher_{$email_slug}_subject`, `tec_tickets_emails_dispatcher_content`, `tec_tickets_emails_dispatcher_{$email_slug}_content`, `tec_tickets_email_json_ld_{$type}_schema_data`, `tec_tickets_emails_{$email->slug}_json_ld_schema`, `tec_tickets_emails_json_data_encode_options`, `tec_tickets_send_rsvp_email_pre`, `tec_tickets_send_rsvp_non_attendance_confirmation_pre`, `tec_tickets_send_tickets_email_for_attendee_pre`
* Tweak - Removed filters: `tec_tickets_emails_heading_plural`, `tec_tickets_emails_{$this->slug}_heading_plural`, `tec_tickets_emails_subject_plural`, `tec_tickets_emails_{$this->slug}_subject_plural`, `tec_tickets_emails_headers`, `tec_tickets_emails_{$this->slug}_headers`, `tec_tickets_emails_attachments`, `tec_tickets_emails_{$this->slug}_attachments`, `tec_tickets_emails_default_emails`, `tec_tickets_emails_post_type_args`
* Tweak - Changed views: `v2/emails/admin-new-order`, `v2/emails/customer-purchase-receipt`, `v2/emails/new-order/body`, `v2/emails/purchase-receipt/body`, `v2/emails/purchase-receipt/intro`, `v2/emails/rsvp-not-going`, `v2/emails/rsvp-not-going/body`, `v2/emails/rsvp`, `v2/emails/rsvp/body`, `v2/emails/template-parts/body`, `v2/emails/template-parts/body/add-content`, `v2/emails/template-parts/body/footer`, `v2/emails/template-parts/body/footer/content`, `v2/emails/template-parts/body/footer/credit`, `v2/emails/template-parts/body/header`, `v2/emails/template-parts/body/header/image`, `v2/emails/template-parts/body/order/attendees-table`, `v2/emails/template-parts/body/order/attendees-table/attendee-email`, `v2/emails/template-parts/body/order/attendees-table/attendee-info`, `emails/template-parts/body/order/attendees-table/attendee-name`, `v2/emails/template-parts/body/order/attendees-table/custom-fields`, `v2/emails/template-parts/body/order/attendees-table/header-row`, `v2/emails/template-parts/body/order/attendees-table/ticket-id`, `v2/emails/template-parts/body/order/attendees-table/ticket-title`, `v2/emails/template-parts/body/order/customer-purchaser-details`, `v2/emails/template-parts/body/order/error-message`, `emails/template-parts/body/order/order-gateway-data`, `v2/emails/template-parts/body/order/order-total`, `v2/emails/template-parts/body/order/payment-info`, `v2/emails/template-parts/body/order/event-title`, `v2/emails/template-parts/body/order/purchaser-details/date`, `v2/emails/template-parts/body/order/purchaser-details/email`, `v2/emails/template-parts/body/order/purchaser-details/name`, `v2/emails/template-parts/body/order/purchaser-details/order-number`, `v2/emails/template-parts/body/order/ticket-totals`, `v2/emails/template-parts/body/order/ticket-totals/header-row`, `v2/emails/template-parts/body/order/ticket-totals/ticket-price`, `v2/emails/template-parts/body/order/ticket-totals/ticket-quantity`, `v2/emails/template-parts/body/order/ticket-totals/ticket-row`, `v2/emails/template-parts/body/order/ticket-totals/ticket-title`, `emails/template-parts/body/post-description`, `emails/template-parts/body/post-title`, `v2/emails/template-parts/body/ticket/holder-name`, `v2/emails/template-parts/body/ticket/number-from-total`, `v2/emails/template-parts/body/ticket/security-code`, `v2/emails/template-parts/body/ticket/ticket-name`, `v2/emails/template-parts/body/tickets-total`, `v2/emails/template-parts/body/tickets`, `v2/emails/template-parts/body/title`, `v2/emails/template-parts/footer`, `v2/emails/template-parts/footer/footer-preview`, `v2/emails/template-parts/footer/footer`, `v2/emails/template-parts/header`, `v2/emails/template-parts/header/head/json-ld`, `v2/emails/template-parts/header/head/meta`, `v2/emails/template-parts/header/head/scripts`, `v2/emails/template-parts/header/head/styles`, `v2/emails/template-parts/header/head/title`, `v2/emails/template-parts/header/header-preview`, `v2/emails/template-parts/header/header`, `v2/emails/template-parts/header/top-link`, `v2/emails/ticket`, `v2/emails/ticket/body`, `v2/emails/admin-failed-order`, `v2/emails/failed-order/body`, `v2/emails/template-parts/body/order/attendees-table/attendee-name`, `v2/emails/template`
* Fix - Tickets Commerce PayPal sandbox connection problem resolved.
* Language - 19 new strings added, 154 updated, 1 fuzzied, and 24 obsoleted

### [5.5.11.1] 2023-05-09

* Fix - Admin Dashboard loading slowly while counting attendees. [ET-1698]
* Fix - Resolve Fatal occurring for some Tickets Commerce users around Order Models and Cart usage. [ET-1735]

### [5.5.11] 2023-05-04

* Tweak - Add Ticket data with attendee data as checkin response. [ET-1694]
* Tweak - Added the ability to disable the Attendees column on the Events admin dashboard using `tec_tickets_admin_post_type_table_column` or `tec_tickets_admin_post_type_table_column_{$column}`. [ET-1701]
* Tweak - Save number of attendees checked-in via Event Tickets Plus app. [ET-1695]
* Fix - When using Tickets Commerce tickets under the price of $1 will no longer display improperly. Deprecated `maybe_reset_cost_format`. [ET-1697]
* Language - 21 new strings added, 85 updated, 0 fuzzied, and 10 obsoleted

### [5.5.10] 2023-04-03

* Tweak - Added functionality to properly restock deleted attendee ticket for Tickets Commerce. [ETP-860]
* Tweak - Add the Attendee count for the site to the `At a Glance` admin widget. [ET-1654]
* Tweak - Add `post_title` data for attendees created using Tickets Commerce. [ET-1590]
* Tweak - Added filters: `tec_tickets_emails_heading_plural`, `tec_tickets_emails_{$this->slug}_heading_plural`, `tec_tickets_emails_subject_plural`, `tec_tickets_emails_{$this->slug}_subject_plural`, `tec_tickets_emails_{$this->slug}_from_email`, `tec_tickets_emails_{$this->slug}_from_name`, `tec_tickets_emails_{$this->slug}_headers`, `tec_tickets_emails_{$this->slug}_attachments`, `tec_tickets_emails_{$this->slug}_placeholders`, `tec_tickets_emails_recipient`, `tec_tickets_emails_{$this->slug}_recipient`, `tec_tickets_emails_subject`, `tec_tickets_emails_{$this->slug}_subject`, `tec_tickets_emails_heading`, `tec_tickets_emails_{$this->slug}_heading`, `tec_tickets_emails_additional_content`, `tec_tickets_emails_{$this->slug}_additional_content`, `tec_tickets_emails_settings`, `tec_tickets_emails_{$this->slug}_settings`, `tribe_tickets_rsvp_tickets_to_send`
* Tweak - Removed filters: `tec_tickets_emails_heading_`, `tec_tickets_emails_subject_`, `tribe_rsvp_non_attendance_email_subject`
* Tweak - Changed views: `v2/emails/customer-completed-order`, `v2/emails/rsvp-not-going`, `v2/emails/rsvp-not-going/body`, `v2/emails/template-parts/body/title`
* Fix - When using Tickets Commerce the SKU will properly appear when creating a ticket using Community Tickets. [CT-64]
* Fix - Fixed Tickets/RSVP blocks crashing when hovering over their tooltips. [ET-1674]
* Fix - Undefined $going variable on Ajax request. [ET-1612]
* Language - 33 new strings added, 83 updated, 1 fuzzied, and 6 obsoleted

### [5.5.9.1] 2023-03-13

* Fix - Fixed unlimited capacity tickets showing as sold out on calendar views. [ET-1678]
* Fix - Fix fatal on the attendees screen when accessing as a non-admin user. [ET-1679]

### [5.5.9] 2023-03-08

* Tweak - Code maintenance for the attendees screen. [ET-1635]
* Tweak - Save activation time for Event Tickets. [ET-1639]
* Tweak - Added wrapper method to fetch RSVP ticket not going option data. [ETP-843]
* Tweak - Save last check-in time for tickets scanned via the Event Tickets Plus APP. [ET-1640]
* Tweak - Added filters: `tec_tickets_emails_heading_`, `tec_tickets_emails_subject_`, `tec_tickets_emails_from_email`, `tec_tickets_emails_from_name`, `tec_tickets_emails_headers`, `tec_tickets_emails_attachments`, `tec_tickets_emails_placeholders`, `tec_tickets_emails_format_string`, `tec_tickets_emails_registered_emails`, `tec_tickets_emails_default_emails`, `tec_tickets_emails_post_type_args`
* Tweak - Removed filters: `tribe_tickets_caps_can_manage_attendees`
* Tweak - Added actions: `tribe_log`
* Tweak - Changed views: `v2/emails/admin-failed-order`, `v2/emails/admin-new-order`, `v2/emails/customer-completed-order`, `v2/emails/email-template`, `v2/emails/email-template/body`, `v2/emails/email-template/body/add-links`, `v2/emails/email-template/body/date`, `v2/emails/email-template/body/event-description`, `v2/emails/email-template/body/event-image`, `v2/emails/email-template/body/event-title`, `v2/emails/email-template/body/footer-content`, `v2/emails/email-template/body/footer`, `v2/emails/email-template/body/greeting`, `v2/emails/email-template/body/header-image`, `v2/emails/email-template/body/header`, `v2/emails/email-template/body/recipient-name`, `v2/emails/email-template/body/ticket-info`, `v2/emails/email-template/body/top-link`, `v2/emails/email-template/main`, `v2/emails/email-template/preview`, `v2/emails/email-template/style`, `v2/emails/rsvp`, `v2/emails/template-parts/body`, `v2/emails/template-parts/body/event/date`, `v2/emails/template-parts/body/event/description`, `v2/emails/template-parts/body/event/image`, `v2/emails/template-parts/body/event/links`, `v2/emails/template-parts/body/event/title`, `v2/emails/email-template/body/event-location`, `v2/emails/template-parts/body/footer`, `v2/emails/template-parts/body/footer/content`, `v2/emails/email-template/body/footer-credit`, `v2/emails/template-parts/body/header`, `v2/emails/template-parts/body/header/image`, `v2/emails/template-parts/body/ticket/holder-name`, `v2/emails/template-parts/body/ticket/number-from-total`, `v2/emails/template-parts/body/ticket/security-code`, `v2/emails/template-parts/body/ticket/ticket-name`, `v2/emails/template-parts/body/tickets-total`, `v2/emails/template-parts/body/tickets`, `v2/emails/template-parts/body/title`, `v2/emails/template-parts/footer`, `v2/emails/template-parts/footer/footer-preview`, `v2/emails/template-parts/footer/footer`, `v2/emails/template-parts/header`, `v2/emails/template-parts/header/head/json-ld`, `v2/emails/template-parts/header/head/meta`, `v2/emails/template-parts/header/head/scripts`, `v2/emails/template-parts/header/head/styles`, `v2/emails/template-parts/header/head/title`, `v2/emails/template-parts/header/header-preview`, `v2/emails/template-parts/header/header`, `v2/emails/template-parts/header/top-link`, `v2/emails/template`, `v2/emails/ticket`
* Fix - Fixed shared capacity ticket counts not showing properly on calendar views. [ETP-851]
* Fix - Fixed attendee ticket title for moved TicketsCommerce tickets. [ET-1611]
* Fix - Fixed fatal error on the Tickets Settings page when site language was set to Italian. [ET-1645]
* Language - 16 new strings added, 181 updated, 1 fuzzied, and 94 obsoleted

### [5.5.8] 2023-02-22

* Version - Event Tickets 5.5.8 is only compatible with The Events Calendar 6.0.10 and higher.
* Version - Event Tickets 5.5.8 is only compatible with Event Tickets Plus 5.6.7 and higher.
* Tweak - PHP version compatibility bumped to PHP 7.4
* Tweak - Version Composer updated to 2
* Tweak - Version Node updated to 18.13.0
* Tweak - Version NPM update to 8.19.3
* Tweak - Reduce JavaScript bundle sizes for Blocks editor

### [5.5.7] 2023-02-09

* Tweak - Added currency format options to alter currency decimal separator, thousand separator, and number of decimal places. [ET-1608]
* Tweak - Updated Currency options in Tickets Commerce settings for Croatian users from Kuna (HRK) to Euro (EUR). [ET-1625]
* Tweak - Updated Attendee Registration Fields upsell notice to only display in admin dashboard. [CT-67]
* Fix - Resolve provisional IDs properly on the event edit screen for ticket management actions. [ET-1632]
* Fix - Fixed Ticket Commerce cart cookies not getting saved. [ET-1629]
* Language - 28 new strings added, 189 updated, 5 fuzzied, and 3 obsoleted

### [5.5.6] 2023-01-16

* Tweak - Updated the settings description for stock handling options. [ET-1603]
* Tweak - Added the `tribe-tickets__tickets-item--shared-capacity` wrapper class for tickets having shared capacity. [ETP-841]
* Tweak - Added a dashboard notice for sites running PHP versions lower than 7.4 to alert them that the minimum version of PHP is changing to 7.4 in February 2023.
* Tweak - Added search capabilities to the Tickets Commerce Orders report page. [ET-1259]
* Fix - Allow loading attendance page with `event_id` params that use The Events Calendar provisional IDs. [ET-1624]
* Language - 4 new strings added, 43 updated, 0 fuzzied, and 2 obsoleted

### [5.5.5] 2022-12-08

* Tweak - Removed locale param for Tickets Commerce JS SDK as per PayPal recommendation. [ET-1615]
* Fix - Remove need for Platform Controls to verify webhook signatures in Stripe. [ET-1508]
* Fix - Fixed the order of tickets in an event changing when you haven't manually requested it. [ET-1568]
* Fix - Fixed shared capacity tickets only showing the lowest capacity between the shared pool tickets. [ETP-815]
* Language - 110 new strings added, 193 updated, 5 fuzzied, and 24 obsoleted

### [5.5.4] 2022-11-09

* Fix - Fixes multiple of the same ticket form being on the same page being out of sync. [GTRIA-729]
* Fix - Added a JS event that checks for attendee label validation if ET+ is active. [ETP-803]

### [5.5.3] 2022-10-31

* Tweak - Added support for `name` and `email` param for searching in Attendee archive REST API. [ET-1591]
* Tweak - Add template tag to properly check if The Events Calendar is active. [ETP-820]
* Tweak - Add `attendance` information to the `events` REST API endpoint. [ET-1580]
* Tweak - Add `check_in` argument support for `attendees` REST API endpoint. [ET-1588]
* Fix - Orderby param not working for Attendee archive REST API. [ET-1591]
* Fix - Properly save the check-in details for attendees on check-in. [ETP-819]
* Fix - TicketsCommerce ticketed events not showing up for Events REST API. [ET-1567]
* Fix - Update version of Firebase/JWT in Common from 5.x to 6.3.0
* Language - 0 new strings added, 18 updated, 0 fuzzied, and 0 obsoleted

### [5.5.2] 2022-10-20

* Fix - Update version of Firebase/JWT in Common from 5.x to 6.3.0

### [5.5.1] 2022-09-22

* Fix - Listing tickets is no longer limited by the global settings. [ET-1584]
* Fix - Correct parameter type hinting when param can be null. [ET-1582]
* Fix - Showing Checkout not available and credit card fields at the same time for PayPal gateway in TicketsCommerce. [ETP-812]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted

### [5.5.0] 2022-09-06

* Version - Event Tickets 5.5.0 is only compatible with The Events Calendar 6.0.0 and higher.
* Version - Event Tickets 5.5.0 is only compatible with Event Tickets Plus 5.6.0 and higher.
* Tweak - Adds a compatibility layer to work with the new Recurrence Backend Engine in TEC/ECP.
* Language - 4 new strings added, 49 updated, 0 fuzzied, and 3 obsoleted

### [5.4.4] 2022-08-15

* Fix - Tickets/RSVP blocks appear in wrong place on non-events when block editor is disabled in The Events Calendar. [ET-1544]
* Fix - Fixed searching attendees by purchaser name and email for Tickets Commerce attendees. [ET-1543]
* Fix - Fixed concurrent RSVP booking not showing error messages for out of stock tickets. [ET-1506]
* Fix - Fixed showing proper ticket unavailable message for past events. [ET-1146]
* Fix - Fixed Shared Capacity calculation with capped tickets. [ETP-801]
* Fix - Fixed logic that was causing fatal errors when using Elementor. [ET-1561]
* Enhancement - Sorting support added on Tickets Commerce Order Report page columns. [ET-1527]
* Enhancement - Sorting support added on Attendee Report page for Ticket, Security Code, and Check In columns. [ET-1526]
* Enhancement - Added section about Tickets Commerce on the Tickets Home page. [ET-1539]
* Enhancement - Tickets Commerce Stripe gateway now shows additional purchaser info metadata in Stripe dashboard. [ET-1542]
* Enhancement - Update REST API Endpoints to have access via API KEY and remove the restriction to be using Event Tickets Plus to access the `attendees` endpoint. [ET-1559]
* Enhancement - Allow filtering of Events Archive REST API using `ticketed` parameter to filter Ticketed and Non-ticketed events. [TEC-4439]
* Language - 6 new strings added, 162 updated, 0 fuzzied, and 9 obsoleted

### [5.4.3.1] 2022-07-21

* Fix - Update Freemius to avoid PHP 8 fatals. [TEC-4330]

### [5.4.3] 2022-07-20

* Tweak - update TCMN to match TEC 5.16.3 [TEC-4433]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted.

### [5.4.2.1] 2022-07-12

* Fix - Fixes compatibility with Elementor-based themes. [ET-1554]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted.

### [5.4.2] 2022-07-05

* Enhancement - Unify CSS class names for many admin elements. [ET-1536]
* Enhancement - Add a `Currency Position` setting for Tickets Commerce. [ET-1534]
* Tweak - Added helpful heading labels for Tickets Commerce Payments settings tab fields. [ET-1529]
* Fix - Fixed changing of Shared Capacity stock when a single ticket stock is updated. [ETP-800]
* Fix - Cannot edit events with Elementor on The Events Calendar with Event Tickets activated. [ET-1538]
* Fix - Remove duplicate `Total Event Capacity` wording when ET+ is activated. [ET-1535]
* Fix - When using Event Tickets as a standalone plugin, the SelectWoo asset was not being properly loaded. [ET-1531]
* Fix - Some CSS issues within the tickets block in the block editor. [ET-1530]
* Fix - The wrong `Ticket #` was being sent in attendee emails for Ticket Commerce tickets. [ET-1537]
* Fix - Allow price block to override the string that is dynamically created from ticket values. [ET-1524]
* Language - 9 new strings added, 177 updated, 0 fuzzied, and 1 obsoleted.

### [5.4.1] 2022-06-08

* Enhancement - Expanded list of supported currencies for Tickets Commerce, for details visit: https://evnt.is/tec-tc-currencies. [ET-1454, ET-1455, ET-1456]
* Fix - In the blocks editor, the ticket sale start/end times always load as midnight. [ET-1518]
* Fix - Encoding issue in the block editor's price block of The Events Calendar events. [ET-1434]
* Fix - Add India to the list of countries ET cannot process fees from. [ET-1522]
* Fix - Avoid loading PayPal partner JS script on all admin pages. [ET-1520]
* Fix - Disable saving Stripe Webhook Signing Secret before validation. [ET-1497]
* Enhancement - Add a new notice to set up permalinks to something different than "Plain" in order to use Tickets Commerce. [ET-1521]
* Enhancement - Add links to "Settings" and "Getting started" on the plugin action links. [ET-1525]
* Language - 2 new strings added, 20 updated, 0 fuzzied, and 3 obsoleted

### [5.4.0.2] 2022-06-06

* Fix - Adds a safety check to avoid issuing tickets for late-declined purchases in PayPal, when the Order status returned is valid. [ET-1533]
* Tweak - Added filters: `tec_tickets_commerce_cart_order_hash`
* Language - 1 new strings added, 15 updated, 0 fuzzied, and 0 obsoleted.

### [5.4.0.1] 2022-05-23

* Fix - Check if function exists for `get_current_screen` to avoid a fatal if not.

### [5.4.0] 2022-05-19

* Version - Event Tickets 5.4.0 is only compatible with The Events Calendar 5.15.0 and higher
* Version - Event Tickets 5.4.0 is only compatible with Event Tickets Plus 5.5.0 and higher
* Feature - Introducing the new Tickets menu on the WordPress admin. [ET-1335]
* Language - 6 new strings added, 171 updated, 1 fuzzied, and 2 obsoleted

### [5.3.4.1] 2022-05-12

* Version - Event Tickets 5.3.4.1 is only compatible with Event Tickets Plus 5.4.4.1 and higher
* Fix - Ensure that Event Tickets Plus customers never encounter application fees on Stripe for Tickets Commerce purchases. [ET-1513]

### [5.3.4] 2022-05-11

* Enhancement - Added availability dates and icons to ticket listing in classic editor. [ET-1494]
* Enhancement - Notify users of the Manual Addition of Attendees feature that is available. [ET-1492]
* Enhancement - Notify users of Capacity and Attendee Registration Field features that are available. [ET-1493]
* Fix - Typo was causing a JS `setAttribute` error in `vue.min.js`. [ET-1504]
* Fix - Fatal error when exporting attendees in PHP 8. [ET-1502]
* Fix - Tickets Commerce manual attendee's ticket price is set to 0. [ETP-781]
* Fix - RSVP title is being encoded within the block editor fields. [ET-1478]
* Fix - Tickets Commerce manual attendee's ticket price is set to 0. [ETP-781]
* Fix - Fixed template override path for a few templates. [ET-1491]
* Tweak - Lighten color of disabled "Get Tickets" button text when using the Genesis theme. [ET-1435]
* Tweak - Added actions: `tec_tickets_attendees_event_summary_table_extra`
* Tweak - Changed views: `blocks/tickets/opt-out-hidden`, `blocks/tickets/registration/summary/content`, `registration-js/attendees/fields/number`, `v2/tickets/commerce/fields/tribe-commerce`, `v2/tickets/item/extra/description-toggle`, `v2/tickets/submit/must-login`.
* Language - 2 new strings added, 46 updated, 0 fuzzied, and 0 obsoleted.

### [5.3.3] 2022-04-28

* Fix - Updates the plugin validation library to track licenses in a more fault-tolerant way. [ET-1498]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted.

### [5.3.2] 2022-04-05

* Feature - REST API endpoints added for creating and updating attendees. [ET-1346]
* Enhancement - Added a notice when an enabled Tickets Commerce gateway doesn't support it's selected currency. [ET-1392]
* Enhancement - Adding the South African Rand to list of supported currencies in Tickets Commerce. [ET-1438]
* Enhancement - Hide 'View My Tickets' link when showing tickets within the `[tribe_tickets]` shortcode. [ETP-775]
* Fix - Fixed Events Tickets App check-in for Tickets Commerce tickets. [ET-1436]
* Fix - Improved validation of Stripe webhook events to avoid handling events created by other apps. [ET-1474]
* Fix - Fixed Issue with Tickets Commerce Tickets not displaying in REST API. [ET-1458]
* Fix - Fixed Issue with Tickets Commerce Attendees not displaying in shortcodes. [ET-1461]
* Fix - Fixed Issue with Tickets Commerce Attendees not being synced with Promoter. [ET-1476]
* Fix - Fixed JS assets loading and causing errors on checkout page for Tickets Commerce. [ET-1426]
* Fix - Fixed WooCommerce currency settings not getting reflected on Event Cost Field . [ETP-783]
* Fix - Correct a misapplied Customizer color that breaks the loading "dot" animation. [ET-1437]
* Fix - Add Mexico to the list of countries ET cannot process fees from. [ET-1479]
* Tweak - Updated links in readme.txt file. [ET-1459]
* Tweak - Added filters: `tec_tickets_commerce_admin_notices`, `tec_tickets_commerce_gateway_supported_currencies_`, `tec_tickets_commerce_currency_code_options`, `tribe_ticket_rest_api_post_attendee_args`, `tribe_ticket_rest_api_edit_attendee_args`, `tribe_tickets_rest_api_post_attendee_data`, `tribe_tickets_rest_api_update_attendee_data`, `tec_tickets_completed_status_by_provider_name`, `tec_tickets_hide_view_link`
* Tweak - Added actions: `tribe_tickets_promoter_trigger_attendee`, `tec-tickets-commerce-checkout-shortcode-assets`, `tec-tickets-commerce-checkout-shortcode-assets`
* Tweak - Changed views: `blocks/attendees/view-link`, `tickets/view-link`
* Language - 17 new strings added, 70 updated, 0 fuzzied, and 0 obsoleted

### [5.3.1] 2022-03-15

* Fix - Fixed a warning message when creating an event via Community Events. [CT-51]
* Fix - Fixed errors for Tickets Commerce with Stripe during checkout. [ET-1447]
* Fix - Fixed the default views (v2) for users that are using Event Tickets as standalone, after version `5.3.0`. [ET-1448]
* Fix - Avoid sending duplicate ticket emails for using Tickets Commerce Stripe Webhooks. [ET-1446]
* Fix - Respect the selected currency when using Tickets Commerce in the blocks editor. [ET-1450]
* Enhancement - Updated theme compatibility class to make use of common compatibility classes. Deprecate the `filter_body_class` and `get_body_classes` methods from `Tribe__Tickets__Theme_Compatibility`. [ET-850]
* Enhancement - Tweaked `get_tickets` method to improve stability and performance around ticket. [ET-1362]
* Tweak - Removed filters: `tribe_tickets_theme_compatibility_registered`
* Language - 0 new strings added, 33 updated, 0 fuzzied, and 0 obsoleted

### [5.3.0.1] 2022-03-01

* Tweak - Update version of Freemius to 2.4.3.

### [5.3.0] 2022-02-24

* Version - Event Tickets 5.3.0 is only compatible with Event Tickets Plus 5.4.0 and higher
* Feature - Introduction of Stripe for Tickets Commerce. [ET-1342]
* Feature - Collect purchaser name and email for anonymous purchases using Tickets Commerce. [ET-1378]
* Feature - Introduce automatic handling of zero-decimal currencies in Tickets Commerce [ET-1414][ET-1420]
* Fix - Remove anonymous purchase notice for Tickets Commerce after ET-1378 was implemented. [ET-1379]
* Tweak - Added filters: `tec_tickets_commerce_order_purchaser_data`, `tec_tickets_commerce_order_purchaser_data`, `tec_tickets_commerce_stripe_checkout_localized_data`, `tec_tickets_commerce_stripe_order_endpoint_error_messages`, `tec_tickets_commerce_stripe_settings`, `tec_tickets_commerce_stripe_settings`, `tec_tickets_commerce_stripe_payment_methods_by_currency`, `tec_tickets_commerce_stripe_payment_methods_available`, `tec_tickets_commerce_stripe_fee_is_applied_notice`, `tec_tickets_commerce_gateway_stripe_webhook_event_handlers`, `tec_tickets_commerce_gateway_stripe_webhook_status`, `tec_tickets_commerce_gateway_stripe_webhook_events_labels_map`, `tec_tickets_commerce_payments_tab_sections`, `tec_tickets_commerce_currency_{$code}_name`, `tec_tickets_commerce_currency_name`, `tec_tickets_commerce_currency_{$code}_precision`, `tec_tickets_commerce_currency_precision`
* Tweak - Removed filters: `tec_tickets_commerce_gateway_paypal_tracking_id`
* Tweak - Changed views: `tickets/email`, `v2/commerce/checkout`, `v2/commerce/checkout/cart`, `v2/commerce/checkout/footer/gateway-error`, `v2/commerce/checkout/gateways`, `v2/commerce/checkout/purchaser-info`, `v2/commerce/checkout/purchaser-info/email`, `v2/commerce/checkout/purchaser-info/name`, `v2/commerce/gateway/paypal/container`, `v2/commerce/gateway/stripe/card-element`, `v2/commerce/gateway/stripe/container`, `v2/commerce/gateway/stripe/payment-element`
* Language - 102 new strings added, 164 updated, 0 fuzzied, and 3 obsoleted

### [5.2.4.1] 2022-02-17

* Fix - Classic Editor compatibility problems with the Ticket Form resolved [GTRIA-738]

### [5.2.4] 2022-02-15

* Tweak - Compatibility with the Common Abstract for editor blocks registration.
* Tweak - Remove the `wp.editor.InnerBlocks` gutenberg component in favor of `wp.blockEditor.InnerBlocks` which was deprecated since version 5.3. [ET-1367]
* Tweak - Prevent scripts from loading on all Admin pages, only load on pages needed.
* Tweak - Performance improvements around Block Asset loading and redundancy.
* Tweak - Internal caching of values to reduce `get_option()` call count.
* Tweak - Switch from `sanitize_title_with_dashes` to `sanitize_key` in a couple instances for performance gains.
* Tweak - Prevent asset loading from repeating calls to plugin URL and path, resulting in some minor performance gains.
* Fix - Update the way we handle Classic Editor compatibility. Specifically around user choice. [TEC-4016]
* Fix - Remove incorrect reference for moment.min.js.map [TEC-4148]
* Fix - Fixed troubleshooting page styles for standalone Event Tickets setup [ET-1382]
* Fix - Remove singleton created from a deprecated class.
* Language - 0 new strings added, 12 updated, 0 fuzzied, and 0 obsoleted

### [5.2.3] 2022-01-19

* Feature - Allow duplicating a ticket when using the Classic Editor. [ET-1349]
* Feature - Added the TEC Tickets icon in the block editor Tickets category section. [ET-1350]
* Enhancement - Added a warning when Tickets Commerce is enabled, but users aren't required to log in before purchasing tickets. [ET-1352
* Fix - Added the post_type to the attendee page on Posts/Pages so that additional logic would function correctly. [ET-1319]
* Fix - Fixed toggling of shared capacity data for tickets. [ETP-497]
* Language - 7 new strings added, 99 updated, 0 fuzzied, and 0 obsoleted

### [5.2.2] 2021-12-15

* Feature - Included Price, Currency and Value classes to improve monetary handling for Tickets Commerce [ET-1331]
* Enhancement - Allow for filtering of tickets within the RSVP template block handler. [ETP-763]
* Fix - Remove use of `wp_cache_flush()` and use conditional when using an external object cache. (props to @r-a-y for this change!) [ET-1343]
* Fix - Fixes error being caused by an endless loop when currency settings are saved. [ET-1344]
* Fix - Fixed an issue where shared capacity on the ticket block page wasn't calculated correctly. [ET-1291]
* Fix - Fixed the `Add Attendee` modal from generating a 500 error when two or more tickets have been enabled for an event. [ETP-764]
* Language - 0 new strings added, 50 updated, 0 fuzzied, and 0 obsoleted

### [5.2.1] 2021-11-17

* Enhancement - Auto generate checkout page when enabling Tickets Commerce. [ET-1232]
* Enhancement - Auto generate order success page when enabling Tickets Commerce. [ET-1233]
* Enhancement - Added filter `tribe_tickets_manual_attendee_allow_email_resend` to allow customization of email resending via Manual Attendees depending on status. [ETP-703]
* Enhancement - Add `getPrice` method to utilities JS object to centralize the way we get ticket prices. [ET-1238]
* Enhancement - Add a modal with more information about the PayPal connection after connecting with PayPal via Tickets Commerce. [ET-1321]
* Fix - Fixes error being caused when trying to load attendee information. [ET-1320]
* Fix - Added `allow_resending_email` method which can be used to enable or disable resending email. [ETP-703]
* Fix - Fixed ticket total formatting within the attendee registration modal when using custom thousands and decimal separators. [ET-1216]
* Fix - QR Code API generation settings not working if `The Events Calendar` plugin was not active. [ETP-754]
* Fix - Fixed the event cost formatting issues showing the wrong currency symbol, symbol location and separators. [ET-1251]
* Fix - Disable "Connect to PayPal" button while a new URL is not available, after changing countries. [ET-1318]
* Fix - Searching Ticket Holder Email / Ticket Holder Name through the Attendee page now functions as expected. [ET-1171]\
* Fix - Working with PayPal accounts in currencies other than USD now works as expected. [ET-1330]
* Language - 17 new strings added, 41 updated, 0 fuzzied, and 0 obsoleted

### [5.2.0.1] 2021-11-10

* Fix - Ensures that Tickets Commerce attendees get archived properly when an order is canceled or not completed. [ET-1322]

### [5.2.0] 2021-11-04

* Feature - Introduction of Tickets Commerce, the new and improved solution you can set up to sell tickets with Event Tickets.
* Language - 840 new strings added, 432 updated, 26 fuzzied, and 16 obsoleted

### [5.1.10] 2021-09-27

* Enhancement - When editing an RSVP or ticket in the block editor, allow title to wrap to multiple lines. [ET-1089]
* Enhancement - Ensure that text for the RSVP going/not going dropdown on front end isn't cut off and arrows aren't hidden. [ET-1169]
* Tweak - Added a new filter `tribe_tickets_get_provider_query_slug` to allow customization of the provider URL variable name. [ET-543]
* Tweak - Changed the `provider` URL variable name to `tickets_provider`. The filter `tribe_tickets_get_provider_query_slug` allows for customization. [ET-543]
* Fix - Fixed ticket total formatting when using custom thousands and decimal separators. [ET-1197]
* Fix - Show warning while creating new tickets with `0` price for TribeCommerce. [ET-1201]
* Fix - Prevent text overlapping description in the ticket AR modal. [ET-1179]
* Fix - Removed the ability to resend tickets from the Attendees page to Attendees who cancelled or refunded their ticket. [ETP-703]
* Language - 26 new strings added, 116 updated, 2 fuzzied, and 35 obsoleted

### [5.1.9.1] 2021-09-08

* Fix - Fixed conflict with WooCommerce Payments plugin showing error on Ticket Form. [ET-1174]

### [5.1.9] 2021-08-31

* Fix - Fixed cart calculation inconsistency with WooCommerce when the "Number of decimals" setting was set to `0`. [ETP-324]
* Fix - Removed RSVP V2 preview templates and functionality. [ET-1162]
* Fix - Updated deprecated hook `block_categories` to use `block_categories_all`. [ET-1156]
* Language - 37 new strings added, 162 updated, 6 fuzzied, and 20 obsoleted

### [5.1.8] 2021-08-24

* Tweak - Add new event repository schema for finding all events with RSVPs or Tickets.
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted

### [5.1.7] 2021-08-03

* Feature - Added export button next to the page title on the Attendees page. [ET-1145]
* Tweak - Changed the word `Purchaser` to `Attendee` when email confirmation is sent for purchasing a ticket. [ETP-655]
* Tweak - Added `$attendees` parameter to the `tribe_report_page_after_text_label` action. [ET-1145]
* Tweak - Removed the edit column when printing the Attendees list. [ETP-702]
* Tweak - Added "Delete" functionality for the tickets area in the classic editor. [ET-1107]
* Language - 1 new strings added, 61 updated, 0 fuzzied, and 0 obsoleted

### [5.1.6] 2021-07-07

* Tweak - Added support for HTML in Ticket description field. [ET-1135]
* Tweak - Added `$ticket_id` parameter to the `tribe_events_tickets_metabox_edit_ajax_advanced` filter. [ETP-111]
* Tweak - Update the plugin screenshots on the WordPress.org page. [ET-1143]
* Fix - Prevent Attendees with HTML entities from exporting broken. [ETP-730]
* Fix - Fixed the ticket block allowing to add more tickets than available when using shared capacity. [ET-1137]
* Fix - Sync WooCommerce decimal separator with in Ticket edit form. [ETP-725]
* Fix - Prevent Tribe Commerce "Confirmation email sender name" from displaying improperly when a single quote is added. [ET-1134]
* Language - 115 new strings added, 118 updated, 0 fuzzied, and 0 obsoleted

### [5.1.5] 2021-06-09

* Fix - Fixed shared capacity stock sync after attendee deletion, for TribeCommerce tickets. [ETP-285]
* Fix - Fix the price number calculation for tickets that are using no decimals and thousand separator. [ET-1114]
* Fix - Revert to not hiding past sale tickets from Cost range in Events [ET-1133]
* Fix - Resolved issue where events with tickets were being shown as Free on the day of the event. [ET-1133]
* Tweak - When using The Events Calendar and Event Tickets split the admin footer rating link 50/50. [ET-1120]
* Tweak - Move complete list of changelog entries from `readme.txt` to `changelog.txt`. [ET-1121]
* Language - 0 new strings added, 24 updated, 0 fuzzied, and 0 obsoleted

### [5.1.4] 2021-05-12

* Fix - Show total Attendance count for Attendee List Block view. [ET-791]
* Fix - Add label to the quantity input in the RSVP & Tickets forms to improve accessibility. [ET-767]
* Fix - Fix a JavaScript localization error that was breaking the manual attendees functionality. [ETP-719]
* Tweak - Update the footer calculations on the tickets block to only visible items so it can be used from the Attendee Registration Modal cart. [ETP-715]
* Tweak - Adjust dimensions of tickets table for the classic editor UI. [ETP-594]
* Tweak - Adjust the width of the Check-In column in the attendees report to make it work properly in different languages. [ET-768]
* Tweak - Added filters: `tribe_tickets_admin_manager_request`, `event_tickets_should_enqueue_admin_settings_assets`, `tribe_tickets_assets_should_enqueue_tickets_loader`, `tribe_tickets_attendee_repository_update_attendee_data_args_before_update`, `tribe_tickets_attendee_repository_set_attendee_args`, `tribe_tickets_attendee_repository_set_attendee_args_`, `tribe_tickets_attendee_repository_save_extra_attendee_data_args`, `tribe_tickets_attendee_repository_save_extra_attendee_data_args_`, `tribe_tickets_attendee_repository_create_order_for_attendee_order_args`, `tribe_tickets_attendees_csv_export_delimiter`, `tribe_tickets_repositories_order_statuses`, `tribe_tickets_repositories_order_public_statuses`, `tribe_tickets_repositories_order_private_statuses`, `tribe_tickets_repositories_order_create_order_for_ticket_order_args`, `tribe_tickets_ticket_object_is_ticket_cache_enabled`, `tribe_tickets_attendee_activity_log_data`, `event_tickets_exclude_past_tickets_from_cost_range`, `tribe_tickets_attendee_lookup_user_from_email`, `tribe_tickets_attendee_create_user_from_email`, `tribe_tickets_attendee_create_user_from_email_send_new_user_info`, `tribe_tickets_handler_email_max_resend_limit`, `tribe_tickets_repositories_order_map`, `tribe_tickets_block_ticket_html_attributes`
* Tweak - Removed filters: `tribe_tickets_rsvp_create_attendee_lookup_user_from_email`
* Language - 1 new strings added, 27 updated, 1 fuzzied, and 0 obsoleted

### [5.1.3] 2021-04-22

* Fix - Add TwentyTwentyOne theme compatibility for Tickets and RSVPs. [ET-1047]
* Fix - Added translation support for "Going" and "Not going" status labels. [ET-1056]
* Fix - Disabled check-in for RSVP with "Not Going" status. [ET-984]
* Fix - Fixed an issue with Tickets and RSVP blocks where long descriptions were breaking the block. They now use an auto-resizing textarea. [ET-1078]
* Tweak - Introduce a new "Attendees" link to the WP Admin bar which can take you directly to the Attendees Report page. [ET-1079]
* Tweak - Added the new `tribe_tickets_attendees_csv_export_delimiter` filter to allow changing the delimiter used when generating a CSV export of attendees. [ET-1055]
* Tweak - Adjusted some template override folder paths documented in some of our Tickets-related templates. [ET-1051]
* Language - 2 new strings added, 70 updated, 0 fuzzied, and 1 obsoleted

### [5.1.2.1] 2021-03-30

* Fix - Prevent PHP errors with trailing commas outside of arrays in function calls. [ET-1084]

### [5.1.2] 2021-03-30

* Fix - Don't show View Orders button on the Classic Editor ticket meta box for RSVP only events. [ET-985]
* Fix - Add "Currently full" message on TEC views when the event has RSVPs without availability. [ET-1004]
* Fix - Fixed showing notices for localized script in the attendee report page. [ET-1043]
* Tweak - Move the sales duration of Tickets and RSVP blocks outside of the "Advanced Options" section, making them more accessible. [ET-951]
* Tweak - Aesthetic improvements for the Tickets and RSVP blocks. Adding a white background so they look consistent when there's a different background on the editor styles. [ET-982]
* Tweak - Add confirmation dialog for bulk deletion of attendees in the attendee report page. [ET-981]
* Tweak - Added Getting started banner with links to knowledgebase articles in Ticket Settings. [ET-959]
* Tweak - Add top border to the ticket save section in order to make it more clear that's not part of the AR fields. [ETP-684]
* Tweak - Added filters: `tribe_tickets_admin_manager_request`, `event_tickets_should_enqueue_admin_settings_assets`, `tribe_tickets_assets_should_enqueue_tickets_loader`, `tribe_tickets_attendee_repository_update_attendee_data_args_before_update`, `tribe_tickets_attendee_repository_set_attendee_args`, `tribe_tickets_attendee_repository_set_attendee_args_`, `tribe_tickets_attendee_repository_save_extra_attendee_data_args`, `tribe_tickets_attendee_repository_save_extra_attendee_data_args_`, `tribe_tickets_attendee_repository_create_order_for_attendee_order_args`, `tribe_tickets_repositories_order_statuses`, `tribe_tickets_repositories_order_public_statuses`, `tribe_tickets_repositories_order_private_statuses`, `tribe_tickets_repositories_order_create_order_for_ticket_order_args`, `tribe_tickets_ticket_object_is_ticket_cache_enabled`, `tribe_tickets_attendee_activity_log_data`, `tribe_tickets_attendee_lookup_user_from_email`, `tribe_tickets_attendee_create_user_from_email`, `tribe_tickets_attendee_create_user_from_email_send_new_user_info`, `tribe_tickets_handler_email_max_resend_limit`, `tribe_tickets_repositories_order_map`, `tribe_tickets_block_ticket_html_attributes`
* Tweak - Removed filters: `tribe_tickets_rsvp_create_attendee_lookup_user_from_email`
* Tweak - Added actions: `tribe_log`, `tribe_tickets_attendee_repository_create_attendee_for_ticket_after_create`, `tribe_tickets_attendee_repository_create_attendee_for_ticket_after_create_`, `tribe_tickets_attendee_repository_update_attendee_after_update`, `tribe_tickets_attendee_repository_update_attendee_after_update_{$this->key_name}`, `tribe_log`, `tribe_log`, `tribe_report_page_after_text_label`
* Tweak - Changed views: `blocks/attendees`, `blocks/attendees/description`, `blocks/attendees/gravatar`, `blocks/attendees/title`, `blocks/attendees/view-link`, `blocks/rsvp`, `blocks/rsvp/content-inactive`, `blocks/rsvp/content`, `blocks/rsvp/details`, `blocks/rsvp/details/availability`, `blocks/rsvp/details/description`, `blocks/rsvp/details/title`, `blocks/rsvp/form`, `blocks/rsvp/form/attendee-meta`, `blocks/rsvp/form/details`, `blocks/rsvp/form/email`, `blocks/rsvp/form/error`, `blocks/rsvp/form/form`, `blocks/rsvp/form/name`, `blocks/rsvp/form/opt-out`, `blocks/rsvp/form/quantity-input`, `blocks/rsvp/form/quantity-minus`, `blocks/rsvp/form/quantity-plus`, `blocks/rsvp/form/quantity`, `blocks/rsvp/form/submit-button`, `blocks/rsvp/form/submit-login`, `blocks/rsvp/icon-svg`, `blocks/rsvp/icon`, `blocks/rsvp/loader-svg`, `blocks/rsvp/loader`, `blocks/rsvp/messages/success`, `blocks/rsvp/status`, `blocks/rsvp/status/full`, `blocks/rsvp/status/going-icon`, `blocks/rsvp/status/going`, `blocks/rsvp/status/not-going-icon`, `blocks/rsvp/status/not-going`, `blocks/tickets`, `blocks/tickets/commerce/fields-edd`, `blocks/tickets/commerce/fields-tpp`, `blocks/tickets/commerce/fields-woo`, `blocks/tickets/commerce/fields`, `blocks/tickets/content-description`, `blocks/tickets/content-inactive`, `blocks/tickets/content-title`, `blocks/tickets/content`, `blocks/tickets/extra-available-quantity`, `blocks/tickets/extra-available-unlimited`, `blocks/tickets/extra-available`, `blocks/tickets/extra-price`, `blocks/tickets/extra`, `blocks/tickets/footer-quantity`, `blocks/tickets/footer-total`, `blocks/tickets/footer`, `blocks/tickets/icon-svg`, `blocks/tickets/icon`, `blocks/tickets/item-inactive`, `blocks/tickets/item`, `blocks/tickets/opt-out-hidden`, `blocks/tickets/quantity-add`, `blocks/tickets/quantity-number`, `blocks/tickets/quantity-remove`, `blocks/tickets/quantity-unavailable`, `blocks/tickets/quantity`, `blocks/tickets/registration/attendee/content`, `blocks/tickets/registration/attendee/fields`, `blocks/tickets/registration/attendee/fields/checkbox`, `blocks/tickets/registration/attendee/fields/radio`, `blocks/tickets/registration/attendee/fields/select`, `blocks/tickets/registration/attendee/fields/text`, `blocks/tickets/registration/attendee/submit`, `blocks/tickets/registration/content`, `blocks/tickets/registration/summary/content`, `blocks/tickets/registration/summary/ticket-icon`, `blocks/tickets/registration/summary/ticket-price`, `blocks/tickets/registration/summary/ticket-quantity`, `blocks/tickets/registration/summary/ticket-title`, `blocks/tickets/registration/summary/ticket`, `blocks/tickets/registration/summary/tickets`, `blocks/tickets/registration/summary/title`, `blocks/tickets/submit-button-modal`, `blocks/tickets/submit-button`, `blocks/tickets/submit-login`, `blocks/tickets/submit`, `components/loader`, `components/notice`, `modal/item-total`, `modal/registration-js`, `registration-js/attendees/content`, `registration-js/content`, `registration-js/mini-cart`, `registration/attendees/content`, `registration/content`, `tickets/email`, `tickets/orders`, `v2/components/icons/error`, `v2/components/icons/guest`, `v2/components/icons/paper-plane`, `v2/components/loader/loader`, `v2/day/event/cost`, `v2/list/event/cost`, `v2/map/event-cards/event-card/actions/cost`, `v2/month/calendar-body/day/calendar-events/calendar-event/tooltip/cost`, `v2/month/mobile-events/mobile-day/mobile-event/cost`, `v2/photo/event/cost`, `v2/rsvp-kitchen-sink`, `v2/rsvp-kitchen-sink/ari`, `v2/rsvp-kitchen-sink/default-full`, `v2/rsvp-kitchen-sink/default-must-login`, `v2/rsvp-kitchen-sink/default-no-description`, `v2/rsvp-kitchen-sink/default-unlimited`, `v2/rsvp-kitchen-sink/default`, `v2/rsvp-kitchen-sink/form-going`, `v2/rsvp-kitchen-sink/form-not-going`, `v2/rsvp-kitchen-sink/success`, `v2/rsvp`, `v2/rsvp/actions`, `v2/rsvp/actions/full`, `v2/rsvp/actions/rsvp`, `v2/rsvp/actions/rsvp/going`, `v2/rsvp/actions/rsvp/not-going`, `v2/rsvp/actions/success`, `v2/rsvp/actions/success/title`, `v2/rsvp/actions/success/toggle`, `v2/rsvp/actions/success/tooltip`, `v2/rsvp/ari`, `v2/rsvp/ari/form`, `v2/rsvp/ari/form/error`, `v2/rsvp/ari/form/fields`, `v2/rsvp/ari/form/fields/email`, `v2/rsvp/ari/form/fields/meta`, `v2/rsvp/ari/form/fields/name`, `v2/rsvp/ari/form/template/fields`, `v2/rsvp/ari/sidebar`, `v2/rsvp/ari/sidebar/quantity/input`, `v2/rsvp/ari/sidebar/quantity/minus`, `v2/rsvp/ari/sidebar/quantity/plus`, `v2/rsvp/content`, `v2/rsvp/details`, `v2/rsvp/details/attendance`, `v2/rsvp/details/availability`, `v2/rsvp/details/availability/days-to-rsvp`, `v2/rsvp/details/availability/full`, `v2/rsvp/details/availability/remaining`, `v2/rsvp/details/availability/unlimited`, `v2/rsvp/details/description`, `v2/rsvp/details/title`, `v2/rsvp/form/buttons`, `v2/rsvp/form/fields`, `v2/rsvp/form/fields/cancel`, `v2/rsvp/form/fields/email`, `v2/rsvp/form/fields/name`, `v2/rsvp/form/fields/submit`, `v2/rsvp/form/form`, `v2/rsvp/form/going/title`, `v2/rsvp/form/not-going/title`, `v2/rsvp/form/title`, `v2/rsvp/messages/error`, `v2/rsvp/messages/must-login`, `v2/rsvp/messages/success`, `v2/rsvp/messages/success/going`, `v2/rsvp/messages/success/not-going`, `v2/tickets`, `v2/tickets/commerce/fields`, `v2/tickets/commerce/fields/tribe-commerce`, `v2/tickets/footer`, `v2/tickets/footer/quantity`, `v2/tickets/footer/return-to-cart`, `v2/tickets/footer/total`, `v2/tickets/item`, `v2/tickets/item/content`, `v2/tickets/item/content/description-toggle`, `v2/tickets/item/content/description`, `v2/tickets/item/content/inactive`, `v2/tickets/item/content/title`, `v2/tickets/item/extra`, `v2/tickets/item/extra/available`, `v2/tickets/item/extra/available/quantity`, `v2/tickets/item/extra/available/unlimited`, `v2/tickets/item/extra/description-toggle`, `v2/tickets/item/extra/price`, `v2/tickets/item/inactive`, `v2/tickets/item/opt-out`, `v2/tickets/item/quantity-mini`, `v2/tickets/item/quantity`, `v2/tickets/item/quantity/add`, `v2/tickets/item/quantity/number`, `v2/tickets/item/quantity/remove`, `v2/tickets/item/quantity/unavailable`, `v2/tickets/items`, `v2/tickets/notice`, `v2/tickets/opt-out/hidden`, `v2/tickets/submit`, `v2/tickets/submit/button`, `v2/tickets/submit/must-login`, `v2/tickets/title`, `v2/week/grid-body/events-day/event/tooltip/cost`, `v2/week/mobile-events/day/event/cost`
* Language - 19 new strings added, 60 updated, 0 fuzzied, and 0 obsoleted

### [5.1.1] 2021-03-04

* Fix - Compatibility with WordPress 5.7 and jQuery 3.5.X [ET-992]
* Fix - Attendees will no longer have a new user created (if they did not already exist), which was introduced in Event Tickets 5.1.0. To turn this on, you can simply add the filter `add_filter( 'tribe_tickets_attendee_create_user_from_email', '__return_true' );`
* Fix - Prevent the Attendee Registration page from having the title coming from draft pages. [ETP-360]
* Fix - Highlight the "Ticketed" and "Unticketed" filters in the WordPress when they're applied. [ET-1022]
* Fix - Prevent duplicate tickets from showing in post loops. [ETP-639]
* Fix - Ensure ticket object caches return normally in all circumstances, preventing potential "Sold Out" messaging from happening in certain hosting environments. [ET-1023]
* Fix - Set the default `iac` argument value in the single ticket REST API endpoint to add tickets since it is an optional argument to be sent.
* Tweak - Added new `Ticket Holder Name` and `Ticket Holder Email Address` columns to the Attendees Report export CSV file and update the previous `Customer` columns to label as `Purchaser`. [ETP-652]
* Tweak - Tweaked SQL queries for MySQL 8+ compatibility. [ET-1021]
* Language - 2 new strings added, 38 updated, 2 fuzzied, and 0 obsoleted

### [5.1.0] 2021-02-16

* Feature - New Attendees ORM functionality allows creating and updating attendees. [ETP-366]
* Feature - New Orders ORM can be used by calling `tribe_tickets_orders()` and allows interfacing with Event Tickets Plus commerce providers. [ETP-366]
* Fix - Remove the duplicate Attendees heading from the Attendees Report screen when using Tribe Commerce tickets. [ETP-366]
* Tweak - New admin manager code to help us consolidate modals going forward with a comprehensive templating and form processing solution. [ETP-366]
* Tweak - Enforce capitalization for the text for action buttons on the Attendees Report screen. [ETP-624]
* Tweak - A new filter was introduced to help avoid problems where ticket caching has problems with certain hosting environments that cause tickets to show as Sold Out. Disable ticket caching with `add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', '__return_false' );` [ETP-366]
* Tweak - Start tracking attendee email activity in a meta record so that there's a better ability for determining if an attendee email was sent, where to, and how many times it was re-sent. [ETP-366]
* Tweak - Added filters: `tribe_tickets_admin_manager_request`, `tribe_tickets_attendee_repository_set_attendee_args`, `tribe_tickets_attendee_repository_set_attendee_args_`, `tribe_tickets_attendee_repository_save_extra_attendee_data_args`, `tribe_tickets_attendee_repository_save_extra_attendee_data_args_`, `tribe_tickets_attendee_repository_create_order_for_attendee_order_args`, `tribe_tickets_repositories_order_statuses`, `tribe_tickets_repositories_order_public_statuses`, `tribe_tickets_repositories_order_private_statuses`, `tribe_tickets_repositories_order_create_order_for_ticket_order_args`, `tribe_tickets_ticket_object_is_ticket_cache_enabled`, `tribe_tickets_attendee_activity_log_data`, `tribe_tickets_attendee_lookup_user_from_email`, `tribe_tickets_attendee_create_user_from_email`, `tribe_tickets_attendee_create_user_from_email_send_new_user_info`, `tribe_tickets_handler_email_max_resend_limit`, `tribe_tickets_repositories_order_map`
* Tweak - Removed filters: `tribe_tickets_rsvp_create_attendee_lookup_user_from_email`
* Tweak - Added actions: `tribe_log`, `tribe_tickets_attendee_repository_create_attendee_for_ticket_after_create`, `tribe_tickets_attendee_repository_create_attendee_for_ticket_after_create_`, `tribe_tickets_attendee_repository_update_attendee_after_update`, `tribe_tickets_attendee_repository_update_attendee_after_update_{$this->key_name}`, `tribe_log`, `tribe_log`, `tribe_report_page_after_text_label`
* Tweak - Changed views: `blocks/attendees`, `blocks/attendees/description`, `blocks/attendees/gravatar`, `blocks/attendees/title`, `blocks/attendees/view-link`, `blocks/rsvp`, `blocks/rsvp/content-inactive`, `blocks/rsvp/content`, `blocks/rsvp/details`, `blocks/rsvp/details/availability`, `blocks/rsvp/details/description`, `blocks/rsvp/details/title`, `blocks/rsvp/form`, `blocks/rsvp/form/attendee-meta`, `blocks/rsvp/form/details`, `blocks/rsvp/form/email`, `blocks/rsvp/form/error`, `blocks/rsvp/form/form`, `blocks/rsvp/form/name`, `blocks/rsvp/form/opt-out`, `blocks/rsvp/form/quantity-input`, `blocks/rsvp/form/quantity-minus`, `blocks/rsvp/form/quantity-plus`, `blocks/rsvp/form/quantity`, `blocks/rsvp/form/submit-button`, `blocks/rsvp/form/submit-login`, `blocks/rsvp/icon-svg`, `blocks/rsvp/icon`, `blocks/rsvp/loader-svg`, `blocks/rsvp/loader`, `blocks/rsvp/messages/success`, `blocks/rsvp/status`, `blocks/rsvp/status/full`, `blocks/rsvp/status/going-icon`, `blocks/rsvp/status/going`, `blocks/rsvp/status/not-going-icon`, `blocks/rsvp/status/not-going`, `blocks/tickets`, `blocks/tickets/commerce/fields-edd`, `blocks/tickets/commerce/fields-tpp`, `blocks/tickets/commerce/fields-woo`, `blocks/tickets/commerce/fields`, `blocks/tickets/content-description`, `blocks/tickets/content-inactive`, `blocks/tickets/content-title`, `blocks/tickets/content`, `blocks/tickets/extra-available-quantity`, `blocks/tickets/extra-available-unlimited`, `blocks/tickets/extra-available`, `blocks/tickets/extra-price`, `blocks/tickets/extra`, `blocks/tickets/footer-quantity`, `blocks/tickets/footer-total`, `blocks/tickets/footer`, `blocks/tickets/icon-svg`, `blocks/tickets/icon`, `blocks/tickets/item-inactive`, `blocks/tickets/item`, `blocks/tickets/opt-out-hidden`, `blocks/tickets/quantity-add`, `blocks/tickets/quantity-number`, `blocks/tickets/quantity-remove`, `blocks/tickets/quantity-unavailable`, `blocks/tickets/quantity`, `blocks/tickets/registration/attendee/content`, `blocks/tickets/registration/attendee/fields`, `blocks/tickets/registration/attendee/fields/checkbox`, `blocks/tickets/registration/attendee/fields/radio`, `blocks/tickets/registration/attendee/fields/select`, `blocks/tickets/registration/attendee/fields/text`, `blocks/tickets/registration/attendee/submit`, `blocks/tickets/registration/content`, `blocks/tickets/registration/summary/content`, `blocks/tickets/registration/summary/ticket-icon`, `blocks/tickets/registration/summary/ticket-price`, `blocks/tickets/registration/summary/ticket-quantity`, `blocks/tickets/registration/summary/ticket-title`, `blocks/tickets/registration/summary/ticket`, `blocks/tickets/registration/summary/tickets`, `blocks/tickets/registration/summary/title`, `blocks/tickets/submit-button-modal`, `blocks/tickets/submit-button`, `blocks/tickets/submit-login`, `blocks/tickets/submit`, `components/loader`, `components/notice`, `modal/item-total`, `modal/registration-js`, `registration-js/attendees/content`, `registration-js/content`, `registration-js/mini-cart`, `registration/attendees/content`, `registration/content`, `tickets/email`, `tickets/orders`, `v2/components/icons/error`, `v2/components/icons/guest`, `v2/components/icons/paper-plane`, `v2/components/loader/loader`, `v2/day/event/cost`, `v2/list/event/cost`, `v2/map/event-cards/event-card/actions/cost`, `v2/month/calendar-body/day/calendar-events/calendar-event/tooltip/cost`, `v2/month/mobile-events/mobile-day/mobile-event/cost`, `v2/photo/event/cost`, `v2/rsvp-kitchen-sink`, `v2/rsvp-kitchen-sink/ari`, `v2/rsvp-kitchen-sink/default-full`, `v2/rsvp-kitchen-sink/default-must-login`, `v2/rsvp-kitchen-sink/default-no-description`, `v2/rsvp-kitchen-sink/default-unlimited`, `v2/rsvp-kitchen-sink/default`, `v2/rsvp-kitchen-sink/form-going`, `v2/rsvp-kitchen-sink/form-not-going`, `v2/rsvp-kitchen-sink/success`, `v2/rsvp`, `v2/rsvp/actions`, `v2/rsvp/actions/full`, `v2/rsvp/actions/rsvp`, `v2/rsvp/actions/rsvp/going`, `v2/rsvp/actions/rsvp/not-going`, `v2/rsvp/actions/success`, `v2/rsvp/actions/success/title`, `v2/rsvp/actions/success/toggle`, `v2/rsvp/actions/success/tooltip`, `v2/rsvp/ari`, `v2/rsvp/ari/form`, `v2/rsvp/ari/form/error`, `v2/rsvp/ari/form/fields`, `v2/rsvp/ari/form/fields/email`, `v2/rsvp/ari/form/fields/meta`, `v2/rsvp/ari/form/fields/name`, `v2/rsvp/ari/form/template/fields`, `v2/rsvp/ari/sidebar`, `v2/rsvp/ari/sidebar/quantity/input`, `v2/rsvp/ari/sidebar/quantity/minus`, `v2/rsvp/ari/sidebar/quantity/plus`, `v2/rsvp/content`, `v2/rsvp/details`, `v2/rsvp/details/attendance`, `v2/rsvp/details/availability`, `v2/rsvp/details/availability/days-to-rsvp`, `v2/rsvp/details/availability/full`, `v2/rsvp/details/availability/remaining`, `v2/rsvp/details/availability/unlimited`, `v2/rsvp/details/description`, `v2/rsvp/details/title`, `v2/rsvp/form/buttons`, `v2/rsvp/form/fields`, `v2/rsvp/form/fields/cancel`, `v2/rsvp/form/fields/email`, `v2/rsvp/form/fields/name`, `v2/rsvp/form/fields/submit`, `v2/rsvp/form/form`, `v2/rsvp/form/going/title`, `v2/rsvp/form/not-going/title`, `v2/rsvp/form/title`, `v2/rsvp/messages/error`, `v2/rsvp/messages/must-login`, `v2/rsvp/messages/success`, `v2/rsvp/messages/success/going`, `v2/rsvp/messages/success/not-going`, `v2/tickets`, `v2/tickets/commerce/fields`, `v2/tickets/commerce/fields/tribe-commerce`, `v2/tickets/footer`, `v2/tickets/footer/quantity`, `v2/tickets/footer/return-to-cart`, `v2/tickets/footer/total`, `v2/tickets/item`, `v2/tickets/item/content`, `v2/tickets/item/content/description-toggle`, `v2/tickets/item/content/description`, `v2/tickets/item/content/inactive`, `v2/tickets/item/content/title`, `v2/tickets/item/extra`, `v2/tickets/item/extra/available`, `v2/tickets/item/extra/available/quantity`, `v2/tickets/item/extra/available/unlimited`, `v2/tickets/item/extra/description-toggle`, `v2/tickets/item/extra/price`, `v2/tickets/item/inactive`, `v2/tickets/item/opt-out`, `v2/tickets/item/quantity-mini`, `v2/tickets/item/quantity`, `v2/tickets/item/quantity/add`, `v2/tickets/item/quantity/number`, `v2/tickets/item/quantity/remove`, `v2/tickets/item/quantity/unavailable`, `v2/tickets/items`, `v2/tickets/notice`, `v2/tickets/opt-out/hidden`, `v2/tickets/submit`, `v2/tickets/submit/button`, `v2/tickets/submit/must-login`, `v2/tickets/title`, `v2/week/grid-body/events-day/event/tooltip/cost`, `v2/week/mobile-events/day/event/cost`
* Language - 2 new strings added, 94 updated, 0 fuzzied, and 0 obsoleted

### [5.0.5] 2021-01-20

* Fix - Prevent potential fatal errors when referencing deleted Tribe Commerce tickets in PayPal orders and API calls. [ET-995]
* Fix - Ensure the currency-related object is available to JavaScript on the Attendee Registration even when there are no tickets shown. [ETP-629]
* Fix - Multiple shortcodes `[tribe_tickets post_id="ID"]` on a single page will now properly work with the Attendee Registration Modal and adding to the cart / checking out. [ETP-627]
* Fix - Ensure trashed orders do not cause the Delete confirmation text to show up when clicking links for attendees in the Attendees Report. [ET-994]
* Language - 2 new strings added, 67 updated, 0 fuzzied, and 3 obsoleted

### [5.0.4.2] 2020-12-29

* Fix - Resolve JavaScript validation issues with start/end date fields when saving tickets in the Classic Editor using a variety of date formats. Props to @therajumandapati for the initial in-depth debugging that helped us get this fix out so quickly! [ET-987]
* Tweak - Point PUE URLs to the correct servers to avoid redirects.

### [5.0.4.1] 2020-12-16

* Fix - Resolve fatal error from the Attendee Registration modal when calling the loading "dot" icons outside of the template context in older views. [ET-986]

### [5.0.4] 2020-12-15

* Fix - Exclude the "RSVP" ticket provider from the providers list in the editor for tickets. [ET-953]
* Fix - Post type settings label typo changed to plural "tickets". [ET-954]
* Fix - RSVP/Ticket's end sale date for non-event post types now defaults to 1 year and 2 hrs from current date instead of 100 years. [ET-954]
* Fix - Remove the Custom Class Name from the block interface for the Event Tickets blocks to prevent extra interface options that are unused. [ET-960]
* Fix - Remove extraneous "Save and checkout" heading from the `registration-js/content.php` view. [ET-955]
* Fix - Prevent PHP notices by setting up the `must_login` argument within the `registration-js/mini-cart.php` view. [ET-955]
* Fix - Make the "Configure Settings" link on the Welcome screen for Event Tickets open up in a new tab. [ET-958]
* Fix - Update loader templates to use new icons from Tribe Common. [ET-588]
* Fix - Resolve PHP notices on the Attendee Registration Page from Tribe Commerce ticket details when multiple Commerce Providers may be available. [ET-599]
* Fix - Prevent potential conflicts with themes like Avada that manually trigger a jQuery ready event during the normal jQuery ready event. [ETP-601]
* Tweak - Add opaque backgrounds for selected bordered elements. [ET-944]
* Tweak - Added admin notice when editing an Events Calendar Pro recurring event that has tickets in classic editor to warn about how tickets will act on recurring events. [ET-949]
* Tweak - Show warning message within the classic ticket editor if no commerce provider is active. [ET-957]
* Tweak - Show warning message within the classic ticket editor for recurring events about the limitations of tickets on recurring events. [ET-947]
* Tweak - Show confirmation dialog before deleting an attendee on the attendee list. [ET-648]
* Tweak - Rearrange Classic Editor's ticket settings so all "Advanced" fields are into the main section, other than the non-RSVP fields for "SKU" and "Ecommerce". [ET-950]
* Language - 16 new strings added, 167 updated, 0 fuzzied, and 7 obsoleted

### [5.0.3.1] 2020-11-19

* Fix - Require Event Tickets Plus 5.1+ for compatibility purposes on certain areas in Event Tickets that have direct calls to Event Tickets Plus functionality. [ET-964]
* Tweak - Changed views: `blocks/tickets/submit`

### [5.0.3] 2020-11-19

* Feature - Added support for the new Individual Attendee Collection functionality included in Event Tickets Plus. It now allows for collection of individual names and emails for each attendee for Tribe Commerce, WooCommerce, and Easy Digital Download tickets. You can enable this option per ticket and choose to make the fields optional or required. [ETP-364]
* Feature - An optional new set of Ticket-specific views have been added that make it easier to customize and require less updating by our team in the future. The new views have greater automated testing coverage to improve long term stability. These views must be enabled in order to make use of the new Individual Attendee Collection feature in Event Tickets Plus. [ETP-364]
* Fix - Calculation fixed for attendee count percentage column while using RSVP only. [ET-876]
* Fix - Correct specificity of checkboxes and radio buttons styles to prevent conflicts with other The Events Calendar family plugins. [ET-922]
* Fix - Ensure shared capacity stock does not reset while updating ticket. [ETP-562]
* Fix - Prevent PHP notices about `$going` not being set in certain template views which would prevent the "Not Going" text from showing up. [ET-943]
* Tweak - Improved performance of certain queries done on the same page for a ticket in regards to capacity and lists of tickets. [ET-917]
* Tweak - Add help section update notice texts for updated directory structure. [ET-929]
* Language - 12 new strings added, 119 updated, 1 fuzzied, and 4 obsoleted
* Tweak - Added filters: `tribe_tickets_attendee_create_individual_name`, `tribe_tickets_attendee_create_individual_email`, `tribe_tickets_data_ticket_ids_have_meta_fields`, `tribe_tickets_rsvp_get_ticket`, `tribe_tickets_has_meta_enabled`, `tribe_{$provider}_email_recipient`, `tribe_tickets_ticket_email_recipient`, `tribe_{$provider}_email_subject`, `tribe_tickets_ticket_email_subject`, `tribe_{$provider}_email_content`, `tribe_tickets_ticket_email_content`, `tribe_{$provider}_email_headers`, `tribe_tickets_ticket_email_headers`, `tribe_{$provider}_email_attachments`, `tribe_tickets_ticket_email_attachments`, `tribe_display_tickets_block_tickets_left_threshold`, `tribe_tickets_ticket_block_submit`, `tribe_tickets_show_original_price_on_sale`, `tribe_tickets_order_link_template_already_rendered`, `tribe_tickets_new_views_is_enabled`, `tribe_tickets_my_tickets_allow_email_resend_on_attendee_email_update`, `tribe_tickets_plus_hide_attendees_list_optout`, `tribe_tickets_block_show_unlimited_availability`
* Tweak - Removed filters: `tribe_tickets_attendee_registration_has_required_meta`, `tribe_tickets_attendee_registration_is_meta_up_to_date`, `tribe_attendee_registration_cart_provider`, `tribe_attendee_registration_form_no_provider_class`, `tribe_attendee_registration_form_classes`, `tribe_attendee_registration_form_class`, `tribe_tpp_email_content`, `tribe_tpp_email_from_name`, `tribe_tpp_email_from_email`, `tribe_tpp_email_headers`, `tribe_tpp_email_attachments`, `tribe_tpp_email_recipient`, `tribe_tpp_email_subject`
* Tweak - Changed views: `blocks/attendees`, `blocks/attendees/description`, `blocks/attendees/gravatar`, `blocks/attendees/title`, `blocks/attendees/view-link`, `blocks/rsvp`, `blocks/rsvp/content-inactive`, `blocks/rsvp/content`, `blocks/rsvp/details`, `blocks/rsvp/details/availability`, `blocks/rsvp/details/description`, `blocks/rsvp/details/title`, `blocks/rsvp/form`, `blocks/rsvp/form/attendee-meta`, `blocks/rsvp/form/details`, `blocks/rsvp/form/email`, `blocks/rsvp/form/error`, `blocks/rsvp/form/form`, `blocks/rsvp/form/name`, `blocks/rsvp/form/opt-out`, `blocks/rsvp/form/quantity-input`, `blocks/rsvp/form/quantity-minus`, `blocks/rsvp/form/quantity-plus`, `blocks/rsvp/form/quantity`, `blocks/rsvp/form/submit-button`, `blocks/rsvp/form/submit-login`, `blocks/rsvp/icon-svg`, `blocks/rsvp/icon`, `blocks/rsvp/loader-svg`, `blocks/rsvp/loader`, `blocks/rsvp/messages/success`, `blocks/rsvp/status`, `blocks/rsvp/status/full`, `blocks/rsvp/status/going-icon`, `blocks/rsvp/status/going`, `blocks/rsvp/status/not-going-icon`, `blocks/rsvp/status/not-going`, `blocks/tickets`, `blocks/tickets/commerce/fields-edd`, `blocks/tickets/commerce/fields-tpp`, `blocks/tickets/commerce/fields-woo`, `blocks/tickets/commerce/fields`, `blocks/tickets/content-description`, `blocks/tickets/content-inactive`, `blocks/tickets/content-title`, `blocks/tickets/content`, `blocks/tickets/extra-available-quantity`, `blocks/tickets/extra-available-unlimited`, `blocks/tickets/extra-available`, `blocks/tickets/extra-price`, `blocks/tickets/extra`, `blocks/tickets/footer-quantity`, `blocks/tickets/footer-total`, `blocks/tickets/footer`, `blocks/tickets/icon-svg`, `blocks/tickets/icon`, `blocks/tickets/item-inactive`, `blocks/tickets/item`, `blocks/tickets/opt-out-hidden`, `blocks/tickets/quantity-add`, `blocks/tickets/quantity-number`, `blocks/tickets/quantity-remove`, `blocks/tickets/quantity-unavailable`, `blocks/tickets/quantity`, `blocks/tickets/registration/attendee/content`, `blocks/tickets/registration/attendee/fields`, `blocks/tickets/registration/attendee/fields/checkbox`, `blocks/tickets/registration/attendee/fields/radio`, `blocks/tickets/registration/attendee/fields/select`, `blocks/tickets/registration/attendee/fields/text`, `blocks/tickets/registration/attendee/submit`, `blocks/tickets/registration/content`, `blocks/tickets/registration/summary/content`, `blocks/tickets/registration/summary/ticket-icon`, `blocks/tickets/registration/summary/ticket-price`, `blocks/tickets/registration/summary/ticket-quantity`, `blocks/tickets/registration/summary/ticket-title`, `blocks/tickets/registration/summary/ticket`, `blocks/tickets/registration/summary/tickets`, `blocks/tickets/registration/summary/title`, `blocks/tickets/submit-button-modal`, `blocks/tickets/submit-button`, `blocks/tickets/submit-login`, `blocks/tickets/submit`, `components/notice`, `modal/item-total`, `modal/registration-js`, `registration-js/attendees/content`, `registration-js/content`, `registration/attendees/content`, `registration/content`, `tickets/email`, `tickets/orders`, `v2/components/icons/error`, `v2/components/icons/guest`, `v2/components/icons/paper-plane`, `v2/components/loader/loader`, `v2/day/event/cost`, `v2/list/event/cost`, `v2/map/event-cards/event-card/actions/cost`, `v2/month/calendar-body/day/calendar-events/calendar-event/tooltip/cost`, `v2/month/mobile-events/mobile-day/mobile-event/cost`, `v2/photo/event/cost`, `v2/rsvp-kitchen-sink`, `v2/rsvp-kitchen-sink/ari`, `v2/rsvp-kitchen-sink/default-full`, `v2/rsvp-kitchen-sink/default-must-login`, `v2/rsvp-kitchen-sink/default-no-description`, `v2/rsvp-kitchen-sink/default-unlimited`, `v2/rsvp-kitchen-sink/default`, `v2/rsvp-kitchen-sink/form-going`, `v2/rsvp-kitchen-sink/form-not-going`, `v2/rsvp-kitchen-sink/success`, `v2/rsvp`, `v2/rsvp/actions`, `v2/rsvp/actions/full`, `v2/rsvp/actions/rsvp`, `v2/rsvp/actions/rsvp/going`, `v2/rsvp/actions/rsvp/not-going`, `v2/rsvp/actions/success`, `v2/rsvp/actions/success/title`, `v2/rsvp/actions/success/toggle`, `v2/rsvp/actions/success/tooltip`, `v2/rsvp/ari`, `v2/rsvp/ari/form`, `v2/rsvp/ari/form/error`, `v2/rsvp/ari/form/fields`, `v2/rsvp/ari/form/fields/email`, `v2/rsvp/ari/form/fields/meta`, `v2/rsvp/ari/form/fields/name`, `v2/rsvp/ari/form/template/fields`, `v2/rsvp/ari/sidebar`, `v2/rsvp/ari/sidebar/quantity/input`, `v2/rsvp/ari/sidebar/quantity/minus`, `v2/rsvp/ari/sidebar/quantity/plus`, `v2/rsvp/content`, `v2/rsvp/details`, `v2/rsvp/details/attendance`, `v2/rsvp/details/availability`, `v2/rsvp/details/availability/days-to-rsvp`, `v2/rsvp/details/availability/full`, `v2/rsvp/details/availability/remaining`, `v2/rsvp/details/availability/unlimited`, `v2/rsvp/details/description`, `v2/rsvp/details/title`, `v2/rsvp/form/buttons`, `v2/rsvp/form/fields`, `v2/rsvp/form/fields/cancel`, `v2/rsvp/form/fields/email`, `v2/rsvp/form/fields/name`, `v2/rsvp/form/fields/submit`, `v2/rsvp/form/form`, `v2/rsvp/form/going/title`, `v2/rsvp/form/not-going/title`, `v2/rsvp/form/title`, `v2/rsvp/messages/error`, `v2/rsvp/messages/must-login`, `v2/rsvp/messages/success`, `v2/rsvp/messages/success/going`, `v2/rsvp/messages/success/not-going`, `v2/tickets`, `v2/tickets/commerce/fields`, `v2/tickets/commerce/fields/tribe-commerce`, `v2/tickets/footer`, `v2/tickets/footer/quantity`, `v2/tickets/footer/return-to-cart`, `v2/tickets/footer/total`, `v2/tickets/item`, `v2/tickets/item/content`, `v2/tickets/item/content/description-toggle`, `v2/tickets/item/content/description`, `v2/tickets/item/content/inactive`, `v2/tickets/item/content/title`, `v2/tickets/item/extra`, `v2/tickets/item/extra/available`, `v2/tickets/item/extra/available/quantity`, `v2/tickets/item/extra/available/unlimited`, `v2/tickets/item/extra/description-toggle`, `v2/tickets/item/extra/price`, `v2/tickets/item/inactive`, `v2/tickets/item/opt-out`, `v2/tickets/item/quantity-mini`, `v2/tickets/item/quantity`, `v2/tickets/item/quantity/add`, `v2/tickets/item/quantity/number`, `v2/tickets/item/quantity/remove`, `v2/tickets/item/quantity/unavailable`, `v2/tickets/items`, `v2/tickets/notice`, `v2/tickets/opt-out/hidden`, `v2/tickets/submit`, `v2/tickets/submit/button`, `v2/tickets/submit/must-login`, `v2/tickets/title`, `v2/week/grid-body/events-day/event/tooltip/cost`, `v2/week/mobile-events/day/event/cost`

### [5.0.2] 2020-10-19

* Fix - Correctly detect ticket provider to support Attendee Information. [ET-915]
* Fix - Correct template override location comments. [ET-919]
* Language - 42 new strings added, 25 updated, 1 fuzzied, and 28 obsoleted

### [5.0.1] 2020-09-21

* Fix - Ensure the Attendees List title for the Attendees report is clean and not replicated on the page for the admin area as well as frontend. [ET-912]
* Fix - Use `the_title` filter when getting list of post titles for the Move Ticket workflow. [ET-909]
* Fix - Use the correct default date for initial Ticket start sale and end sale dates for Classic Editor. [ET-900]
* Fix - We have added the missing ID to the new RSVP block to allow linking to it directly. [ET-904]
* Fix - Prevent 301 redirects to the homepage while handling Tribe Commerce checkout process. [ET-845]
* Fix - Use the correct file path for the modal overrides. It's now correctly using the `your-theme/tribe/tickets/` path. [ETP-432]
* Fix - More thorough validity checking for post IDs, such as to account for a BuddyPress page having a Post ID of zero. [ET-899]
* Fix - Remove duplicate `button_id` from the `$args` in `src/blocks/tickets/submit-button-modal.php`. Props @justlevine for the fix! [ET-907]
* Fix - Ensure we print the required label for the ARI checkboxes. [ETP-361]
* Fix - Prevent PHP errors in the `tickets/view-link.php` template in automated testing suite. [ET-910]
* Tweak - We have added context to some of the strings and labels of the new RSVP block to allow more granular translation. [ET-903]
* Tweak - Added filters: `tribe_tickets_attendees_show_view_title`
* Tweak - Changed views: `blocks/rsvp/status/going`, `blocks/rsvp/status/not-going`, `blocks/tickets/submit-button-modal`, `registration-js/attendees/fields/checkbox`, `tickets/view-link`, `v2/rsvp`, `v2/rsvp/actions/rsvp/going`, `v2/rsvp/actions/rsvp/not-going`, `v2/rsvp/details/attendance`
* Language - 4 new strings added, 53 updated, 0 fuzzied, and 3 obsoleted

### [5.0.0.1] 2020-08-31

* Fix - Prevent the Attendee Registration modal from incorrectly setting RSVP as the provider class. [ET-901]

### [5.0.0] 2020-08-26

* Feature - We've introduced a refined look and experience for RSVPs! New installations of Event Tickets will gain the new look right away. Existing Event Tickets installations can opt-in upon upgrade from our previous version(s). This new design is consistent in both the Classic and Block modes so you have a better visual when creating RSVPs in either WordPress editor. [ET-866]
* Tweak - Removed unused HTML files in the plugin root folder that were there for your reference to our plugin's data collection transparency. This information is included within WordPress' Privacy Guide at /wp-admin/privacy-policy-guide.php [ET-854]
* Fix - Event Tickets Plus' `[tribe_tickets]` shortcode no longer double-renders the Tickets block when using Classic Editor. The issue was caused by _setting_ `global $post` within `\Tribe\Tickets\Events\Attendees_List::should_hide_optout()`, which was called via the `tribe_tickets_plus_hide_attendees_list_optout` filter. [ET-889]
* Fix - Replace usage of MultiByte package when it's not available, use `tribe_strtoupper` instead of `mb_strtoupper` and `mb_detect_encoding` [ETP-411] [ETP-412] [VE-150]
* Tweak - Added filters: `tribe_tickets_rsvp_render_step_template_args_pre_process`, `tribe_tickets_hide_attendees_list_optout`, `tribe_tickets_rsvp_create_attendee_lookup_user_from_email`, `tribe_tickets_rsvp_has_meta`, `tribe_tickets_rsvp_has_meta`
* Tweak - Added actions: `tribe_tickets_before_front_end_ticket_form`
* Tweak - Changed views: `v2/components/fields/birth`, `v2/components/fields/checkbox`, `v2/components/fields/datetime`, `v2/components/fields/email`, `v2/components/fields/number`, `v2/components/fields/radio`, `v2/components/fields/select`, `v2/components/fields/telephone`, `v2/components/fields/text`, `v2/components/fields/url`, `v2/components/loader/loader`, `v2/rsvp-kitchen-sink/form-going`, `v2/rsvp-kitchen-sink/form-not-going`, `v2/rsvp`, `v2/rsvp/actions`, `v2/rsvp/actions/success`, `v2/rsvp/actions/success/title`, `v2/rsvp/actions/success/toggle`, `v2/rsvp/ari`, `v2/rsvp/ari/form`, `v2/rsvp/ari/form/buttons`, `v2/rsvp/ari/form/error`, `v2/rsvp/ari/form/fields`, `v2/rsvp/ari/form/fields/email`, `v2/rsvp/ari/form/fields/meta`, `v2/rsvp/ari/form/fields/name`, `v2/rsvp/ari/form/guest-template`, `v2/rsvp/ari/form/guest`, `v2/rsvp/ari/form/template/fields`, `v2/rsvp/ari/form/template/title`, `v2/rsvp/ari/form/title`, `v2/rsvp/ari/sidebar/guest-list`, `v2/rsvp/ari/sidebar/guest-list/guest-template`, `v2/rsvp/ari/sidebar/guest-list/guest`, `v2/rsvp/ari/sidebar/quantity/input`, `v2/rsvp/details/availability`, `v2/rsvp/details/availability/days-to-rsvp`, `v2/rsvp/details/availability/remaining`, `v2/rsvp/details/availability/unlimited`, `v2/rsvp/form/buttons`, `v2/rsvp/form/fields/email`, `v2/rsvp/form/fields/name`, `v2/rsvp/form/fields/quantity`, `v2/rsvp/form/form`, `v2/rsvp/messages/error`, `v2/rsvp/messages/success`, `v2/rsvp/messages/success/going`, `v2/rsvp/messages/success/not-going`
* Language - 14 new strings added, 54 updated, 3 fuzzied, and 3 obsoleted

### [4.12.3.1] 2020-08-17

* Fix - Prevent attendee registration modal in block editor from closing when clicking into the modal. [GTRIA-275]

### [4.12.3] 2020-07-28

* Feature - Notify promoter for actions (RSVP going, RSVP not going, Event Checkin, Attendee Registered) for RSVP and Tribe Commerce. [ET-860]
* Fix - Prevent PHP errors when loading the new RSVP views for posts using classic editor when The Events Calendar setting for Blocks editor is off. [ET-853]
* Tweak - Layout improvements for Attendee Information's Birth Date field. [ET-875]
* Tweak - Added `tribe_tickets_is_provider_active()` template function so we can more easily check if a ticket's commerce provider is currently active before using its class methods. [ET-843]
* Tweak - Added `Tribe__Tickets_Plus__Tickets::get_attendee_optout_key()` to simplify getting the optout key regardless of ticket provider class and accounting for legacy code. [ETP-843]
* Tweak - Added `Tribe__Tickets__Tickets::get_event_ticket_provider_object()` to make it easier to get and interact with the ticket provider object itself and implemented it across all plugin code where the object is needed in place of the previous usage of class representation as a string. [ET-843]
* Tweak - Added `Tribe__Tickets__Tickets::get_ticket_provider_instance()` to consistently and more concisely get a ticket provider's instance only if it is active. [ET-843]
* Tweak - Updated `Tribe__Tickets__Tickets::get_event_ticket_provider()` so it returns false if the resulting ticket provider is no longer active and implemented it across all plugin code. [ET-843]
* Tweak - Updated `Tribe__Tickets__Status__Manager::get_provider_slug()` and added `Tribe__Tickets__Status__Manager::get_provider_class_from_slug()` for convenient and consistent lookups. [ETP-843]
* Tweak - Display all of a post's order tabs in the Attendees Report admin screen. Example: if a post's ticket provider was Tribe Commerce and such tickets were sold, then the post's provider changed to WooCommerce Tickets, the Attendees Report screen should show both the Tribe Commerce order history tab and the WooCommerce order history tab, regardless of which ticket provider is currently the default. Introduced `Tribe__Tickets__Tickets::post_has_tickets()` helper function. [ET-843]
* Tweak - Modify parameters on `register_rest_route` to include `permission_callback` to prevent notices on WordPress 5.5.
* Language - 8 new strings added, 200 updated, 1 fuzzied, and 1 obsoleted

### [4.12.2] 2020-06-24

* Feature - Added a preview of the new RSVP refresh. View the non-functional demo https://evnt.is/refreshrsvp.
* Fix - Resolve a PHP notice when rendering the Event Tickets Plus field for Birth date [ETP-330]
* Tweak - Improve the Birth date field styles that could show up without styling under certain contexts. [ET-835]
* Tweak - Add hook to ticket email template to allow injecting content between ticket details and organizers. [ET-844]
* Tweak - For the `[tribe-user-event-confirmations]` shortcode, link to both the event and the direct My Tickets page. [ET-792]
* Language - 39 new strings added, 26 updated, 0 fuzzied, and 0 obsoleted

### [4.12.1.1] 2020-05-29

* Fix - Resolve PHP notices that can cause the Events to not show tickets or RSVPs. [ET-836]

### [4.12.1] 2020-05-20

* Feature - Added new field types to choose from when requiring Attendee Information on a Ticket or RSVP: Email, URL, Date of Birth, Date and Telephone, when using Event Tickets Plus. [ETP-89]
* Feature - Extend `tribe_events()` with new post filtering options: `has_attendees`, `attendee`, `attendee__not_in`, and `attendee_user`. [ET-618]
* Feature - Add new `tribe( 'tickets.post-repository' )` object that works similar to `tribe_events()` but supports any post type. [ET-618]
* Fix - Remove opinionated `max-width: none;` on generic elements over the Attendee Registration page CSS to prevent theme conflicts. [ETP-314]
* Fix - Attendee Registration page briefly showing notice while tickets form is loading. [ETP-241]
* Fix - Ensure defaults are passed into `Tribe__Tickets__Editor__Template->attr()` correctly instead of defaulting to an empty array. [TEC-2964]
* Fix - Prevent problems with `func_get_args()` usage around template inclusion for legacy template files. [TEC-3104]
* Fix - Use unique HTML id for checkboxes and radio on the Attendee Registration fields to prevent conflicts. [ETP-306]
* Fix - Update several templates to account for a passed post to be of an unregistered post type, such as for an Event post when The Events Calendar plugin is disabled. [ET-787]
* Tweak - Ensure tickets labels use a function to retrieve the label so that they can be filtered. [ETP-119]
* Tweak - Output the internal Post ID to the Attendees Report's Event Title, each Ticket Name, and each Attendee. [ET-786]
* Language - 15 new strings added, 412 updated, 14 fuzzied, and 15 obsoleted

### [4.12.0] 2020-04-23

* Fix - When using Event Tickets Plus and using a custom Attendee Registration page with the [tribe_attendee_registration] shortcode, Event Tickets will no longer replace the contents of the page. [ETP-292]
* Fix - Update notice template to print content only when there's information. Remove the "Whoops" title for the Attendee Registration page notice. [ETP-233]
* Fix - Update file path in the docblocks of the templates for The Events Calendar new views. [ETP-289]
* Fix - Update position of the `tribe_tickets_ticket_add` action so it receives the ticket data on ticket creation. [ETP-302]
* Fix - Removed the `type="submit"` from the button element in the ticket quantity remove template. [ETP-224]
* Fix - Removed the duplicate `type="button"` from the button element in the ticket quantity add template. [ETP-224]
* Fix - Correct the `Total` value, when the WooCommerce options for currency are empty (Thousand and decimal separator, number of decimals). [ETP-231]
* Fix - Correct dependencies for the tickets block JS, in order to have the block working in WordPress versions prior to 5.0. [ETP-238]
* Fix - Load plugin text domain on the new 'tribe_load_text_domains' action hook, which fires on 'init' instead of on the 'plugins_loaded' hook. [ET-773]
* Fix - Deprecate `Tribe__Tickets__Tickets_Handler::get_total_event_capacity()` and replace its usage with `tribe_get_event_capacity()`, which returns the correct count. [ET-770]
* Fix - When updating to Event Tickets 4.12, a background migration will get kicked off that fixes ticket-supported posts that use the Attendees List block or the Attendees List shortcodes from Event Tickets Plus so those attendees appear in the REST API properly. [ET-777]
* Fix - Prevent Blocks editor from throwing browser alert when leaving the page without any changes applied to the edited post.
* Tweak - Improved on meta data handling of for Blocks editor.
* Tweak - Deprecate Select2 3.5.4 in favor of SelectWoo
* Tweak - Consolidate duplicate code for getting tickets for each Ticket Provider. [ETP-235]
* Tweak - Modify new views implementation for The Events Calendar, in order to include the "Sold Out" message as we had in the previous version. [ET-764]
* Tweak - Attendee REST endpoint now returns 401 (Unauthorized) if Event Tickets Plus is not loaded. [ETP-297]
* Tweak - Implement price suffix for the tickets block when a price suffix is provided by a Commerce provider. [ET-620]
* Tweak - When using Event Tickets Plus, the checkbox "Hide my attendee data from public view" will no longer show up when purchasing a ticket if an "Attendees List" is not being displayed in the event. [ETP-624]
* Language - 0 new strings added, 329 updated, 10 fuzzied, and 58 obsoleted

### [4.11.5] 2020-03-18

* Feature - Include Freemius integration to allow opt-in information collection. [ET-595]
* Fix - Prevent unintentionally clearing global capacity settings when saving event/post while using Block Editor is enabled. [ETP-267]
* Fix - Save updated shared capacity for event/post if the argument is passed to the REST API endpoint for a Tribe Commerce ticket. [ETP-267]
* Fix - Make sure when changing unlimited ticket to be a shared capacity ticket that the empty individual capacity gets properly overridden to the shared capacity for Tribe Commerce. [ET-752]
* Fix - Prevent ticket capacity showing sold out when you have unlimited tickets or RSVPs alongside shared capacity tickets unless it really is sold out. [ET-744]
* Fix - The quantity allowed in a single "add to cart" action is now always set (defaults to `100`, filterable via `tribe_tickets_get_ticket_max_purchase`), for sanity and performance reasons. [ETP-149]
* Fix - Correct the displayed quantity of tickets available in the Attendees Report admin screen. It was forcing the formatted number string (e.g. `2,000`) to an integer (this example was displaying as `2`) so only affected tickets with available quantities greater than 999 that weren't Unlimited. [ET-756]
* Fix - Make it so the ticket quantity in the tickets block doesn't jump around when it gets to the maximum available in Safari and you try to increase it. [ET-758]
* Tweak - Notify Promoter of changes when tickets are moved to other Events. [ET-741]
* Tweak - Improved compatibility between Tribe Commerce and Promoter by extending the actions that notify Promoter of Attendee modifications. [ET-746]
* Tweak - Added filters: `tribe_tickets_integrations_should_load_freemius`, `tribe_tickets_get_ticket_default_max_purchase`
* Tweak - Changed views: `blocks/rsvp/form/attendee-meta`, `blocks/rsvp/form/details`, `blocks/rsvp/form/quantity-input`, `blocks/rsvp/form/quantity`, `blocks/tickets/extra-available`, `blocks/tickets/quantity-number`, `tickets/rsvp`, `tickets/tpp`
* Language - 4 new strings added, 47 updated, 2 fuzzied, and 0 obsoleted

### [4.11.4] 2020-02-26

* Fix - Update file path in the docblocks of the templates for The Events Calendar new views. [ET-713]
* Fix - Hitting enter in the tickets form changes ticket quantities. [ETP-43]
* Fix - Respect the page title and fix redirection for the custom attendee registration page. [ETP-156]
* Fix - Ensure we're loading the common full styles when required. This fixes missing styles problems from the tickets block. [ET-725]
* Fix - Adjust JavaScript to have the Attendee Registration page working in IE11. [ETP-220]
* Fix - Add theme compatibility for the Attendee Registration Modal by adding theme identifying body CSS classes. [ETP-156]
* Fix - When Classic Editor plugin is activated, prevent ticket availability AJAX errors by temporarily disabling the AJAX requests. [ET-730]
* Fix - When not using blocks, the scripts to obtain an RSVP ticket now work even if required Attendee Information (from Event Tickets Plus) is missing upon initial attempt to submit the form. [ET-686]
* Fix - Prevent The Events Calendar plugin from overriding the Attendee Registration page content when Events Page is set as site home page. [ET-732]
* Fix - Use the default `datepickerFormat` value if the option hasn't been set yet when setting up validation rules for the ticket add/edit admin form. [ET-727]
* Fix - Resolve problems where "View My Tickets" (or RSVPs) page would be blank or not load. [ET-735]
* Fix - Use accessibility CSS classes for more screen reader text elements. [ET-725]
* Fix - Save initial shared capacity value for global stock correctly on first Tribe Commerce ticket so availability shows as expected instead of zero. [ET-737]
* Tweak - Added filters: `tribe_tickets_theme_compatibility_registered`
* Tweak - Changed views: `blocks/tickets/content-description`, `blocks/tickets/extra`, `blocks/tickets/quantity-add`, `blocks/tickets/quantity-remove`, `registration-js/content`, `v2/day/event/cost`, `v2/list/event/cost`, `v2/map/event-cards/event-card/actions/cost`, `v2/month/calendar-body/day/calendar-events/calendar-event/tooltip/cost`, `v2/month/mobile-events/mobile-day/mobile-event/cost`, `v2/photo/event/cost`, `v2/week/grid-body/events-day/event/tooltip/cost`, `v2/week/mobile-events/day/event/cost`
* Language - 0 new strings added, 76 updated, 0 fuzzied, and 0 obsoleted

### [4.11.3.1] 2020-02-11

* Fix - Resolve potential fatal errors when an object is passed to determine a CSS class where we had expected a string. [ET-716]
* Fix - Prevent conflicts with a template variable used by the Tickets block when rendering while The Events Calendar is activated. [ET-717]
* Fix - Prevent The Events Calendar from disabling the redirect for Tribe Commerce that should take you to PayPal when checking out. [ET-714]
* Fix - Better detect the post ID to use on normal pages for Tribe Commerce. [ET-714]
* Tweak - Changed views: `modal/registration-js`, `registration-js/content`

### [4.11.3] 2020-02-06

* Feature - Show original price on ticket block if ticket on sale. Allow turning off via the `tribe_tickets_show_original_price_on_sale` filter. [ETP-47]
* Fix - Allow adding ticket header image on non-event posts. [ETP-54]
* Fix - Close opening `<div>` in `blocks/attendees.php`. [ET-589]
* Fix - Correct broken JavaScript for themes that change the base post CSS classes. [ET-640]
* Fix - Correct logic so selling out of one RSVP doesn't prevent "purchasing" another. [ETP-603]
* Fix - Price formatting method now prevents incorrect display when a comma is used as the decimal separator. [ETP-53]
* Fix - Disable RSVP and Tickets block when password protection is enabled on posts or pages. [ET-604]
* Fix - Ensure that attendee images display horizontally in the frontend for Twenty Nineteen and Twenty Twenty themes. [ET-590]
* Fix - JavaScript updated to remove IE 11 console errors. [ET-619]
* Fix - Load JavaScript assets along with Ticket Block when using Classic Editor. [ET-587]
* Fix - Override checkout link in WooCommerce Mini-Cart widget so it uses the custom page for attendee registration if it is setup. [ETP-41]
* Fix - Remove inaccurate display of "You don't have tickets for this event" notice at single event page's list of current user's RSVP's and/or Tickets. [ETP-50]
* Fix - The Events Calendar's List View "RSVP Now!" button again displays for Events having only RSVP tickets and has the correct anchor link. [ETP-51]
* Fix - Tickets Block quantity +/- buttons set to 'button' type to avoid submitting Add to Cart form in IE 11 or when JavaScript is disabled. [ET-619]
* Fix - Additional implementation of dynamic ticket text functions so singular and plural versions of "Ticket" change in more areas when filtered. [ETP-145]
* Fix - Ensure that empty start/end dates are treated like "immediately": and "forever", respectively. [ETP-159]
* Tweak - Create new function `tribe_get_event_capacity()` for checking the capacity of an entire event. Have `tribe_tickets_get_capacity()` pass off to it as when given an event. [ETP-48]
* Tweak - Refine logic for the "no results" notice on the "My Tickets" page. [ETP-151]
* Tweak - Remove duplicate ticket script loading to prevent JavaScript conflicts. [ET-596]
* Tweak - Change the Attendee List opt-out checkbox to be checked by default. [ET-615]
* Tweak - Change the Attendee List opt-out checkbox wording, centralize where we handle it and create a new function to retrieve it. [ET-615]
* Tweak - Add some code for future implementation around converting opt-outs to opt-ins. [ET-615]
* Tweak - Adjust styles to ensure our "Get Tickets" button styles get preserved. [ETP-210]
* Tweak - Added filters: `tribe_tickets_default_opt_out_text`, `tribe_tickets_default_opt_in_text`, `tribe_tickets_show_original_price_on_sale`
* Tweak - Changed views: `blocks/attendees`, `blocks/attendees/description`, `blocks/rsvp/form/error`, `blocks/rsvp/form/opt-out`, `blocks/rsvp/form/quantity-plus`, `blocks/rsvp/form/submit-button`, `blocks/rsvp/messages/success`, `blocks/tickets`, `blocks/tickets/extra-available`, `blocks/tickets/extra-price`, `blocks/tickets/extra`, `blocks/tickets/footer-total`, `blocks/tickets/item`, `blocks/tickets/opt-out-hidden`, `blocks/tickets/quantity-add`, `blocks/tickets/quantity-remove`, `blocks/tickets/registration/summary/title`, `blocks/tickets/submit-button-modal`, `blocks/tickets/submit-button`, `modal/item-remove`, `modal/item-total`, `modal/registration-js`, `registration-js/attendees/ticket`, `tickets/email`, `tickets/orders-pp-tickets`, `tickets/orders-rsvp`, `tickets/orders`, `tickets/rsvp`, `tickets/tpp`
* Language - 7 new strings added, 187 updated, 7 fuzzied, and 6 obsoleted

### [4.11.2] 2020-01-27

* Tweak - Adding support for The Events Calendar 5.0.0
* Tweak - Added new `tribe_events_is_current_time_in_date_window()` function that checks if the current datetime is within a post's ticket availability window [TEC-3033]
* Language - 0 new strings added, 94 updated, 0 fuzzied, and 0 obsoleted

### [4.11.1] 2019-12-19

* Feature - Use the same loading icon for the RSVP block that we're using for the tickets block. [135660]
* Tweak - Added `tribe_tickets_is_enabled_post_context()` function [124403]
* Tweak - Avoid loading plugin assets on post types that are not tickets-enabled post types [124403]
* Tweak - Add filter for `ticket-display-tickets-left-threshold` to tickets and RSVP block. Added new filters to allow showing "Unlimited" on unlimited tickets [130660]
* Tweak - Added filters: `tribe_tickets_is_enabled_post_context`, `tribe_rsvp_block_loader_classes`, `tribe_display_rsvp_block_tickets_left_threshold`, `tribe_rsvp_block_show_unlimited_availability`, `tribe_rsvp_block_loader_classes`, `tribe_display_tickets_block_tickets_left_threshold`, `tribe_tickets_block_show_unlimited_availability`, `tribe_display_rsvp_block_tickets_left_threshold`, `tribe_rsvp_block_show_unlimited_availability`, `tribe_display_tickets_block_tickets_left_threshold`, `tribe_tickets_block_show_unlimited_availability`
* Tweak - Changed views: `blocks/rsvp`, `blocks/rsvp/details/availability`, `blocks/rsvp/form/quantity-input`, `blocks/rsvp/loader`, `blocks/tickets`, `blocks/tickets/extra-available-quantity`, `blocks/tickets/extra-available`, `blocks/tickets/extra`, `blocks/tickets/item`, `blocks/tickets/quantity`, `blocks/tickets/registration/attendee/submit`, `tickets/rsvp`, `tickets/tpp`
* Fix - Fix the header image attachment handling for RSVP blocks [137243]
* Fix - Ensure that tickets without an end date set in the Classic editor get set to end at the start of an event per the tooltip [125969]
* Fix - Make "Show attendees list on event page" checkbox apply to the REST API as well. [133333]
* Fix - Overriding the maximum purchase quantity (the `tribe_tickets_get_ticket_max_purchase` filter) now works in all contexts [133432]
* Fix - Prevent duplicate blocks on provider change. Add logic to test current provider against event default provider. [137925]
* Fix - If running WP 5.3+, add `show_in_rest` as an array configuration for capacity and the RSVP not going fields so that they save properly. [137875]
* Fix - Gracefully handle enter key in modal form to prevent missing data when submitting. [136595]
* Fix - Increase size of -/+ signs for decreasing/increasing quantity on tickets. [138558]
* Fix - Handle special characters for Event Ticket field labels like single quotes and colons so they don't break the saving. [136451]
* Fix - Don't try to load RSVPs with the Tickets block `render()`. [138646]
* Fix - Correct shared capacity handling when tickets left threshold is set. [138620]
* Language - 0 new strings added, 131 updated, 1 fuzzied, and 0 obsoleted

### [4.11.0.1] 2019-12-11

* Fix - Avoid running extra unnecessary queries when registering assets [138390]
* Fix - Make `Tribe__Tickets__Tickets::get_tickets()` protected to avoid errors with it not being public before upgrading ET+ [138385]

### [4.11] 2019-12-10

* Feature - Add ability to utilize the block ticket template outside of Gutenberg views [132568]
* Feature - Use the block template view for the "classic" editor so they look the same now on the frontend [132568]
* Feature - Implement a copy of tribe-common-styles and restyle the front end tickets block [131117]
* Feature - Add currency formatting by commerce [133179]
* Tweak - Clean up the way we add options to the ticket settings tab in PHP to make it more readable and maintainable. [133048]
* Tweak - Add ability to track installed version history. Added `$version_history_slug` and `$latest_version_slug` properties to `Tribe__Tickets_Plus__Main` [133048]
* Tweak - Minimum required WordPress version updated to WordPress 4.9
* Tweak - Added filters: `tribe_tickets_modal_setting`, `tribe_events_tickets_template_`, `tribe_attendee_registration_cart_provider`, `tribe_tickets_commerce_cart_get_tickets_`, `tribe_tickets_commerce_cart_get_ticket_meta`, `tribe_tickets_commerce_cart_get_cart_url_`, `tribe_tickets_commerce_cart_get_checkout_url_`, `tribe_tickets_commerce_cart_get_data`, `tribe_edd_format_amount_decimals`, `tribe_format_amount_decimals`, `tribe_format_amount`, `tribe_format_amount_with_symbol`, `tribe_tickets_commerce_paypal_notify_url`, `tribe_tickets_commerce_paypal_custom_args`, `tribe_tickets_commerce_paypal_add_to_cart_args`, `tribe_tickets_commerce_paypal_gateway_add_to_cart_redirect`, `tribe_tickets_commerce_paypal_invoice_number`, `tribe_tickets_tribe-commerce_cart_url`, `tribe_tickets_tribe-commerce_checkout_url`, `tribe_tickets_availability_check_interval`, `tribe_tickets_checkout_urls`, `tribe_tickets_cart_urls`, `tribe_tickets_availability_check_interval`, `tribe_tickets_checkout_urls`, `tribe_tickets_cart_urls`, `tribe_tickets_order_link_template_already_rendered`, `tribe_tickets_order_link_template_already_rendered`, `tribe_tickets_block_loader_classes`, `tribe_events_tickets_attendee_registration_modal_content`, `tribe_tickets_ticket_block_submit`, `tribe_tickets_loader_text`, `tribe_tickets_modal_loader_classes`, `tribe_tickets_order_link_template_already_rendered`
* Tweak - Added actions: `tribe_tickets_commerce_cart_update_tickets_`, `tribe_tickets_commerce_cart_update_tickets`, `tribe_tickets_commerce_cart_update_ticket_meta`, `event_tickets_rsvp_after_attendee_update`, `tribe_tickets_registration_content_before_all_events`, `tribe_tickets_registration_content_before_all_events`, `tribe_tickets_registration_content_after_all_events`
* Tweak - Changed views: `blocks/attendees`, `blocks/rsvp`, `blocks/rsvp/form/opt-out`, `blocks/tickets`, `blocks/tickets/content-description`, `blocks/tickets/content-inactive`, `blocks/tickets/content-title`, `blocks/tickets/content`, `blocks/tickets/extra-available-quantity`, `blocks/tickets/extra-available-unlimited`, `blocks/tickets/extra-available`, `blocks/tickets/extra-price`, `blocks/tickets/extra`, `blocks/tickets/footer-quantity`, `blocks/tickets/footer-total`, `blocks/tickets/footer`, `blocks/tickets/icon-svg`, `blocks/tickets/icon`, `blocks/tickets/item-inactive`, `blocks/tickets/item`, `blocks/tickets/opt-out-hidden`, `blocks/tickets/quantity-add`, `blocks/tickets/quantity-number`, `blocks/tickets/quantity-remove`, `blocks/tickets/quantity-unavailable`, `blocks/tickets/quantity`, `blocks/tickets/registration/attendee/content`, `blocks/tickets/registration/attendee/fields/checkbox`, `blocks/tickets/registration/attendee/fields/radio`, `blocks/tickets/registration/attendee/fields/select`, `blocks/tickets/registration/attendee/fields/text`, `blocks/tickets/registration/attendee/submit`, `blocks/tickets/registration/content`, `blocks/tickets/registration/summary/content`, `blocks/tickets/registration/summary/description`, `blocks/tickets/registration/summary/ticket-icon`, `blocks/tickets/registration/summary/ticket-price`, `blocks/tickets/registration/summary/ticket-quantity`, `blocks/tickets/registration/summary/ticket-title`, `blocks/tickets/registration/summary/ticket`, `blocks/tickets/registration/summary/tickets`, `blocks/tickets/registration/summary/title`, `blocks/tickets/submit-button-modal`, `blocks/tickets/submit-button`, `blocks/tickets/submit-login`, `blocks/tickets/submit`, `components/loader`, `components/notice`, `modal/cart`, `modal/item-remove`, `modal/item-total`, `modal/registration-js`, `modal/registration`, `registration-js/attendees/content`, `registration-js/attendees/fields`, `registration-js/attendees/fields/checkbox`, `registration-js/attendees/fields/radio`, `registration-js/attendees/fields/select`, `registration-js/attendees/fields/text`, `registration-js/attendees/ticket`, `registration-js/content`, `registration-js/mini-cart`, `registration/attendees/content`, `registration/attendees/error`, `registration/attendees/fields`, `registration/attendees/fields/checkbox`, `registration/attendees/fields/radio`, `registration/attendees/fields/select`, `registration/attendees/fields/text`, `registration/attendees/loader`, `registration/attendees/success`, `registration/button-cart`, `registration/button-checkout`, `registration/content`, `registration/summary/content`, `registration/summary/description`, `registration/summary/registration-status`, `registration/summary/ticket/content`, `registration/summary/ticket/icon-svg`, `registration/summary/ticket/icon`, `registration/summary/ticket/price`, `registration/summary/ticket/quantity`, `registration/summary/ticket/title`, `registration/summary/tickets-header`, `registration/summary/tickets`, `registration/summary/title`, `registration/summary/toggle-handler`, `tickets/orders`, `tickets/rsvp`, `tickets/tpp-success`, `tickets/tpp`, `tickets/view-link`
* Fix - Pass missing 'provider' argument from `views/registration/content.php` to `views/registration/button-cart.php` [131896]
* Language - 0 new strings added, 75 updated, 0 fuzzied, and 2 obsoleted

### [4.10.11.1] 2019-11-18

* Fix - Force null values to 0 for `_tribe_ticket_capacity` so RSVPs save correctly in 5.3 block editor. [137383]
* Fix - Bypass REST update/delete of virtual meta key `_tribe_tickets_list` so events will save in WP 5.3. [137383]
* Fix - Allow `null` to be sent for REST API updates in WP 5.3 for certain meta fields that we intentionally send null for but don't match the registered schema type. [137383]
* Fix - Handle the onRequestClose action in element.js to prevent Attendee Information modal closing when clicking within the modal. [137394]

### [4.10.11] 2019-11-13

* Fix - Add a check for empty tickets to `Tribe__Tickets__Editor__Blocks__Tickets::ticket_availability()` method to avoid PHP error notices showing [122334]
* Fix - Correctly get the Event / Post ID within the `Tribe__Tickets__Editor__Blocks__Rsvp::rsvp_process` method to ensure the right ID gets saved with the RSVP [135409]
* Language - 0 new strings added, 74 updated, 0 fuzzied, and 0 obsoleted

### [4.10.10] 2019-10-14

* Tweak - Changed views: `blocks/tickets`, `tickets/tpp`
* Fix - Attendees Report's "Orders" tab now displays amount sold and available regardless of amount, including for unlimited and zero remaining for Tribe Commerce attendees [134108]
* Fix - Prevent fatal errors when hosting environment does not support multibyte functionality by using new `tribe_strpos()` function [135202]
* Fix - Prevent Attendee Registration saving from storing only the last attendee's information for all RSVP attendees [134408]
* Fix - Remove check for tickets in beginning of `/src/views/blocks/tickets.php` as it prevents showing the "tickets unavailable" message [134821]
* Language - 2 new strings added, 19 updated, 0 fuzzied, and 0 obsoleted

### [4.10.9] 2019-10-01

* Feature - New functions to easily rename ticket types and ensure consistent wording: `tribe_get_rsvp_label_singular()`, `tribe_get_rsvp_label_singular_lowercase()`, `tribe_get_rsvp_label_plural()`, `tribe_get_rsvp_label_plural_lowercase()`, `tribe_get_ticket_label_singular()`, `tribe_get_ticket_label_singular_lowercase()`, `tribe_get_ticket_label_plural()`, and `tribe_get_ticket_label_plural_lowercase()` [130897]
* Tweak - Allow Admin and Editor users to see Attendees in REST API responses by default [128298]
* Tweak - Notify Promoter if an event with tickets is deleted [134113]
* Tweak - Added filters: `tribe_display_tickets_left_threshold`, `tribe_events_tickets_views_v2_is_enabled`, `tribe_get_rsvp_label_singular`, `tribe_get_rsvp_label_singular_lowercase`, `tribe_get_rsvp_label_plural`, `tribe_get_rsvp_label_plural_lowercase`, `tribe_get_ticket_label_singular`, `tribe_get_ticket_label_singular_lowercase`, `tribe_get_ticket_label_plural`, `tribe_get_ticket_label_plural_lowercase`, `tribe_tickets_filter_showing_tickets_on_attendee_registration`
* Tweak - Changed views: `blocks/attendees/view-link`, `blocks/rsvp/content-inactive`, `blocks/rsvp/form/error`, `blocks/rsvp/form/quantity`, `blocks/rsvp/form/submit-button`, `blocks/rsvp/icon`, `blocks/rsvp/messages/success`, `blocks/rsvp/status/full`, `blocks/tickets/registration/summary/title`, `registration/content`, `tickets/email`, `tickets/orders-pp-tickets`, `tickets/orders-rsvp`, `tickets/orders`, `tickets/rsvp`, `tickets/tpp-success`, `tickets/tpp`, `tickets/view-link`, `v2/day/event/cost`, `v2/list/event/cost`, `v2/map/event-cards/event-card/event/actions/cost`, `v2/month/calendar-body/day/calendar-events/calendar-event/tooltip/cost`, `v2/month/mobile-events/mobile-day/mobile-event/cost`, `v2/photo/event/cost`, `v2/week/grid-body/events-day/event/tooltip/cost`, `v2/week/mobile-events/day/event/cost`
* Fix - The attendee link in the ticket and RSVP block so it shows after the creation of a ticket or RSVP. [128521]
* Fix - Prevent conflict with Genesis Framework where content or the excerpt does not show in the post archives [125496]
* Fix - Prevent tickets that do not have attendee meta from showing on the attendee registration page [125021]
* Fix - Prevent multiple clicks on `Confirm RSVP` from submitting entries [132961]
* Fix - Make 'Not going' available to translate in RSVP dropdown [134358]
* Fix - Update how we intercept the singular event template when The Events Calendar is active, on events created using the Block editor so that you can view 'My Tickets' correctly [134583]
* Language - 101 new strings added, 158 updated, 48 fuzzied, and 104 obsoleted

### [4.10.8] 2019-09-16

* Tweak - Renamed `src/views/tickets/orders-link.php` to `src/views/tickets/view-link.php` and renamed `src/views/blocks/attendees/order-links.php` to `src/views/blocks/attendees/view-link.php` for improved and consistent naming between Classic and Block Editor templates [130955]
* Tweak - Tribe Commerce PayPal Tickets now sets Euro currency symbol after amount (postfix/suffix) if WordPress site language is non-English, to match EU's guidelines [128532]
* Tweak - Add class to wrapper div around ticket controls in admin [127193]
* Tweak - Smarter plugin dependency checking with more accurate admin notices if not all requirements are satisfied [131080]
* Tweak - Reduced file size by removing .po files and directing anyone creating or editing local translations to translate.wordpress.org
* Tweak - Make the ticket form price field disable-able via a filter and make its description text filterable as well. [132274]
* Tweak - Allow text to wrap in attendees and orders report tables to avoid text going into other columns. [133195]
* Tweak - Added Order ID and Product ID search types to Attendees Report and fixed Ticket ID search type to be based on the ticket's Post ID [132248]
* Tweak - Added filters: `tribe_tickets_get_total_complete`, `tribe_tickets_get_total_refunded`, `tribe_tickets_price_description`, `tribe_tickets_price_disabled`
* Tweak - Changed views: `blocks/attendees/order-links`, `blocks/rsvp`, `blocks/rsvp/form/submit-login`, `blocks/tickets`, `blocks/tickets/quantity-number`, `registration/attendees/fields/select`, `tickets/orders`, `tickets/rsvp`, `tickets/tpp`, `tickets/orders-link`
* Fix - Front-end search box (Community Tickets) input name changed to no longer trigger a theme's Search template [132248]
* Fix - Fix potential issues with query that had no upper limit set when all that's needed is to check if it had one item [133247]
* Fix - Support refunded attendee counting/handling for Tribe Commerce and Event Tickets Plus WooCommerce orders [126734]
* Fix - Correct text domain in Tribe Commerce admin view [127645]
* Fix - Correct the sold count in Attendees Report for unlimited stock Tribe Commerce tickets (was previously showing negative), and improve text to always display quantities sold and remaining for all ticket types [128666]
* Fix - Correct the docblock and variable names passed to the `tribe_tickets_get_ticket_max_purchase` filter and update RSVP and Tribe Commerce ticket templates to only display the available quantity [119822]
* Fix - Make the "Tickets" heading not appear on a single event page if there are only past Tribe Commerce tickets [130748]
* Fix - Fatal error when sending the attendee list by email in WordPress 4.9 or earlier [134061]
* Fix - The "View your RSVPs and Tickets" link was not appearing when using the Block Editor for Events [128512]
* Fix - Admin ticket editor was not displaying currency symbol at all if set to display after amount (postfix/suffix) [128532]
* Fix - Update `Tribe__Tickets__Tickets_View::get_description_rsvp_ticket()` to determine when to use the appropriate singular or plural texts based on the quantity found for each ticket type and deprecated its third parameter (_bool_ `$plurals`) (props @solepixel for pointing us to the issue) [129582]
* Fix - Correct two places where the translation domain was incorrect. Thanks to @cfaria for the catch! [128193]
* Fix - Allow saving RSVP status changes (Going / Not Going) even if tickets have no Attendee Information fields [128629]
* Language - 5 new strings added, 132 updated, 0 fuzzied, and 3 obsoleted

### [4.10.7.2] 2019-09-03

* Fix - Prevent formulas from being exported when exporting attendees to CSV [133550]

### [4.10.7.1] 2019-08-27

* Fix - Resolve JS console warnings from `tooltip.js` in `tribe-common` by adding missing `tribe` var when the var is not setup on the current page already [133207]

### [4.10.7] 2019-08-22

* Tweak - Use unique IDs for tabbed views, correct styles to adapt [131430]
* Tweak - Add hook under the price description field of the admin ticket editor [128843]
* Tweak - Modify methods to check for a post id of 0 to prevent PHP notices [128346]
* Tweak - Added filters: `tribe_tickets_attendees_table_classes`, `tribe_tickets_commerce_order_table_classes`, `tribe_tickets_order_report_show_title`, `tribe_tickets_tpp_order_report_show_title`, `tribe_tickets_tpp_order_report_title`
* Tweak - Added actions: `tribe_tickets_price_input_description`
* Fix - Correct hardcoded table name in `tribe-user-event-confirmations` shortcode [129402]
* Language - 1 new strings added, 66 updated, 0 fuzzied, and 0 obsoleted

### [4.10.6.2] 2019-06-20

* Fix - Prevent issue where older versions of the tribe-common libraries could be bootstrapped [129479]
* Fix - Add Promoter PCSS file so that the proper CSS will be generated on package build [129584]

### [4.10.6.1] 2019-06-13

* Tweak - Adjust newsletter signup submission destination [129034]
* Fix - Resolve hardcoded reference to `wp_posts` table in optout ORM queries [129053]
* Language - 0 new strings added, 8 updated, 0 fuzzied, and 0 obsoleted

### [4.10.6] 2019-05-23

* Feature - Implemented our abstract Object-relational Mapping (ORM) layer where Ticket Attendees are called throughout the plugin and increased stability with more automated tests [123468]
* Tweak - Added ability to query attendees by provider using `tribe_attendees( 'rsvp' )`, `tribe_attendees( 'tribe-commerce' )`, and other providers registered by Event Tickets Plus [123468]
* Tweak - Added new Attendees querying filters with `tribe_attendees()` including: `order`, `order__not_in`, `product_id`, `product_id__not_in`, `purchaser_name`, `purchaser_name__not_in`, `purchaser_name__like`, `purchaser_email`, `purchaser_email__not_in`, `purchaser_email__like`, `security_code`, `security_code__not_in`, `user`, `user__not_in`, `price`, `rsvp_status__or_none`, `provider`, `provider__not_in`, and `order_status__not_in` [123468]
* Tweak - Added new `no_or_none` option for `tribe_attendees()` filtering by `optout` to return attendees if they have not opted out or not provided their intention yet [123468]
* Tweak - Added ability to select which attendee field to search on Attendees admin screen; Added ability to search attendees by User ID and Ticket ID; Removed ability to search by purchase time and ticket name to improve search performance [128202]
* Tweak - Only show RSVP totals when an Event or Post has an RSVP set up on it or if it has attendees [128071]
* Tweak - Added filters: `tribe_tickets_search_attendees_by_like`, `tribe_tickets_search_attendees_types`, `tribe_tickets_attendees_list_limit_attendees`
* Fix - Prevent multiple Tickets or RSVP blocks from being created in the block editor, limit blocks to one instance of each per post [127507]
* Language - 6 new strings added, 82 updated, 0 fuzzied, and 0 obsoleted

### [4.10.5] 2019-05-14

* Feature - Add tooltips to Attendee Report page [120856]
* Feature - Add tooltip to explain what statues are behind Pending Order Completion [120862]
* Feature - Add tooltip to explain the Available Count Per Ticket [120862]
* Feature - Add tooltips to explain the sold & available amounts in the ticket block [121992]
* Tweak - Add method to get all possible names of the completed status by ecommerce provider [122458]
* Tweak - Change success message for ticket move [102635]
* Tweak - Ticket Attendee and Order Page Header css by changing overflow to visible [120862]
* Tweak - Update Status Manager to accept provider names or abbreviations [120862]
* Tweak - In the Ticket Block add link to EDD Orders Page [121440]
* Tweak - Change "Attendee Registration" to "Attendee Information" in several locations [126038]
* Tweak - Exclude WooCommerce Product and EDD Downloads as supported post types when saving for tickets to prevent recursion errors, in case they were previously saved before we removed them from the options list [126749]
* Tweak - Added filters: `tribe_tickets_plus_get_total_cancelled`
* Tweak - Added actions: `tribe_ticket_available_warnings`
* Tweak - Changed views: `registration/content`
* Fix - Add checks to `tribe_events_count_available_tickets()` and `tribe_events_has_unlimited_stock_tickets()` to properly detect unlimited tickets. [119844]
* Fix - Change `inventory` to compare the correct ticket when checking event shared capacity [119844]
* Fix - Make Attendees Report match the order report, specifically "Total Tickets Issued" should not include cancelled tickets [69823]
* Fix - On deletion of an attendee update the shared capacity for Tribe Commerce Tickets [106516]
* Fix - On the Attendee page use the shared capacity in Overview if ticket has it enabled  [106516]
* Fix - Ensure capacity changes for source and target tickets when moving a ticket from one type to another [102636]
* Fix - Correct escaping on attendee registration shortcode [125964]
* Fix - Fix error with creating new ticket in block editor [126266]
* Fix - Fix issue where Tribe Commerce would not submit correctly when using the attendee registration shortcode [126779]
* Fix - Fix autoloader usage so it loads the correct latest version of Tribe Common [127173]
* Language - 10 new strings added, 45 updated, 1 fuzzied, and 6 obsoleted

### [4.10.4.4] 2019-05-03

* Fix - Prevent Composer autoloader from throwing Fatal due to non-existent `setClassMapAuthoritative()` method as the previous fix only applied to coordinated The Events Calendar release [126988]

### [4.10.4.3] 2019-04-26

* Fix - Prevent Composer autoloader from throwing Fatal due to non-existent `setClassMapAuthoritative()` method [126590]

### [4.10.4.2] 2019-04-25

* Fix - Avoid fatal errors due to Events Query method not been present by bumping version of The Events Calendar required to 4.9.0.2

### [4.10.4.1] 2019-04-25

* Fix - Fix error with creating new ticket in block editor [126266]
* Fix - Prevent PHP fatal errors with Tribe__Utils__Array aliases

### [4.10.4] 2019-04-23

* Tweak - Changed minimum supported version of The Events Calendar to 4.9
* Tweak - Add function and hooks for provider classes added to the attendee registration form [124997]
* Tweak - Restyle RSVP block in the front end [123196]
* Tweak - Allow reordering of ticket blocks in the block editor to be preserved in the front end [121703]
* Tweak - After deleting attendees you are now redirected back to the admin attendee page to clear the url of the deleting actions [122083]
* Tweak - Tribe Commerce knowledge base article link now opens up in a new window [122331]
* Tweak - Attendee registration fields configuration for block editor opens up in modal [123818]
* Tweak - Move IPN settings together in ticket settings tab [122333]
* Tweak - Change attendee registration page shortcode to use ID instead of page slug, add function for backward compatibility [124997]
* Tweak - Added filters: `tribe_attendee_registration_form_no_provider_class`, `tribe_attendee_registration_form_classes`, `tribe_attendee_registration_form_class`
* Tweak - Changed views: `blocks/rsvp/form/quantity`, `blocks/rsvp/icon-svg`, `blocks/rsvp/status/going-icon`, `blocks/rsvp/status/going`, `blocks/rsvp/status/not-going-icon`, `blocks/rsvp/status/not-going`, `registration/content`
* Tweak - Changed some attendee report tooltip text to clarify capacity/inventory/stock and added a link for more information about Availability [126342]
* Fix - Change RSVP import identifier in CSV importer so it provides the correct response message [124052]
* Fix - Filter the Attendee Registration display to only show tickets for the current provider and add provider to Attendee Registration URL [122317]
* Fix - Prevent potential PayPal issues by not allowing $0 tickets in the block editor for Tribe Commerce [123835]
* Fix - When moving an attendee prevent shared capacity from being enabled on the receiving event [120727]
* Fix - Tidy attendee list print styles [125299]
* Fix - Use tribe.context->doing_cron to avoid issues with WordPress versions before 4.8 [26111]
* Fix - Prevent PHP notices when looking for a template that does not exist in `tribe_tickets_get_template_part()` (props @stian-overasen) [125913]
* Fix - Correct issue with attendee registration information not saving on shortcode page [125964]
* Language - 1 new strings added, 46 updated, 0 fuzzied, and 1 obsoleted

### [4.10.3] 2019-04-17

* Feature - Compatibility with the Object Relational Mapping introduced on version 4.9 of The Events Calendar
* Tweak - Improving cost symbol usage across all Commerces
* Fix - Make sure we are not displaying ticket for non-logged users on the Rest API that can edit the Event
* Language - 0 new strings added, 38 updated, 0 fuzzied, and 0 obsoleted

### [4.10.2] 2019-04-01

* Tweak - Update hooks attached to tickets to notify Promoter [124118]
* Tweak - Use buttons instead of links and add better feedback on checkin (disable buttons) [70618]
* Tweak - Use `get_stylesheet_directory()` instead of `get_template_directory()` to honor child themes for Attendee Registration template [123613]
* Tweak - Remove empty "Primary Info" column from attendee list email and export [122274]
* Tweak - Only show Attendee data in the REST API for tickets if the Event/Post has the Attendees shortcode/block, with new filter `tribe_tickets_rest_api_always_show_attendee_data` to always show it. Promoter bypasses this for it's own requests [117668]
* Tweak - Added filters: `tribe_providers_in_cart`, `tribe_tickets_rest_api_always_show_attendee_data`
* Tweak - Changed views: `blocks/tickets/registration/attendee/fields/checkbox`, `blocks/tickets/registration/attendee/fields/radio`, `registration/attendees/fields/checkbox`, `registration/attendees/fields/radio`, `registration/button-checkout`, `tickets/orders`
* Fix - Add variable not defined when a ticket was moved to a different event [124164]
* Fix - Resolve problems with `WP_Theme::get_page_templates()` usage, use `array_keys()` instead of `array_values()` since the array is keyed by filename, not template name. Props to @eri-trabiccolo for flagging this! [123613]
* Fix - Allow IE users to increment/decrement the ticket quantity field via the buttons [121073]
* Fix - Use a md5 hash for checkbox and radio option names to prevent fields from not saving if they a large amount of characters [119448]
* Fix - Remove duplicate coding to update RSVP stock when deleting an attendee [123334]
* Fix - When updating RSVP stock use the capacity - minus complete attendees for the new stock number and prevent negative stock [123334]
* Fix - Fix React console warnings when editing events [121198]
* Fix - Correct attendee list page on posts and pages [123514]
* Fix - Connect Tribe Commerce PayPal tickets into the shared capacity and fix counts in PayPal sales report [109419]
* Fix - Show RSVP on list view when it's the only attached ticket [123124]
* Fix - Make submit button dependent on presence of editable meta data [114111]
* Fix - Allow the PayPal confirmation email address sender to be empty, so it can default to the WordPress site email address [122745]
* Fix - Stop claiming that the Attendee Registration page is an archive, add shortcode to display on any page [123044]
* Fix - Remove CSS that was hiding the RSVP form when Blocks are disabled [123136]
* Fix - Prevent the classic RSVP form from showing in block-enabled posts on front-end [124394]
* Language - 1 new strings added, 116 updated, 0 fuzzied, and 1 obsoleted

### [4.10.1.2] 2019-03-14

* Tweak - Update hooks attached to tickets to notify Promoter [124118]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted

### [4.10.1.1] 2019-03-06

* Feature - Add hooks to notify Promoter when an event with tickets has changes [123732]
* Fix - Correct variable name that is throwing undefined errors on checkin [123756]
* Language - 0 new strings added, 23 updated, 0 fuzzied, and 0 obsoleted

### [4.10.1] 2019-02-26

* Tweak - Add a new setting to set a threshold on whether to show the "Display # tickets left" along with a new filter `tribe_tickets_left_threshold` [119910]
* Tweak - Add duration error for duration issues with ticket and RSVP [122679]
* Tweak - Save attendee information on registration page via ajax [121592]
* Tweak - Make clipboard icon for ticket block a tooltip and persistent [122444]
* Tweak - Indicate required fields in ticket block [122442]
* Tweak - Add filter for attendee registration page template [121223]
* Tweak - Add filter to manage attendees permissions [123070]
* Tweak - Add filter to allow email form to be shown for non-admins [123070]
* Tweak - Added filters: `tribe_tickets_attendee_registration_page_template`, `tribe_tickets_user_can_manage_attendees`, `tribe_allow_admin_on_frontend`, `tribe_display_tickets_left_threshold`
* Tweak - Changed views: `registration/attendees/content`, `registration/attendees/error`, `registration/attendees/fields`, `registration/attendees/fields/checkbox`, `registration/attendees/fields/radio`, `registration/attendees/fields/select`, `registration/attendees/fields/text`, `registration/attendees/loader`, `registration/attendees/success`, `registration/button-cart`, `registration/button-checkout`, `registration/cart-empty`, `registration/content`, `registration/summary/content`, `registration/summary/description`, `registration/summary/registration-status`, `registration/summary/ticket/content`, `registration/summary/icon-svg`, `registration/summary/ticket/icon`, `registration/summary/ticket/price`, `registration/summary/ticket/quantity`, `registration/summary/ticket/title`, `registration/summary/tickets-header`, `registration/summary/tickets`, `registration/summary/title`, `registration/summary/toggle-handler`
* Fix - Only show attendee registration for RSVP if going [121026]
* Fix - Fix broken ticket block sagas to allow syncing with event times [120736]
* Fix - Only allow attendee move functionality in admin [87145]
* Fix - Add filter to allow plugins to bypass the checkin caps requirement (for community events) [118675]
* Fix - Filter the_excerpt to prevent loading it on the CE attendee registration page [119777]
* Fix - Allow users to always access attendee registration page if tickets in cart have meta [121819]
* Fix - Allow tickets with required and non-required meta to be saved together [121821]
* Fix - Fix issue with WooCommerce checkout returning to attendee registration page [120735]
* Fix - Update available tickets when updating capacity [120280]
* Fix - Do not show print, email, and export buttons if not in admin [120646]
* Fix - Add Indian Rupee and Russian Ruple to PayPal currency code list [120554]
* Fix - Fix RSVP "don't show my information" checkbox in Twenty Nineteen theme [120685]
* Fix - Add ticket id to option id for attendee registration fields [122035]
* Fix - Path to override attendee registration templates defined in template files [120196]
* Fix - Fix path to override attendee registration templates [120037]
* Fix - Fix text domain in attendee field view [121019]
* Fix - Fix focus for sales duration in ticket block [122441]
* Fix - Fix mobile styles for tickets and RSVP [118299]
* Fix - Prevent notices when adding a new event in Community Events with Community Tickets active [116724]
* Fix - Modify how the status manager initializes to use class names instead of proper names, which might be translated [123056]
* Fix - Prevent fatal errors on front end ticket page if the provider is deactivated [122322]
* Language - 7 new strings added, 140 updated, 1 fuzzied, and 2 obsoleted

### [4.10.0.1] 2019-02-07

* Fix - Modify extension dependency checking with new system to determine if it can load [122368]

### [4.10] 2019-02-05

* Feature - Add check and enforce PHP 5.6 as the minimum version [116283]
* Feature - Add system to check plugin versions to inform you to update and prevent site breaking errors [116841]
* Tweak - Add tooltips and additional information to Tribe Commerce Orders page header [116747]
* Tweak - Update plugin header [90398]
* Tweak - Add tooltip info that clarifies two settings only apply to classic editor [20963]
* Tweak - Added filters: `event_tickets_attendees_{$provider_slug}_checkin_stati`, `tribe_tickets_supported_system_version`, `tribe_not_php_version_names`
* Tweak - Removed filters: `event_tickets_attendees_{$provider}_checkin_stati`
* Tweak - Removed actions: `tribe_tickets_plugin_failed_to_load`
* Fix - Prevent errors in PHP 7.2+ with ticket management [119608]
* Fix - Only allow delete or move attendee if user can manage attendee [103974]
* Fix - Prevent redirection of attendee registration to homepage when home is set to main events page [119680]
* Deprecated - constants MIN_TEC_VERSION and MIN_COMMON_VERSION in Tribe__Tickets__Main, use $min_tec_version property and no replacement for MIN_COMMON_VERSION
* Deprecated - The `et_plus_compatibility_notice()` method has been deprecated in `Tribe__Tickets__Main` in favor of Plugin Dependency Checking system
* Language - 15 new strings added, 136 updated, 0 fuzzied, and 40 obsoleted

### [4.9.4] 2019-01-15

* Feature - Added prompt in attendee registration when clicking checkout with unsaved attendee info [119760]
* Feature - Improve the UX for RSVPs with required login [119946]
* Tweak - Fix header image message for RSVP and Tickets blocks [119759]
* Tweak - Update attendee registration user experience [119465]
* Tweak - Adjust behavior of removing Tickets block to be more intuitive [119662]
* Tweak - Adjust behavior of removing RSVP block to be more intuitive [119663]
* Tweak - Added actions: `event_tickets_rsvp_after_ticket_row`
* Tweak - Changed views: `blocks/attendees`, `blocks/attendees/description`, `blocks/attendees/gravatar`, `blocks/attendees/order-links`, `blocks/attendees/title`, `blocks/rsvp`, `blocks/rsvp/content-inactive`, `blocks/rsvp/content`, `blocks/rsvp/details`, `blocks/rsvp/details/availability`, `blocks/rsvp/details/description`, `blocks/rsvp/details/title`, `blocks/rsvp/form`, `blocks/rsvp/form/attendee-meta`, `blocks/rsvp/form/details`, `blocks/rsvp/form/email`, `blocks/rsvp/form/error`, `blocks/rsvp/form/form`, `blocks/rsvp/form/name`, `blocks/rsvp/form/opt-out`, `blocks/rsvp/form/quantity-input`, `blocks/rsvp/form/quantity-minus`, `blocks/rsvp/form/quantity-plus`, `blocks/rsvp/form/quantity`, `blocks/rsvp/form/submit-button`, `blocks/rsvp/form/submit-login`, `blocks/rsvp/form/submit`, `blocks/rsvp/icon-svg`, `blocks/rsvp/icon`, `blocks/rsvp/loader-svg`, `blocks/rsvp/loader`, `blocks/rsvp/messages/success`, `blocks/rsvp/status`, `blocks/rsvp/status/full`, `blocks/rsvp/status/going-icon`, `blocks/rsvp/status/going`, `blocks/rsvp/status/not-going-icon`, `blocks/rsvp/status/not-going`, `blocks/tickets`, `blocks/tickets/commerce/fields-edd`, `blocks/tickets/commerce/fields-tpp`, `blocks/tickets/commerce/fields-woo`, `blocks/tickets/commerce/fields`, `blocks/tickets/content-description`, `blocks/tickets/content-inactive`, `blocks/tickets/content-title`, `blocks/tickets/content`, `blocks/tickets/extra-available-quantity`, `blocks/tickets/extra-available-unlimited`, `blocks/tickets/extra-available`, `blocks/tickets/extra-price`, `blocks/tickets/extra`, `blocks/tickets/icon-svg`, `blocks/tickets/icon`, `blocks/tickets/item-inactive`, `blocks/tickets/item`, `blocks/tickets/quantity-add`, `blocks/tickets/quantity-number`, `blocks/tickets/quantity-remove`, `blocks/tickets/quantity-unavailable`, `blocks/tickets/quantity`, `blocks/tickets/registration/attendee/content`, `blocks/tickets/registration/attendee/fields`, `blocks/tickets/registration/attendee/fields/checkbox`, `blocks/tickets/registration/attendee/fields/radio`, `blocks/tickets/registration/attendee/fields/select`, `blocks/tickets/registration/attendee/fields/text`, `blocks/tickets/registration/content`, `blocks/tickets/registration/summary/content`, `blocks/tickets/registration/summary/description`, `blocks/tickets/registration/summary/ticket-icon`, `blocks/tickets/registration/summary/ticket-price`, `blocks/tickets/registration/summary/ticket-quantity`, `blocks/tickets/registration/summary/ticket-title`, `blocks/tickets/registration/summary/ticket`, `blocks/tickets/registration/summary/tickets`, `blocks/tickets/registration/summary/title`, `blocks/tickets/submit-button`, `blocks/tickets/submit-login`, `blocks/tickets/submit`, `registration/button-checkout`
* Fix - Make RSVP block duration tooltip hover area larger [120063]
* Fix - Fix RSVP block FE attendee display option to not be bold [120064]
* Fix - Set RSVP block submit button hover color [120065]
* Fix - Don't enqueue wp admin common styles on the front end [119755]
* Fix - ensure that the RSVP login link redirects the user back to the event page post-login [120365]
* Fix - Fix datepicker UI and input not showing the same date [119666]
* Fix - Hide unavailable RSVPs in the FE [119597]
* Fix - Clear shared capacity from tickets block when removing tickets block [118334]
* Fix - Fix svg for RSVP "going" button [116206]
* Fix - Display ticket price as 0 if price is blank in ticket block [119410]
* Fix - Remove new ticket block if cancel button is clicked [119435]
* Fix - Move apostrophe position in RSVP block [119409]
* Fix - Move attendee registration position in RSVP block [119464]
* Fix - Fix PHP notice on attendee registration page [119680]
* Fix - Hide unavailable tickets in Tickets block when Tickets block is not selected [119630]
* Fix - Fix attendee registration for RSVP block FE [119800]
* Fix - Ensure that the attendee page loads if the theme has no page.php/page templates defined [120034]
* Language - 8 new strings added, 52 updated, 0 fuzzied, and 26 obsoleted

### [4.9.3] 2018-12-19

* Fix - Only show "Log in before purchasing" when login is required for Tribe Commerce tickets [118977]
* Fix - Set custom date format for date pickers used on tickets [119356]
* Fix - Display only tickets that are in date range [119628]
* Fix - RSVP now stays in sync with the Events and saving properly the Sale dates [118337]
* Fix - Remove the old `events-gutenberg` domain into the templates [119270]
* Fix - Prevent RSVP from showing on Tickets and vice versa [119726]
* Fix - Tickets will no longer get saved as RSVPs via Block Editor [119726]
* Tweak - Link to the correct support places [117795]
* Tweak - Added filter: `tribe_tickets_show_login_before_purchasing_link` [118977]
* Language - 23 new strings added, 11 updated, 0 fuzzied, and 20 obsoleted

### [4.9.2] 2018-12-13

* Feature - Added new action `tribe_tickets_update_blocks_from_classic_editor` to allow for custom actions when updating the tickets blocks
* Feature - Allowed admin to re-order tickets in the Tickets block [113737]
* Feature - Added ecommerce links to Tickets block [117227]
* Feature - Improved Cancel button UX in Tickets and RSVP blocks [119053]
* Feature - Added option to show or hide the Attendee List block header and subtitle [117040 & 117041]
* Tweak - Set the availability date pickers in Tickets and RSVP blocks to obey the Datepicker Display Format setting [117446]
* Fix - Corrected an issue where feature detection of async-process support would fire too many requests [118876]
* Fix - Updated blocks when moving from classic to blocks editor and vice versa [119426]
* Fix - Removed dotted border for the RSVP block when viewed on mobile devices [118952]
* Fix - Made sure all block editor code for Meta saving is PHP 5.2 compatible
* Fix - Restored Shared Capacity functionality for ET+ users in the Tickets block [118923]
* Fix - Set Tickets block currency symbol from the ecommerce provider settings [115649]
* Fix - Set the Attendee registration field editor to opens in the same tab from block editor [117854]

### [4.9.1] 2018-12-05

* Fix - Event Tickets Plus updates correctly when we are handling an outdated version

### [4.9.0.2] 2018-11-30

* Fix - Fixed an issue where the checking of the Event Tickets Plus version number would fail, and incorrectly deactivate Event Tickets Plus [119100]

### [4.9.0.1] 2018-11-30

* Fix - Adjustments for better compatibility with earlier PHP versions (our thanks to @megabit81 for highlighting this problem) [119073]
* Fix - Update common library to ensure better compatibility with addons running inside multisite networks [119044]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted

### [4.9] 2018-11-29

* Feature - added new Tickets block for adding, managing, and displaying tickets
* Feature - added new RSVP block for adding and displaying an RSVP (independently from tickets listings)
* Feature - added new Attendee List block for displaying event attendees (replaces attendee list setting)
* Tweak - limited RSVP block to a single RSVP except in cases of legacy configurations
* Tweak - admin can now disable Not Going responses for RSVPs via the RSVP block
* Fix - Adjust some permissions checks to ensure that RSVPs can be created by Subscriber-level users via the Community Tickets submission form [118598]
* Tweak - Added filters: `tribe_tickets_attendee_registration_checkout_url`, `tribe_tickets_rewrite_base_slugs`, `tribe_tickets_rewrite_i18n_domains`, `tribe_tickets_rewrite_i18n_slugs_raw`, `tribe_tickets_rewrite_i18n_slugs`, `tribe_tickets_attendee_registration_page_title`, `tribe_tickets_tickets_in_cart`, `tribe_tickets_attendee_registration_has_required_meta`, `tribe_tickets_attendee_registration_is_meta_up_to_date`, `tribe_tickets_commerce_paypal_gateway_add_to_cart_redirect`, `tribe_tickets_rsvp_send_mail`, `tribe_tickets_event_attendees_skip_empty_post`, `tribe_tickets_tickets_in_cart`, `tribe_tickets_rsvp_form_email`, `tribe_tickets_rsvp_form_full_name`, `tribe_tickets_plus_hide_attendees_list_optout`
* Tweak - Added actions: `tribe_tickets_pre_rewrite`, `tribe_tickets_commerce_paypal_gateway_pre_add_to_cart`, `tribe_tickets_rsvp_before_order_processing`, `event_tickets_rsvp_tickets_generated`, `tribe_tickets_ticket_deleted`, `tribe_tickets_ticket_added`, `tribe_tickets_tickets_hook`
* Tweak - Changed views: `blocks/attendees`, `blocks/attendees/description`, `blocks/attendees/gravatar`, `blocks/attendees/order-links`, `blocks/attendees/title`, `blocks/rsvp`, `blocks/rsvp/content`, `blocks/rsvp/details`, `blocks/rsvp/details/availability`, `blocks/rsvp/details/description`, `blocks/rsvp/details/title`, `blocks/rsvp/form`, `blocks/rsvp/form/email`, `blocks/rsvp/form/error`, `blocks/rsvp/form/form`, `blocks/rsvp/form/name`, `blocks/rsvp/form/opt-out`, `blocks/rsvp/form/quantity-input`, `blocks/rsvp/form/quantity-minus`, `blocks/rsvp/form/quantity-plus`, `blocks/rsvp/form/quantity`, `blocks/rsvp/form/submit-button`, `blocks/rsvp/form/submit-login`, `blocks/rsvp/form/submit`, `blocks/rsvp/icon-svg`, `blocks/rsvp/icon`, `blocks/rsvp/loader-svg`, `blocks/rsvp/loader`, `blocks/rsvp/messages/success`, `blocks/rsvp/status`, `blocks/rsvp/status/full`, `blocks/rsvp/status/going-icon`, `blocks/rsvp/status/going`, `blocks/rsvp/status/not-going-icon`, `blocks/rsvp/status/not-going`, `blocks/tickets`, `blocks/tickets/commerce/fields-edd`, `blocks/tickets/commerce/fields-tpp`, `blocks/tickets/commerce/fields-woo`, `blocks/tickets/commerce/fields`, `blocks/tickets/content-description`, `blocks/tickets/content-title`, `blocks/tickets/content`, `blocks/tickets/extra-available-quantity`, `blocks/tickets/extra-available-unlimited`, `blocks/tickets/extra-available`, `blocks/tickets/extra-price`, `blocks/tickets/extra`, `blocks/tickets/icon-svg`, `blocks/tickets/icon`, `blocks/tickets/item`, `blocks/tickets/quantity-add`, `blocks/tickets/quantity-number`, `blocks/tickets/quantity-remove`, `blocks/tickets/quantity-unavailable`, `blocks/tickets/quantity`, `blocks/tickets/registration/attendee/content`, `blocks/tickets/registration/attendee/fields`, `blocks/tickets/registration/attendee/fields/checkbox`, `blocks/tickets/registration/attendee/fields/radio`, `blocks/tickets/registration/attendee/fields/select`, `blocks/tickets/registration/attendee/fields/text`, `blocks/tickets/registration/content`, `blocks/tickets/registration/summary/content`, `blocks/tickets/registration/summary/description`, `blocks/tickets/registration/summary/ticket-icon`, `blocks/tickets/registration/summary/ticket-price`, `blocks/tickets/registration/summary/ticket-quantity`, `blocks/tickets/registration/summary/ticket-title`, `blocks/tickets/registration/summary/ticket`, `blocks/tickets/registration/summary/tickets`, `blocks/tickets/registration/summary/title`, `blocks/tickets/submit-button`, `blocks/tickets/submit-login`, `blocks/tickets/submit`, `registration/attendees/content`, `registration/attendees/error`, `registration/attendees/fields`, `registration/attendees/fields/checkbox`, `registration/attendees/fields/radio`, `registration/attendees/fields/select`, `registration/attendees/fields/text`, `registration/button-cart`, `registration/button-checkout`, `registration/cart-empty`, `registration/content`, `registration/summary/content`, `registration/summary/description`, `registration/summary/icon-svg`, `registration/summary/registration-status`, `registration/summary/ticket/content`, `registration/summary/ticket/icon`, `registration/summary/ticket/price`, `registration/summary/ticket/quantity`, `registration/summary/ticket/title`, `registration/summary/tickets-header`, `registration/summary/tickets`, `registration/summary/title`, `registration/summary/toggle-handler`, `tickets/rsvp`
* Language - 35 new strings added, 121 updated, 0 fuzzied, and 0 obsoleted

### [4.8.4.1] 2018-11-21

* Fix - Resolved a compatibility issue with WordPress version 4.7.11 and earlier (our thanks to @placer69 and @earthnutvt for flagging this) [118627]

### [4.8.4] 2018-11-13

* Fix - Registration form from hiding with multiple RSVP tickets and one of them being set to quantity zero, thanks pixelbrad for reporting [116139]
* Fix - Remaining ticket quantity counter for tickets with shared capacity and capped sales, thanks for the report mirre1 and pixelbrad [104356]
* Tweak - Adjust tooltips next to the ticket end-sale dates for improved clarity based on post type [116853]

### [4.8.3] 2018-10-22

* Fix - Ensure ticket start sale and end sale datepicker respects the WordPress Week Starts On Setting, thanks websource! [109729]
* Tweak - Ensure the ticket currency and position returned by the REST API is based on the ticket provider [116352]
* Language - 12 strings updated, 0 added, fuzzied, or obsoleted

### [4.8.2.1] 2018-10-10

* Fix - Prevent fataling when upgrading Event Tickets while running versions of Event Tickets Plus lower than 4.8 [115510]
* Language - 188 new strings added, 125 updated, 6 fuzzied, and 10 obsoleted

### [4.8.2] 2018-10-03

* Fix - Ensure that ticket forms and related info are not visible on password-protected events, posts, and other post types [102643]
* Fix - Prevent notice when editing ticket with global capacity [104169]
* Fix - Fixed a number of locations in Tribe Commerce-powered admin views where prices were reported without their full decimal values [112217]
* Fix - Display RSVP/Tickets unavailability message on the position selected over the Settings. Thanks @liblogger for flagging this! [113161]
* Fix - Remove the "Not Going" RSVPs from the attendee count on the events list. Props to @mirre1 for flagging this! [111104]
* Fix - Ensured that the TribeCommerce ticket start and end sale date respect the event timezone. Thanks Ryan and Georges for flagging this! [109510]
* Fix - Fixed datepicker format related problems when using Event Tickets as standalone [111817]
* Tweak - Ensure the attendees cache is cleared upon checking in an attendee or undoing a checkin (thanks to @hadamlenz on GitHub for submitting this change!) [113661]
* Tweak - Fix some internal documentation of shortcode templates to ensure filenames are accurate [112360]
* Tweak - Prevent RSVP form from submitting when the quantity is 0 or if blank [113989]

### [4.8.1] 2018-09-12

* Fix - Show the ticket table when tickets are all sold out, show message in list view [111893]
* Fix - Fixed ticket description issue when creating events with no description. Thanks Aaron Brazell for the fix! [113038]
* Fix - Correct un-responsive ticket table, thanks @understandphoto for bringing this to our attention! [109730]
* Tweak - Added filter: `tribe_tickets_get_ticket_max_purchase` [112478]

### [4.8] 2018-08-22

* Feature - Include a Tickets REST API endpoint for read operations [108021]
* Fix - Fixed datepicker formats YYYY.MM.DD, MM.DD.YYYY and DD.MM.YYYY validation error on ticket start sale date. Thanks @dmitry-zhuk, Albert and others for reporting this issue! [102815]
* Fix - Active tab logic for Attendees in Tribe Commerce, thanks Luc [107897]
* Fix - Fixed default currency symbol inconsistency. Thanks Uwe and Zé for pointing this out! [104093]
* Tweak - Tribe Commerce Orders Sales by Ticket section to remove duplicate data [110034]
* Tweak - Attendees section to clarify information [110038]

### [4.7.6] 2018-08-01

* Fix - Fixed the "Show description" setting for Tribe Commerce tickets in the backend and frontend [100524]
* Fix - Added required post ID parameter to `the_title` filter in Tribe Commerce [109592]
* Fix - Stop showing tickets for past events with no end sale date. Thanks to @thesinglegourmet for flagging this! [107121]
* Fix - Stop showing posts with "pending review" status in the blog page. Thanks Jansen, Antonio and others for reporting this issue! [102184]
* Tweak - Added start sale date to ticket unavailability message with filters to disable or include the time [82684]
* Tweak - Added parent post and order IDs as parameters to the Tribe Commerce email filters [104209]
* Tweak - Made the attendees list html title translatable. Thanks @websource for pointing this out [109595]
* Tweak - Added a new filter `tribe_tickets_email_ticket_image` for easier ticket image customization in the tickets email [79876]
* Tweak - Corrected the reference to the [tribe-tpp-success] shortcode within the Tribe Commerce settings area [111011]
* Feature - Include RSVP and Tribe Commerce tickets fields data in WP personal data eraser [108490]
* Feature - Include TribeCommerce orders data in WP personal data exporter [108487]

### [4.7.5.1] 2018-07-10

* Fix - Fatal error on some product pages when The Events Calendar is not active [110248]

### [4.7.5] 2018-07-09

* Fix - Display unavailability message when tickets are not yet or no longer available [81334]
* Fix - Issues with calculating and displaying ticketed events on the admin list [71122]
* Fix - Add Privacy Policy guide for Event Tickets [108456]
* Feature - Include RSVP and Tribe Commerce tickets fields data in WP personal data export [107156]

### [4.7.4.1] 2018-06-22

* Fix - Sending the ticket email when WooCommerce is active and purchasing another ticket type [109102]

### [4.7.4] 2018-06-20

* Fix - Properly calculate existing stock for RSVPs. Thanks to @afplct, @jacob, @dimaginet and others for flagging this! [102634]
* Fix - Properly update attendees transient when checkin/unchekin an attendee, in order to see changes immediately. Thanks to @newcollegeofflorida and @gschnoor for flagging this! [73272]
* Fix - Make sure the ticket creation is compatible with object cache. Thanks @zanart, @bethanymrac, @vividimage and others for flagging this! [105802]
* Fix - Display a notice if the user accesses the tickets page and doesn't have tickets [89201]
* Fix - If the ticket is a WooCommerce product and has a featured image, display it in the email [79877]
* Fix - Make sure the PayPal orders are being recorded. Thanks @burlingtonbytes for flagging this! [108436]
* Tweak - Added new action, `tribe_tickets_ticket_email_ticket_top`, to the tickets email template [79878]
* Tweak - Changed `tribe_tickets_email_include_event_date` filter default value to true. Now event date shows by default in RSVP ticket emails. Thanks @melvidge for the feedback [102309]
* Tweak - Replaced start date in the RSVP non-attendance email template with full event schedule details [87686]
* Tweak - Changed shortlinks to use https in Event Tickets welcome screen [75647]
* Language - 2 new strings added, 66 updated, 0 fuzzied, and 1 obsoleted

### [4.7.3.1] 2018-05-31

* Fix - Include new DataTables files in event tickets via tribe-common

### [4.7.3] 2018-05-29

* Fix - Display the correct number of attendees on the events list in the admin section (props to @vbt, @xen and others for flagging this!) [102128]
* Fix - Display the correct number of available tickets on list and day view (Thanks to @designfestbrum, @kaisv and others for reporting this problem!) [100340]
* Fix - Ensured that the ticket start and end sale date respect the event timezone (props to @Ryan, @Georges, @bcbookprizes for flagging this!) [76683]
* Fix - Add methods to handle refunds for tickets and fix the attendees report accordingly [102081]
* Fix - Modify the front end ticket list display so it always displays even when Hide From Event Listings is checked for an event, thanks to @atmedia for reporting [74523]
* Tweak - Better handle the admin display of ticket prices that are affected WooCommerce Membership discounts (thanks to @cardinalacres, @steamfablab, and others for reporting these issues!) [97583]
* Tweak - Added a "Return to Cart" link to PayPal tickets form [100253]
* Tweak - Changed validation on the option 'Post types that can have tickets' to allow empty value [105930]
* Language - 10 new strings added, 170 updated, 1 fuzzied, and 3 obsoleted

### [4.7.2] 2018-04-18

* Feature - Add new action, `tribe_tickets_before_front_end_ticket_form`, if RSVP has been expired and the tickets form is not rendered any more [98203]
* Feature - CSV imports for RSVP's now allow importing the "Show Description" setting; a new filter, `tribe_tickets_import_rsvp_data`, can set the data via code [96162]
* Fix - Prevent rendering of the RSVP form if Event Tickets is disabled for the tribe_events post type [66072]
* Fix - Ensure date-pickers honor the "Start of Week" option [75114]
* Fix - Ensure exported Attendee Reports have user info in the "Primary Information" column [70453]
* Fix - Corrected the datetime format used within our JSON-LD output so that it follows the ISO 8601 standard [43349]
* Fix - Make sure ticket countdown is updated when Tribe Commerce is not used [102497]
* Fix - Make sure the Attendees actions dropdown contains only actions the current user is allowed to perform [102498]
* Tweak - Added the `wp-background-processing` library by Ashley Rich (https://github.com/A5hleyRich/wp-background-processing) to `common` [102323]

### [4.7.1] 2018-03-28

* Feature - Added updater class to enable changes on future updates [84675]
* Feature - Added JSON-LD for custom post types with support for tickets and a new filter `tribe_tickets_default_currency` (thanks to Albert for flagging this in our forums) [95034]
* Fix - Added caching to prevent duplicate calls to `get_all_event_tickets` within the admin environment (props to Gabriel in our help desk for flagging this) [99267]
* Fix - Improved sanitization of the RSVP description field [99100]
* Fix - Updated logic for calculating the ticketed/unticketed event counts to better account for trashed events (props to @mrwweb for reporting this problem) [92236]
* Fix - Improved the ticket editor interface so that warnings in relation to recurring events stay visible [95098]
* Fix - Restored access to the attendee list from the organizer and venue post editor screens (when ticketing is enabled for those post types- our thanks to Antonio Jose in our forums for flagging this problem) [90062]
* Fix - Added safeguards to prevent RSVPs from being changed from "not going" to "going" if doing so would result in the ticket capacity being exceeded [100165]
* Fix - Added warning if a ticket has stock management turned off in the related WooCommerce product, but has capacity enabled for the ticket (thanks Isaiah Baker and others for highlighting this) [91471]
* Fix - Made sure the correct menu parent is expanded on the admin when visiting the list of attendees [93057]
* Fix - Changes for compatibility with Community Tickets (and to fix the ability to send the attendee report email, which was broken under some conditions) [99979]
* Fix - Added safeguards to prevent overwriting the start date of a ticket if it was already set [99601]
* Fix - Changes to ensure buy now buttons work with plain/"ugly" permalinks [96640]
* Fix - Updated the ticket start/end sale date logic to be timezone aware (props to @evolutionstartup for reporting this in our help desk) [99721]
* Fix - Fixes a glitch, where adding an RSVP results in "NaN" in the counter when using Event Tickets, Enfold and WooCommerce (thanks to @tbo24 for the contribution) [93027]
* Tweak - Changed Event tickets slug from 3 different types into 2 variants for post types and events types [88569]
* Tweak - Made it easier to set Tribe Commerce as the default ticket module (when multiple ticketing modules are active) [96538]
* Tweak - Unified upsell messages in the Ticket settings tab [100736]
* Tweak - Changed default status for 'Enable Tribe Commerce' option in tickets settings [102182]

### [4.7] 2018-03-13

* Feature - Included Tribe Commerce as a solution for selling tickets using PayPal
* Tweak - allow pagination and screen options on the Attendees list [64516]
* Tweak - Added filters: `tribe_tickets_search_attendees_by`, `tribe_commerce_currency_symbol`, `tribe_commerce_currency_symbol_position`, `tribe_tickets_commerce_price_format_use_currency_locale`, `tribe_tickets_commerce_currency_code_options`, `tribe_tickets_orders_tabbed_view_tab_map`, `tribe_tickets_should_use_ticket_in_sales_counts`, `tribe_tickets_get_total_sold`, `tribe_tickets_get_total_pending`, `tribe_tickets_get_total_paid`, `tribe_tickets_commerce_paypal_errors_map`, `tribe_tickets_commerce_paypal_notify_url`, `tribe_tickets_commerce_paypal_custom_args`, `tribe_tickets_commerce_paypal_add_to_cart_args`, `tribe_tickets_commerce_paypal_get_transaction_data`, `tribe_tickets_commerce_paypal_product_name`, `tribe_tickets_commerce_paypal_handler`, `tribe_tickets_commerce_paypal_validate_transaction`, `tribe_tickets_commerce_paypal_ipn_config_status`, `tribe_tickets_commerce_paypal_validate_transaction`, `tribe_tickets_commerce_paypal_is_active`, `tribe_tickets_register_ticket_post_type_args`, `tribe_tickets_register_attendee_post_type_args`, `tribe_tickets_register_order_post_type_args`, `tribe_tickets_tpp_send_mail`, `tribe_tickets_tpp_tickets_to_send`, `tribe_tpp_email_content`, `tribe_tpp_email_from_name`, `tribe_tpp_email_from_email`, `tribe_tpp_email_headers`, `tribe_tpp_email_attachments`, `tribe_tpp_email_recipient`, `tribe_tpp_email_subject`, `tribe_tickets_should_default_ticket_sku`, `tribe_tickets_tpp_get_ticket`, `tribe_tickets_order_data`, `tribe_tickets_paypal_report_url`, `tribe_tpp_submission_message`, `tribe_tickets_tpp_ticket_price_html`, `tribe_tickets_commerce_paypal_order_stati`, `tribe_tickets_tpp_metabox_capacity_file`, `tribe_tickets_tpp_enable_global_stock`, `tribe_tickets_tpp_pending_stock_ignore`, `tribe_tickets_tpp_pending_stock_reserve_time`, `tribe_tickets_attendee_data`, `tribe_tickets_tpp_order_postarr`, `tribe_tickets_tpp_order_line_total_statuses`, `tribe_tickets_commerce_paypal_attendee_revenue`, `tribe_tickets_commerce_paypal_revenue_generating_order_statuses`, `tribe_tickets_commerce_paypal_tickets_revenue`, `tribe_tickets_commerce_paypal_ticket_sales_count`, `tribe_tickets_commerce_paypal_tickets_sales`, `tribe_tickets_commerce_paypal_orders_table_column`, `tribe_tickets_commerce_paypal_search_orders_by`, `tribe_tickets_commerce_paypal_oversell_default_policy`, `tribe_tickets_commerce_paypal_oversell_policy`, `tribe_tickets_commerce_paypal_oversell_policies_map`, `tribe_tickets_commerce_paypal_oversell_generates_notice`, `tribe_tickets_commerce_paypal_oversell_policy_object`, `tribe_tickets_commerce_paypal_completed_transaction_statuses`, `tribe_tickets_commerce_paypal_revenue_generating_statuses`, `event_tickets_is_tpp_ticket_restricted`, `tribe_tickets_attendees_admin_expire`, `tribe_filter_attendee_order_link`, `tribe_events_tickets_module_name`, `tribe_tickets_current_user_can_delete_ticket`, `tribe_events_tickets_module_name`, `tribe_events_tickets_attendees_url`, `tribe_events_tickets_module_name`, `tribe_events_tickets_tpp_display_sku`, `tribe_tickets_stock_message_available_quantity`
* Tweak - Removed filters: `tribe_events_tickets_google_low_inventory_level`, `event_tickets_email_include_start_date`
* Tweak - Added actions: `tribe_tickets_orders_tabbed_view_register_tab_right`, `tribe_tickets_orders_tabbed_view_register_tab_left`, `tribe_tickets_tpp_before_order_processing`, `tribe_tickets_tpp_before_attendee_ticket_creation`, `event_tickets_tpp_attendee_created`, `event_tickets_tpp_attendee_updated`, `event_tickets_tpp_tickets_generated_for_product`, `event_tickets_tpp_tickets_generated_for_product`, `event_tickets_tpp_tickets_generated`, `tickets_tpp_ticket_deleted`, `tribe_events_tickets_metabox_edit_ajax_advanced`, `tribe_tickets_tpp_order_from_post`, `tribe_tickets_tpp_order_from_transaction`, `tribe_tickets_tpp_after_before_delete`, `tribe_tickets_tpp_after_after_delete`, `event_tickets_checkin`, `event_tickets_uncheckin`, `tribe_events_tickets_settings_content_before`, `event_tickets_ticket_list_after_ticket_name`, `tribe_events_tickets_metabox_pre`, `tribe_events_tickets_metabox_advanced`, `tribe_tickets_report_event_details_list_top`, `tribe_tickets_report_event_details_list_bottom`, `tribe_tickets_after_event_details_list`, `event_tickets_user_details_tpp`, `event_tickets_orders_attendee_contents`, `event_tickets_tpp_after_ticket_row`
* Tweak - Changed views: `login-before-purchase`, `login-to-purchase`, `tickets/email`, `tickets/orders-pp-tickets`, `tickets/orders`, `tickets/rsvp`, `tickets/tpp-success`, `tickets/tpp`
* Language - 172 new strings added, 179 updated, 6 fuzzied, and 2 obsoleted

### [4.6.3.1] 2018-02-26

* Fix - Remove PHP warnings during CSV generation of the attendees [94293]]

### [4.6.3] 2018-01-10

* Fix - Ensured that only users of the editor or administrator roles can delete, check-in, and undo check-ins on tickets (props to @skamath for reporting this!) [68831]
* Tweak - Addressed some issues where the ticket form would sometimes show up even when all tickets' end-sale dates had passed (props to @reckling and others for reporting this!) [94724]
* Tweak - Introduced the `tribe_tickets_caps_can_manage_attendees` filter for customizing what user capabilities are required for managing attendees [68831]

### [4.6.2] 2017-12-07

* Fix - Fixed broken RSVP ticket sales when using Aggregator CSV [92936]
* Fix - Prevent non-escaped underscores from getting into the final SQL for LIKE queries (Props to @misenhower) [GH#567]
* Fix - Fixed sorting for Tickets so that moving them to the first and last position of the order is allowed [92558]
* Fix - Improved handling of Stock to ensure it's updated accordingly based on total sales when updating capacity [93601]
* Fix - Improved CSS for Capacity Table on mobile [90907]
* Fix - Fixed some bugs with attendees management that prevented check-in features from working in Community Tickets front-end views (props @musician4you and several other folks for highlighting this issue) [81629]
* Tweak - Introduced the `tribe_tickets_event_action_links_edit_url` filter for more granular control over "edit event" links in various views [93339]
* Tweak - Prevent EDD from being a provider for front-end Community Tickets, as only WooCommerce is allowed for that [91758]
* Tweak - Added actions: `event_tickets_ticket_list_before_ticket_name`, `event_tickets_ticket_list_after_ticket_name`
* Language - 0 new strings added, 54 updated, 0 fuzzied, and 0 obsoleted

### [4.6.1.1] 2017-11-24

* Fix - Fixed some issues to ensure Start and End Time for Ticket sales work correctly (props to Scott) [93439]
* Fix - Ensure attendee fields remain visible within the admin environment (compatibility fix for Event Tickets Plus) [94142]
* Language - 0 new strings added, 1 updated, 0 fuzzied, and 0 obsoleted

### [4.6.1] 2017-11-21

* Fix - RSVP and Tickets migration from pre-4.6 updates capacity corretly for all cases now (props to Uwe Matern) [93231]
* Fix - Ensure Attendees column for events displays the correct percentages [92287]
* Fix - Tickets Editor now has more support for Accessibility [80651]
* Tweak - Prevent unnecessary AJAX requests when using tickets editor [88642]
* Tweak - Removes weird clearing of fields when canceling or saving Tickets [88642]
* Tweak - Allow saving of Ticket Editor contents when Updating the Event [91760]
* Tweak - Included more hooks via the new Template class for the Ticket Editor: `tribe_template_file`, `tribe_template_before_include`, `tribe_template_after_include` and `tribe_template_html` [91760]
* Tweak - Only display admin links in Community Tickets if user is able to access the admin [79565]
* Tweak - spacing of message to logged in users to view attendees [92550]
* Tweak - Added filters: `tribe_ticket_filter_attendee_report_link`, `tribe_tickets_attendees_show_title`
* Tweak - Removed filters: `tribe_tickets_default_end_date`, `tribe_tickets_ajax_refresh_settings`, `tribe_tickets_can_update_ticket_price`, `tribe_tickets_disallow_update_ticket_price_message`, `tribe_events_tickets_metabox_edit_attendee`
* Tweak - Added actions: `tribe_tickets_save_post`
* Tweak - Removed actions: `tribe_events_tickets_pre_ticket_list`, `tribe_events_tickets_post_ticket_list`
* Tweak - Changed views: `tickets/email`, `tickets/orders-link`
* Language - 10 new strings added, 132 updated, 0 fuzzied, and 9 obsoleted

### [4.6] 2017-11-09

* New - Fully redesigned ticket editor interface
* New - Ticket description is now optional for frontend display
* New - Updated time pickers for start and end sale
* New - Improved clarity around ticket availability
* Tweak - Renamed "stock" to "capacity"
* Tweak - Added filters: `tribe_event_ticket_decimal_point`, `tribe_tickets_default_ticket_capacity_type`, `tribe_tickets_rsvp_send_mail`, `tribe_tickets_show_description`, `tribe_tickets_ajax_refresh_tables`, `tribe_tickets_ajax_refresh_settings`, `tribe_events_tickets_metabox_edit_attendee`, `tribe_tickets_get_default_module`, `tribe_tickets_total_event_capacity`
* Tweak - Removed filters: `tribe_events_tickets_attendees_url`
* Tweak - Added actions: `tribe_events_tickets_metabox_edit_advanced`, `tribe_events_tickets_ticket_table_add_tbody_column`, `tribe_events_save_tickets_settings`, `tribe_events_tickets_capacity`, `tribe_events_tickets_post_capacity`, `tribe_events_tickets_pre_ticket_list`, `tribe_events_tickets_post_ticket_list`, `tribe_events_tickets_new_ticket_buttons`, `tribe_events_tickets_new_ticket_warnings`, `tribe_events_tickets_after_new_ticket_panel`, `tribe_events_tickets_pre_edit`, `tribe_events_tickets_metabox_edit_main`, `tribe_events_tickets_metabox_edit_accordion_content`, `tribe_events_tickets_post_accordion`, `tribe_events_tickets_bottom`, `tribe_events_tickets_bottom_right`, `tribe_events_tickets_ticket_table_add_header_column`, `tribe_ticket_order_field`, `tribe_events_tickets_settings_content`, `tribe_events_tickets_metabox_edit_advanced`
* Tweak - Removed actions: `tribe_events_tickets_metabox_advanced`, `event_tickets_ticket_list_after_ticket_name`, `tribe_events_tickets_metabox_pre`
* Tweak - Changed views: `tickets/email`, `tickets/rsvp`
* Tweak - Changed minimum supported version of WordPress to 4.5
* Language - 57 new strings added, 152 updated, 4 fuzzied, and 26 obsoleted

### [4.5.7] 2017-10-18

* Tweak - Improved compatibility of the "Attendees Export" CSV with Excel and other programs by removing line breaks from multi-line fields in the CSV (props: @twodoplhins) [80563]
* Tweak - Improve contrast on labels for ticket settings [93919]

### [4.5.6] 2017-09-20

* Fix - Prevent occasional issue with email content-type not being reset after ticket emails were sent (props to @jappleton in the forums for reporting this!) [62976]
* Fix - Hide unused 'back' button when moving tickets to another post [80604]
* Fix - Prevent multiple instances of the 'View your RSVPs and Tickets' link from showing on single events (or other ticket-enabled post types - props to @svkg in the forums for reporting this) [87429]
* Fix - Clear attendee cache when a ticket gets moved to another post [80200]
* Fix - Open the exportable CSV file of attendees in a new tab to accommodate Google Chrome's strict handling of file and MIME types, preventing some console errors and notices in Chrome [70750]
* Fix - Added "View Tickets" link to Custom Post Types when appropriate (thank you @19ideas for helping identify this) [67570]
* Fix - Fix some layout issues with the "Email Attendees" modal in the Attendees list admin view, especially when viewed on phones or tablets (props to @event-control for reporting this!) [80975]
* Fix - Avoid notice-level errors when calling ticket stock functions in relation to events with unlimited stock (props to Lou Anne for highlighting this) [78685]
* Tweak - Documented filter for available Ticket Modules, and used its method instead more places [66421]
* Tweak - The `tribe_events_tickets_modules` filter has now been deprecated and should not be used

### [4.5.5] 2017-09-06

* Fix - Fixed issue where RSVP options would often fail to show up on custom post types (thanks to tvtap for reporting this issue!) [73052]
* Fix - Confirm RSVP button not showing when last ticket was out of stock [86616]
* Fix - Fixed issue where email address links were getting http:// prepended in the RSVP list (thank you to @petemorelli for reporting this!) [85556]
* Fix - Resolved issue where tribe_events_count_available_tickets() sometimes returned the wrong stock count (props to Florian for reporting this) [81967]
* Fix - Added check to see if log directory is readable before listing logs within it (thank you @rodrigochallengeday-org and @richmondmom for reporting this) [86091]
* Tweak - RSVP non attendance email filters names are now unique (thanks to solwebsolutions for reporting this!) [74412]
* Tweak - Include full event start and end date in Tickets Email (thank you @pagan11460 for the suggestion!) [73885]
* Tweak - Improve performance of the addition of the "Attendees" row action link in wp-admin list tables (props to pixeldesigns for reporting this!) [72126]
* Tweak - Changed views: `tickets/email.php`, `tickets/orders-link.php`, `tickets/orders-rsvp.php`, and `tickets/rsvp.php`
* Tweak - Added filters: `tribe_events_set_notice`, `tribe_rsvp_non_attendance_email_headers`, `tribe_rsvp_non_attendance_email_attachments`, `tribe_rsvp_non_attendance_email_recipient`, `tribe_rsvp_non_attendance_email_subject`, and `tribe_rsvp_non_attendance_email_content`
* Tweak - Added filters: `tribe_event_tickets_plus_email_meta_fields`
* Tweak - Added actions: `tribe_tickets_before_front_end_ticket_form`
* Language - 0 new strings added, 53 updated, 0 fuzzied, and 0 obsoleted

### [4.5.4] 2017-08-24

* Tweak - Removed WP API adjustments [85996]
* Compatibility - Minimum supported version of WordPress is now 4.5
* Language - 0 new strings added, 18 updated, 0 fuzzied, and 0 obsoleted [event-tickets]

### [4.5.3] 2017-07-26

* Fix - Improved get_ticket_counts() to account for tickets with global stock enabled  [82684]
* Fix - Improved tribe_events_count_available_tickets() to account for tickets with global stock enabled (thanks to Florian for reporting this) [81967]
* Fix - Fixed some PHP notices that would show up when buying EDD tickets [83277]

### [4.5.2] 2017-07-13

* Fix - Hide others users from attendee managers email options [77050]
* Tweak - In 'user event confirmations' shortcode, add shortcode name to shortcode_atts function call to give more customization options [66141]
* Tweak - Added a filter to modify the Primary Info column of the Attendees Table [69538]
* Tweak - Added a filter to hide the attendee optout option in the tickets form [46087]
* Tweak - Added new parameters to RSVP email filters [64172]

### [4.5.1] 2017-06-28

* Tweak - Spelling and related language fixes (with thanks to @garrett-eclipse) [77196]

### [4.5.0.2] 2017-06-22

* Fix - Prevent warnings on Strict mode for PHP 5.3 and for PHP 7

### [4.5.0.1] 2017-06-22

* Fix - Prevent fatals involving Commerce Classes and Tribe__Tickets__Tickets

### [4.5] 2017-06-22

* Feature - Show remaining ticket count, buy now or rsvp now buttons in list views of The Events Calendar [71092 & 71094]
* Feature - An API to get ticket, attendee, event, and order information from a post id for RSVP, EDD, and WooCommerce Tickets [74363]
* Fix - Resolved issue where the Meta Chunker attempted to inappropriately chunk meta for post post_types [80857]
* Fix - Resolve Thunderbird for Windows rendering of Tickets email
* Tweak - Added filters: `tribe_tickets_buy_button`
* Tweak - Changed views: `tickets/rsvp`, `tickets/email`
* Language - 5 new strings added, 65 updated, 0 fuzzied, and 0 obsoleted [event-tickets]
* Language - 0 new strings added, 0 updated, 1 fuzzied, and 0 obsoleted [tribe-common]

### [4.4.10] 2017-06-14

* Fix - Allow importing of RSVP tickets with sale dates, even when time is not specified [77608]

### [4.4.9] 2017-06-01

* Feature - Overlay over ticket fields when javascript is disabled to prevent ticket orders [63912]
* Performance - Allow ticket providers to defer expensive calculations until it's necessary to run them [79683]
* Tweak - Introduce a new hook making it easier to disable the additional "Attendees" column [79683]

### [4.4.8] 2017-05-17

* Fix - Remove undefined and unneeded template variable [77421]
* Fix - Location of the #buy-tickets anchor should respect the ticket form location (our thanks to Hans for reporting this) [77992]
* Tweak - Language and text changes [68432]
* Tweak - Now uses tribe_tickets_get_template_part() to load the email/tickets template for increased flexibility [69660]

### [4.4.7] 2017-05-04

* Fix - Fixed "Email attendees" modal window display on mobile devices [72558]

### [4.4.6] 2017-04-19

* Tweak - Some corrections on and tweaks of the welcome screen [75575]
* Tweak - Added filters for adjusting the register post type arguments

### [4.4.5] 2017-03-23

* Fix - Improve handling of unlimited ticket stock (props: @jtsternberg) [74123]
* Fix - A PHP error rendered the help tab broken [75544]

### [4.4.4] 2017-03-08

* Fix - Fixed a bug that caused an inconsistency with the check-in/undo check-in button (thanks to @joe for the report in the forums) [68414]
* Fix - Fixed a bug that displayed an error message even for successful check-ins via QR code [68416]

### [4.4.3] 2017-02-22

* Fix - Avoid using TEC functions if TEC isn't activated (thanks for reporting @Liesbet) [72499]
* Fix - Fixed bug where the ticket page link template filter on the_content was being executed on every post type regardless of whether the post type had tickets enabled (props to nichestudio on our forums) [70485]
* Fix - Ensure the Confirm RSVP button is always visible when ticket stock is available (thank you @Terry for the report in our forums) [73539]

### [4.4.2] 2017-02-09

* Tweak - Print styles for the attendees report are now more efficient (props @ajuliano) [72772]
* Fix - Email template file: link event title to event single page, add state/province and postal code to venue information, link venue address to Google Map link if the event's Show Google Maps Link option is checked [72475]
* Fix - Resolved an issue where attendees would always attempt to be fetched and set in transients - even when an un-expired transient held an empty attendee list. (props to nichestudio on our forums) [70485]

### [4.4.1] 2017-01-26

* Fix - Resolve the Fatals related to undefined methods and Memory exhaustion [71958, 71912]
* Fix - Use timezoned time for `tribe_events_ticket_is_on_sale()` [71959]
* Tweak - Improvements to the Front End UX Tickets RSVP Styles [72036]
* Fix - Prevent content from being cut off on check in screen on iphone, other tweaks to mobile views [70771]
* Fix - Prevent PHP 5.2 Strict mode from throwing notices due to usage of `is_a` [72812]

### [4.4.0.1] 2017-01-09

* Fix - Adds safety check to ensure a smooth activation process when earlier versions of Tribe Common are active

### [4.4] 2017-01-09

* Fix - Help page is now accessible even if The Events Calendar is not active on the site [69248]
* Tweak - Added a tabbed view support for Attendees and Ticket Orders [66015]
* Tweak - Added the "Attendees" report column in admin lists of posts supporting tickets [67176]
* Tweak - Improve the Attendee Report header with improved layout and better labels [66003]
* Tweak - Adjust the layout of the attendee report screen [66004, 65887]

### [4.3.5] 2016-12-20

* Tweak - Updated the template override instructions in a number of templates [68229]
* Tweak - Allow better filtering for Attendees (Props to @jtsternberg) [69886]
* Fix - Prevent JavaScript Notices related to Bumpdown [69886]
* Fix - Assets URL on Windows Servers are fully operational again [68377]
* Fix - JavaScript and CSS files will respect HTTPS on all pages [69561]

### [4.3.4.1] 2016-12-09

* Fix - Updates Tribe Common to remove some stray characters that were impacting page layouts (props: @Aetles) [70536]

### [4.3.4] 2016-12-08

* Tweak - Tribe Common now is loaded only once across our plugin suite, improves performance on some cases [65755]

### [4.3.3] 2016-11-16

* Feature - Added Tribe Extension class and loader, to make small addons easier to build [68188]
* Fix - Prevent HTTPS websites from pointing to Assets in HTTP [68372]

### [4.3.2] 2016-11-02

* Tweak - Include more Edited data on the `edit-ticket.tribe` action on JavaScript [68557]

### [4.3.1.1] 2016-10-20

* Fix - Corrected a packaging issue from the 4.3.1 release [67936]

### [4.3.1] 2016-10-20

* Tweak - Registered plugin as active with Tribe Common [66657]
* Fix - When searching in the attendees list the ticket meta details can still be toggled after search [61783]
* Fix - Fixed an issue where long file names would break plugin updates on some Windows installations [62552]

### [4.3] 2016-10-13

* Feature - Add ticket management facilities allowing reassignment to different posts [61724]
* Tweak - Changed "Event Add-Ons" to load faster [64286]
* Tweak - Reworked and reorganized the attendee list screen [61992]
* Tweak - Added tribe_tickets_rsvp_before_order_processing and tribe_tickets_rsvp_before_attendee_ticket_creation actions (props to @sabitertan on GitHub for this!) [65836]
* Fix - Cease using GLOB_BRACE for including deprecated files due to limited server support [63172]
* Fix - Made some untranslatable strings translatable (big thanks to @Petr from the support forums on this!) [62458]
* Deprecated - The `process_bulk_actions()` method has been deprecated in `Tribe__Tickets__Attendees_Table` in favor of `process_actions()` in the same class

### [4.2.7] 2016-09-15

* Fix - Stop logic for dealing with recurring events from impacting other post types (Originally reported by @Ryan on the support forums. Thanks!)
* Tweak - Share "tickets unavailable" messaging across ticketing providers to prevent unnecessary duplication
* Tweak - Additional support for plugin extensions

### [4.2.6] 2016-08-31

* Feature - Utilize new tribe_is_event_past() conditional to display better messaging when tickets are not available (Thank you to @Jonathan here for reporting this in the forums.)

### [4.2.5] 2016-08-17

* Fix - Garbled site title in RSVP confirmation email

### [4.2.4] 2016-08-03

* Tweak - Changed "Event Add-Ons" to load faster

### [4.2.3] 2016-07-20

* Tweak - Add prompt for ratings on admin Event screens
* Fix - Provide fallback page if App Shop API fails to load
* Fix - Events related links should appear under the Events menu (Thanks @Abby for the original report of this on our support forums)

### [4.2.2] 2016-07-06

* Fix - Send an email acknowledgement, rather than a set of tickets, when a user confirms they will not attend an event (RSVPs)
* Tweak - Add a period to the ticket header image setting
* Fix - Removed the ticket description in the admin area to allow for more room for tickets sold notes
* Fix - Displays the name of the ticket for each attendee on the order confirmation page
* Fix - Fixed issue where front-end attendees table check-in state would not show the correct value

### [4.2.1.1] 2016-06-28

* Fix - Ensure translations load as expected with all supported versions of WordPress (thanks to @JacobALund for originally reporting this on .org forums)

### [4.2.1] 2016-06-22

* Tweak - Create a readable ID on CSV and email exports when they're available
* Fix - Display all visible columns in attendees CSV and email export reports
* Fix - Correct how attendee meta meta is handled on all pages
* Fix - Remove notices in the attendees export by CSV and email when Community Tickets is activated

### [4.2] 2016-06-08

* Feature - Add a shortcode listing those upcoming events the user has indicated they will attend
* Feature - Make it possible to disable the ticket form for logged out users
* Feature - Added RSVP and WooCommerce tickets import via .csv file (Thank you Quakely for submitting this idea on UserVoice!)
* Feature - Authenticated Attendees can control their RSVP on Events
* Tweak - Language files in the `wp-content/languages/plugins` path will be loaded before attempting to load internal language files (Thank you to user @aafhhl for bringing this to our attention!)
* Tweak - Add messaging on the RSVP form when tickets are not yet or are no longer on sale (Props to @masteradhoc on GitHub for this change!)
* Tweak - Improved our JSON-LD output to include tickets (Big thanks to Lars for reporting this!)
* Tweak - Record the user ID associated with the creation of new attendee records
* Tweak - Fixed translation domain on a few strings (Thank you @TEME for reporting the issue!)
* Tweak - Move plugin CSS to PostCSS
* Tweak - Fix padding/spacing for RSVP form on single event page in Twenty Fifteen
* Tweak - Updated plugin description on admin plugin page
* Tweak - Move plugin CSS to PostCSS
* Fix - Target specific input fields when check for remaining tickets to help with theme compatibility issues such as Avada (Thanks to Michael C!)
* Fix - Corrects capabilities test in relation to editing tickets (Props to @bokorir!)
* Fix - Loads thickbox for email modal in attendees list

### [4.1.4] 2016-05-19

* Fix - Improve email attendees integration with Community Events Tickets
* Fix - Remove unneeded plugin settings when Event Tickets is operating without The Events Calendar
* Tweak - Setting to opt out of the frontend attendee list now automatically hidden if the attendee list is also hidden
* Tweak - Make the visibility of the delete ticket link filterable

### [4.1.3] 2016-04-28

* Tweak - Added back the purchaser name and email address to the attendee reports for all tickets. We had inadvertently removed those in a previous release. #facepalm [45082]
* Tweak - Fixed an error where tickets on custom post types would not save or display on the front end. That was annoying to anyone trying to create RSVPs or tickets on anything other than a default page or post.

### [4.1.2] 2016-04-11

* Fix - Removed a notice on attendees list when a non-existent email method is on a hook

### [4.1.1] 2016-03-30

* Tweak - Add filters for generated attendee and order data; tribe_tickets_attendee_data and tribe_tickets_order_data, respectively
* Tweak - Relocated the generation of tickets to the template_redirect action because init was too early for proper permalink fetching
* Fix - Resolved issue where purchasing tickets was impossible if ticket stock was set to unlimited (thanks to James for reporting this one)
* Fix - Fixed issue where the customer name and customer email address had been removed from the attendee export CSV (nice find here by Joe in our forums)

### [4.1] 2016-03-15

* Feature - Implemented global stock per event allowing multiple tickets to pull from the same pool of available tickets on an event (Heck yeah to all those who voted on this feature!)
* Feature - Added filters for RSVP ticket generation: event_tickets_rsvp_tickets_created, event_tickets_rsvp_tickets_generated_for_product, and event_tickets_rsvp_tickets_generated (props to 75ninteen for this pull request!)
* Tweak - Conditionally show attendees link on Event listing in the WordPress administration
* Tweak - Obfuscated license keys Events > Help > System Information
* Tweak - Allowed the "same slug" notice to be dismissed and fix some text in that message
* Fix - Fixed issue where some characters were not escaped appropriately for month and year formats
* Fix - Resolved issue where the RSVP confirmation error message displayed when it shouldn't
* Fix - Prevent notices to enqueue method when moving form hooks

### [4.0.5] 2016-02-17

* Feature - Add a loading graphic after clicking send email for the attendee's report

### [4.0.4] 2015-12-23

* Feature - Add support for global ticket stock so multiple tickets can optionally reduce from a single ticket total for a given event
* Tweak - Ignore alpha/beta/rc suffixes on version numbers when checking template versions
* Tweak - Add HTML id attribute to ticket area on the single-event page so plugin/theme authors can use anchor tags to jump to that section of the page
* Fix - Resolved issue with stock calculations on the Attendees report

### [4.0.3] 2015-12-22

* Tweak - Leverage the original_stock() method when rendering ticket availability to avoid funky math problems with different Event Tickets Plus commerce providers (Thank you liblogger for reporting this issue!)

### [4.0.2] 2015-12-16

* Tweak - Removing dates from ticket emails when those tickets are attached to non The Events Calendar event posts
* Fix - Fixed a settings page URL (Thanks for the tip Kristy!)

### [4.0.1] 2015-12-10

* Tweak - Removed The Events Calendar-specific fields from the Attendees Report as defaults. The Events Calendar will now hook into the report and inject event-specific fields
* Fix - Fixed issue where a retina-friendly loading gif was 404ing

### [4.0] 2015-12-02

* Feature - Initial release
