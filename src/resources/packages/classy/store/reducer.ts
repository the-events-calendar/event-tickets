import { Reducer } from 'redux';
import { StoreState } from '../types/store';

const initialState: StoreState = {
	ticket: {
		price: 0,
		stock: 0,
		startDate: '',
		endDate: '',
		isFree: false,
		quantity: 0,
	},
};

export const reducer: Reducer<StoreState> = (state = initialState, action) => {
	return state;
};
