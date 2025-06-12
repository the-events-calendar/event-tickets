export interface TicketState {
	price: number;
	stock: number;
	startDate: string;
	endDate: string;
	isFree: boolean;
	quantity: number;
}

export interface StoreState {
	ticket: TicketState;
}

export interface StoreSelectors {
	getTicketPrice: () => number;
	getTicketStock: () => number;
	getTicketStartDate: () => string;
	getTicketEndDate: () => string;
	getTicketIsFree: () => boolean;
	getTicketQuantity: () => number;
}

export interface StoreActions {
	// Add actions here when needed
} 