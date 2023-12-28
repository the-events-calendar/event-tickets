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

	return {
		independentCapacity: selectors.getIndependentTicketsCapacity(state),
		independentTicketItems: getIndependentTicketItems(state),
		isSettingsLoading: selectors.getTicketsIsSettingsLoading(state),
		sharedCapacity: selectors.getTicketsSharedCapacity(state),
		sharedTicketItems: getSharedTicketItems(state),
		totalCapacity,
		unlimitedTicketItems,
	};
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
