// Readiness and connection actions.
export const INBOUND_APP_READY = 'app_postmessage_ready';
export const INBOUND_APP_READY_FOR_DATA = 'app_postmessage_ready_for_data';
export const OUTBOUND_HOST_READY = 'host_postmessage_ready';
export const OUTBOUND_SEAT_TYPE_TICKETS = 'host_postmessage_seat_type_tickets';
export const INBOUND_SEATS_SELECTED = 'app_postmessage_seats_selected';
export const INBOUND_SET_ELEMENT_HEIGHT = 'app_postmessage_set_element_height';

// Map, layout and seat type edit actions.
export const MAP_CREATED_UPDATED = 'app_postmessage_map_created_updated';
export const LAYOUT_CREATED_UPDATED = 'app_postmessage_layout_created_updated';
export const SEAT_TYPE_CREATED_UPDATED = 'app_postmessage_seat_type_created_updated';
export const SEAT_TYPES_UPDATED = 'app_postmessage_seat_types_updated';
export const SEAT_TYPE_DELETED = "app_postmessage_seat_type_deleted";
export const RESERVATIONS_DELETED = 'app_postmessage_reservations_deleted';
export const RESERVATIONS_UPDATED = 'app_postmessage_reservations_updated';
export const RESERVATIONS_UPDATED_FOLLOWING_SEAT_TYPES =
	'app_postmessage_reservations_updated_following_seat_types';
export const GO_TO_ASSOCIATED_EVENTS = 'app_postmessage_goto_associated_events';
export const RESERVATION_UPDATED = 'app_postmessage_reservation_updated';
export const RESERVATION_CREATED = 'app_postmessage_reservation_created';

// Service-side redirection actions.
export const GO_TO_MAPS_HOME = 'app_postmessage_goto_maps_home';
export const GO_TO_LAYOUTS_HOME = 'app_postmessage_goto_layouts_home';

// Sessions actions.
export const OUTBOUND_REMOVE_RESERVATIONS =
	'host_postmessage_remove_reservations';

// Seats report action.
export const OUTBOUND_EVENT_ATTENDEES = 'host_postmessage_event_attendees';
export const OUTBOUND_ATTENDEE_UPDATE = 'host_postmessage_attendee_update';
