/**
 * V2 RSVP Configuration
 *
 * Retrieves V2 RSVP configuration from the localized editor config.
 * Uses the TEC REST V1 tickets endpoint for API operations.
 */

/**
 * Default configuration values.
 */
const DEFAULT_CONFIG = {
	enabled: false,
	ticketsEndpoint: '/tec/v1/tickets',
	ticketType: 'tc-rsvp',
};

/**
 * Get the V2 RSVP configuration from the localized window object.
 *
 * @return {Object} The V2 RSVP configuration.
 */
export const getV2Config = () => {
	const config = window.tribe_editor_config?.tickets?.rsvpV2 || {};

	return {
		enabled: Boolean( config.enabled ),
		ticketsEndpoint: config.ticketsEndpoint || DEFAULT_CONFIG.ticketsEndpoint,
		ticketType: config.ticketType || DEFAULT_CONFIG.ticketType,
	};
};

/**
 * Check if V2 RSVP is enabled.
 *
 * @return {boolean} Whether V2 RSVP is enabled.
 */
export const isV2Enabled = () => getV2Config().enabled;

/**
 * Get the TEC REST V1 tickets endpoint path.
 *
 * @return {string} The tickets endpoint path (e.g., '/tec/v1/tickets').
 */
export const getTicketsEndpoint = () => getV2Config().ticketsEndpoint;

/**
 * Get the V2 ticket type constant.
 *
 * @return {string} The V2 ticket type ('tc-rsvp').
 */
export const getTicketType = () => getV2Config().ticketType;

export default {
	getV2Config,
	isV2Enabled,
	getTicketsEndpoint,
	getTicketType,
};
