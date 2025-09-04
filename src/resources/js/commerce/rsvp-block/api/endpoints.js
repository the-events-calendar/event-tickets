/**
 * API endpoints for RSVP block
 *
 * @since TBD
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Create a new RSVP ticket
 *
 * @since TBD
 *
 * @param {Object} data The RSVP data to create.
 *
 * @return {Promise} The API response promise.
 */
export const createRSVP = async ( data ) => {
	const response = await apiFetch( {
		path: '/tribe/tickets/v1/commerce/ticket',
		method: 'POST',
		data: {
			post_ID: data.postId,
			rsvp_limit: data.limit === '' ? -1 : data.limit,
			ticket_type: 'tc-rsvp',
			ticket_provider: 'TEC\\Tickets\\Commerce\\Module',
			rsvp_start_date: data.openRsvpDate || '',
			rsvp_start_time: data.openRsvpTime || '00:00:00',
			rsvp_end_date: data.closeRsvpDate || '',
			rsvp_end_time: data.closeRsvpTime || '23:59:59',
			tec_tickets_rsvp_enable_cannot_go: data.showNotGoingOption ? '1' : '',
			...data.additionalData,
		},
	} );

	if ( ! response.success ) {
		throw new Error( response.message || 'Failed to create RSVP' );
	}

	return response;
};

/**
 * Update an existing RSVP ticket
 *
 * @since TBD
 *
 * @param {Object} data The RSVP data to update.
 *
 * @return {Promise} The API response promise.
 */
export const updateRSVP = async ( data ) => {
	const response = await apiFetch( {
		path: '/tribe/tickets/v1/commerce/ticket',
		method: 'POST',
		data: {
			post_ID: data.postId,
			rsvp_id: data.rsvpId,
			rsvp_limit: data.limit === '' ? -1 : data.limit,
			ticket_type: 'tc-rsvp',
			ticket_provider: 'TEC\\Tickets\\Commerce\\Module',
			rsvp_start_date: data.openRsvpDate || '',
			rsvp_start_time: data.openRsvpTime || '00:00:00',
			rsvp_end_date: data.closeRsvpDate || '',
			rsvp_end_time: data.closeRsvpTime || '23:59:59',
			tec_tickets_rsvp_enable_cannot_go: data.showNotGoingOption ? '1' : '',
			...data.additionalData,
		},
	} );

	if ( ! response.success ) {
		throw new Error( response.message || 'Failed to update RSVP' );
	}

	return response;
};

/**
 * Delete an RSVP ticket
 *
 * @since TBD
 *
 * @param {Object} data The RSVP data containing ID to delete.
 *
 * @return {Promise} The API response promise.
 */
export const deleteRSVP = async ( data ) => {
	const response = await apiFetch( {
		path: '/tribe/tickets/v1/commerce/ticket',
		method: 'DELETE',
		data: {
			post_ID: data.postId,
			rsvp_id: data.rsvpId,
		},
	} );

	if ( ! response.success ) {
		throw new Error( response.message || 'Failed to delete RSVP' );
	}

	return response;
};

/**
 * Fetch RSVP data
 *
 * @since TBD
 *
 * @param {string} rsvpId The RSVP ID to fetch.
 *
 * @return {Promise} The API response promise.
 */
export const fetchRSVP = async ( rsvpId ) => {
	if ( ! rsvpId ) {
		return null;
	}

	const response = await apiFetch( {
		path: `/tribe/tickets/v1/tickets/${rsvpId}`,
		method: 'GET',
	} );

	return response;
};