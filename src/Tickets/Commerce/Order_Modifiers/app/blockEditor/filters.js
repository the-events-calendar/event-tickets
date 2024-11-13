import { addFilter, addAction } from '@wordpress/hooks';
import { storeName } from './store';
import { select } from '@wordpress/data';

import {
	filterSetBodyDetails,
} from './hook-callbacks';



addFilter(
	'tec.tickets.blocks.setBodyDetails',
	'tec.tickets.order-modifiers.fees',
	filterSetBodyDetails
);
