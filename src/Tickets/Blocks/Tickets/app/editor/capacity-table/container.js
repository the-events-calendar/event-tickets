/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import CapacityTable from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const getTicketItems = (tickets) => {
	const items = tickets
		.filter((ticket) => ticket.details.title)
		.map((ticket) => ticket.details.title)
		.join(', ');
	return items ? ` (${items}) ` : '';
};

const getIndependentTicketItems = (state) => {
	const independentTickets = selectors.getIndependentTickets(state);
	return getTicketItems(independentTickets);
};

const getSharedTicketItems = (state) => {
	const sharedTickets = selectors.getSharedTickets(state);
	return getTicketItems(sharedTickets);
};

const mapStateToProps = (state) => {
	const independentAndSharedCapacity =
		selectors.getIndependentAndSharedTicketsCapacity(state);
	const unlimitedTicketItems = getTicketItems(
		selectors.getUnlimitedTickets(state)
	);
	const totalCapacity = unlimitedTicketItems.length
		? __('Unlimited', 'event-tickets')
		: independentAndSharedCapacity;

	let mappedProps = {
		independentCapacity: selectors.getIndependentTicketsCapacity(state),
		independentTicketItems: getIndependentTicketItems(state),
		isSettingsLoading: selectors.getTicketsIsSettingsLoading(state),
		rowsAfter: [],
		sharedCapacity: selectors.getTicketsSharedCapacity(state),
		sharedTicketItems: getSharedTicketItems(state),
		totalCapacity,
		unlimitedTicketItems,
	};

	/**
	 * Filters the properties mapped from the state for the CapacityTable component.
	 *
	 * @since 5.8.0
	 *
	 * @param {Object} mappedProps   The mapped props.
	 * @param {Object} context.state The state of the block.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.CapacityTable.mappedProps',
		mappedProps,
		{ state }
	);

	return mappedProps;
};

const mapDispatchToProps = (dispatch) => ({
	onSharedCapacityChange: (e) => {
		dispatch(actions.setTicketsSharedCapacity(e.target.value));
		dispatch(actions.setTicketsTempSharedCapacity(e.target.value));
	},
});

export default compose(
	withStore(),
	connect(mapStateToProps, mapDispatchToProps)
)(CapacityTable);
