export type StoreSelectors = {
	getTicketPrice: () => number;
	getTicketStock: () => number;
	getTicketStartDate: () => string;
	getTicketEndDate: () => string;
	getTicketIsFree: () => boolean;
	getTicketQuantity: () => number;
}
