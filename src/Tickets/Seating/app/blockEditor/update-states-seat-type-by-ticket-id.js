import { storeName } from './store';
import { dispatch } from '@wordpress/data';

export const useUpdateStatesSeatTypeByTicketId = (clientId) => {
	dispatch(storeName).setTicketSeatTypeByPostId(clientId);
};
