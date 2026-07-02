/**
 * Returns the most recently created RSVP ticket from a REST list response.
 *
 * @param {Array}  tickets    Ticket REST response objects.
 * @param {string} ticketType Expected ticket type (e.g. tc-rsvp).
 * @return {Object|null} Latest matching ticket or null.
 */
export const selectLatestRsvpTicket = ( tickets, ticketType ) => {
	const rsvpTickets = Array.isArray( tickets )
		? tickets.filter( ( ticket ) => ticket.type === ticketType )
		: [];

	if ( ! rsvpTickets.length ) {
		return null;
	}

	return rsvpTickets.reduce( ( latest, ticket ) => {
		if ( ! latest || ticket.id > latest.id ) {
			return ticket;
		}

		return latest;
	}, null );
};
