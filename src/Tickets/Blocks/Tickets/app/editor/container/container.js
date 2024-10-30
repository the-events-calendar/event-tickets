/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';

const getHasOverlay = (state, ownProps) =>
	selectors.getTicketsIsSettingsOpen(state) ||
	(!selectors.hasATicketSelected(state) && !ownProps.isSelected);

const getShowInactiveBlock = (state, ownProps) => {
	const showIfBlockIsSelected =
		ownProps.isSelected && !selectors.hasTickets(state);
	const showIfBlockIsNotSelected =
		!ownProps.isSelected &&
		!selectors.hasATicketSelected(state) &&
		(!selectors.hasCreatedTickets(state) ||
			!selectors.hasTicketOnSale(state));

	return showIfBlockIsSelected || showIfBlockIsNotSelected;
};

const mapStateToProps = (state, ownProps) => {
	let mappedProps = {
		Warning: null,
		canCreateTickets: selectors.canCreateTickets(),
		hasATicketSelected: selectors.hasATicketSelected(state),
		hasOverlay: getHasOverlay(state, ownProps),
		isSettingsOpen: selectors.getTicketsIsSettingsOpen(state),
		showAvailability:
			ownProps.isSelected && selectors.hasCreatedTickets(state),
		showInactiveBlock: getShowInactiveBlock(state, ownProps),
		showUneditableTickets: true,
		showWarning: false,
		uneditableTickets: selectors.getUneditableTickets(state),
		uneditableTicketsAreLoading:
			selectors.getUneditableTicketsAreLoading(state),
	};

	/**
	 * Filters the properties mapped from the state for the TicketsContainer component.
	 *
	 * @since 5.8.0
	 *
	 * @param {Object} mappedProps      The mapped props.
	 * @param {Object} context.state    The state of the block.
	 * @param {Object} context.ownProps The props passed to the block.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.TicketsContainer.mappedProps',
		mappedProps,
		{ state, ownProps }
	);

	return mappedProps;
};

export default compose(withStore(), connect(mapStateToProps))(Template);
