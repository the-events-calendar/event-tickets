type SelectedTicket = {
	ticket_id: number;
	quantity: number;
	optout: boolean;
};

function createSelectedTicket(
	ticketId: number,
	quantity: number,
	optout: boolean
): SelectedTicket {
	return {
		ticket_id: ticketId,
		quantity,
		optout,
	};
}
