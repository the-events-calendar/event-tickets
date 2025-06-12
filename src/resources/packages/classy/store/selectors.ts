import { createSelector } from '@wordpress/data';
import { STORE_NAME } from './constants';
import { StoreState, StoreSelectors } from '../types/store';

export const getTicketPrice = createSelector(
	(state: StoreState) => [state.ticket?.price],
	(state: StoreState) => state.ticket?.price || 0
);

export const getTicketStock = createSelector(
	(state: StoreState) => [state.ticket?.stock],
	(state: StoreState) => state.ticket?.stock || 0
);

export const getTicketStartDate = createSelector(
	(state: StoreState) => [state.ticket?.startDate],
	(state: StoreState) => state.ticket?.startDate || ''
);

export const getTicketEndDate = createSelector(
	(state: StoreState) => [state.ticket?.endDate],
	(state: StoreState) => state.ticket?.endDate || ''
);

export const getTicketIsFree = createSelector(
	(state: StoreState) => [state.ticket?.isFree],
	(state: StoreState) => state.ticket?.isFree || false
);

export const getTicketQuantity = createSelector(
	(state: StoreState) => [state.ticket?.quantity],
	(state: StoreState) => state.ticket?.quantity || 0
); 