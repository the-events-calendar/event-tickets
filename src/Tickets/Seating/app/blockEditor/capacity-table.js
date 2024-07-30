import {select} from "@wordpress/data";
import { storeName } from './store';
import {addFilter} from "@wordpress/hooks";
import { getLocalizedString } from '@tec/tickets/seating/utils';

export function filterCapacityTableMappedProps(mappedProps) {
	const hasSeats = select(storeName).isUsingAssignedSeating();
	const layoutLocked = select(storeName).isLayoutLocked();

	if ( ! hasSeats || ! layoutLocked ) {
		return mappedProps;
	}

	let layoutId  = select(storeName).getCurrentLayoutId();
	if ( ! layoutId ) {
		return mappedProps;
	}

	let seatTypes = select(storeName).getSeatTypesForLayout(layoutId, true);
	let activeSeatTypes = Object.values( select(storeName).getSeatTypesByPostID() );

	mappedProps.rowsAfter = mappedProps.rowsAfter || [];
	const seatTypeLabels = activeSeatTypes.map( type => seatTypes[type].name );
	const seatTypeTotalCapacity = activeSeatTypes.reduce( ( sum, type ) => sum + parseInt(seatTypes[type].seats), 0 );
	mappedProps.rowsAfter.push({
		label: getLocalizedString( 'seats-row-label', 'capacity-table' ),
		items: seatTypeLabels ? `(${seatTypeLabels})` : '',
		right: String(seatTypeTotalCapacity),
	});

	mappedProps.totalCapacity  = ( mappedProps.totalCapacity - mappedProps.sharedCapacity ) + seatTypeTotalCapacity;
	mappedProps.sharedCapacity = '';
	mappedProps.sharedTicketItems = '';

	return mappedProps;
}

addFilter(
	'tec.tickets.blocks.Tickets.CapacityTable.mappedProps',
	'tec.tickets.flexibleTickets',
	filterCapacityTableMappedProps
);
