import { useEffect } from 'react';
import { useSelect, useDispatch } from '@wordpress/data';
import { registerStore } from '@wordpress/data';
import { STORE_NAME } from '../store/constants';
import { storeConfig } from '../store';

// Register the store if it hasn't been registered yet
if ( !window.wp?.data?.select( STORE_NAME ) ) {
	registerStore( STORE_NAME, storeConfig );
}

export const useTicketsStore = () => {
	const dispatch = useDispatch();

	// Selectors
	const tickets = useSelect( ( select ) => {
		const store = select( STORE_NAME );
		return store ? store.getTicketsForPost( store.getState() ) : [];
	}, [] );

	const isLoading = useSelect( ( select ) => {
		const store = select( STORE_NAME );
		return store ? store.getIsLoading( store.getState() ) : false;
	}, [] );

	const error = useSelect( ( select ) => {
		const store = select( STORE_NAME );
		return store ? store.getError( store.getState() ) : null;
	}, [] );

	const currentPostId = useSelect( ( select ) => {
		const store = select( STORE_NAME );
		return store ? store.getCurrentPostId( store.getState() ) : null;
	}, [] );

	// Actions
	const fetchTickets = ( postId: number ) => {
		const store = dispatch( STORE_NAME );
		return store ? store.fetchTickets( postId ) : Promise.resolve();
	};

	const createTicket = ( ticketData: any ) => {
		const store = dispatch( STORE_NAME );
		return store ? store.createTicket( ticketData ) : Promise.resolve();
	};

	const updateTicket = ( ticketId: number, ticketData: any ) => {
		const store = dispatch( STORE_NAME );
		return store ? store.updateTicket( ticketId, ticketData ) : Promise.resolve();
	};

	const deleteTicket = ( ticketId: number ) => {
		const store = dispatch( STORE_NAME );
		return store ? store.deleteTicket( ticketId ) : Promise.resolve();
	};

	const clearError = () => {
		const store = dispatch( STORE_NAME );
		return store ? store.clearError() : undefined;
	};

	return {
		// State
		tickets,
		isLoading,
		error,
		currentPostId,

		// Actions
		fetchTickets,
		createTicket,
		updateTicket,
		deleteTicket,
		clearError,
	};
};
