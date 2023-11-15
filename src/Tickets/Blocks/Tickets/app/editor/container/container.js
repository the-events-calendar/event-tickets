/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { applyFilters } from '@wordpress/hooks';
import { isTicketEditableFromPost } from '@moderntribe/tickets/data/blocks/ticket/utils';

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

const getShowUneditableTickets = (state, ownProps) => {
	/**
	 * Filters whether to show uneditable tickets in the Ticket Container block.
	 * Uneditable tickets are tickets that should appear in the container, but not be editable in
	 * the context of this post.
	 *
	 * @since TBD
	 *
	 * @param {boolean} showUneditableTickets Whether to show uneditable tickets.
	 */
	return applyFilters(
		'tec.tickets.blocks.tickets.showUneditableTickets',
		true,
		state,
		ownProps
	);
};

const getUneditableTickets = (ownProps) => {
	const currentPost = wp.data.select('core/editor').getCurrentPost();
	const allTickets = ownProps.tickets || [];
	return allTickets.filter((ticket) =>
		isTicketEditableFromPost(ticket.id, ticket.type, currentPost)
	);
};

const mapStateToProps = (state, ownProps) => ({
	allTicketsFuture: selectors.allTicketsFuture(state),
	allTicketsPast: selectors.allTicketsPast(state),
	canCreateTickets: selectors.canCreateTickets(),
	hasCreatedTickets: selectors.hasCreatedTickets(state),
	hasOverlay: getHasOverlay(state, ownProps),
	isSettingsOpen: selectors.getTicketsIsSettingsOpen(state),
	showAvailability: ownProps.isSelected && selectors.hasCreatedTickets(state),
	showInactiveBlock: getShowInactiveBlock(state, ownProps),
	hasATicketSelected: selectors.hasATicketSelected(state),
	showUneditableTickets: getShowUneditableTickets(state, ownProps),
	uneditableTickets: getUneditableTickets(ownProps),
});

export default compose(withStore(), connect(mapStateToProps))(Template);
