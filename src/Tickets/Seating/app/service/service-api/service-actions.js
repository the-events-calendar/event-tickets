// Readiness and connection actions.
export const INBOUND_APP_READY = 'app_postmessage_ready';
export const INBOUND_APP_READY_FOR_DATA = 'app_postmessage_ready_for_data';
export const OUTBOUND_HOST_READY = 'host_postmessage_ready';
export const OUTBOUND_SEAT_TYPE_TICKETS = 'host_postmessage_seat_type_tickets';
export const INBOUND_SEATS_SELECTED = 'app_postmessage_seats_selected';

// Map, layout and seat type edit actions.
export const MAP_CREATED_UPDATED = 'app_postmessage_map_created_updated';
export const LAYOUT_CREATED_UPDATED = 'app_postmessage_layout_created_updated';
export const SEAT_TYPE_CREATED_UPDATED =
	'app_postmessage_seat_type_created_updated';

// Service-side redirection actions.
export const GO_TO_MAPS_HOME = 'app_postmessage_goto_maps_home';
export const GO_TO_LAYOUTS_HOME = 'app_postmessage_goto_layouts_home';

// Reservations actions.
export const OUTBOUND_REMOVE_RESERVATIONS =
	'host_postmessage_remove_reservations';
