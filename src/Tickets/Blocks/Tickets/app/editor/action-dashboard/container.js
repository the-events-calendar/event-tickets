/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors, constants } from '@moderntribe/tickets/data/blocks/ticket';
import { hasRecurrenceRules } from '@moderntribe/common/utils/recurrence';

const mapStateToProps = (state, ownProps) => {
	const provider = selectors.getTicketsProvider(state);
	const page = constants.TICKET_ORDERS_PAGE_SLUG[provider];
	const isRecurring = hasRecurrenceRules(state);
	const selectedBlock = select('core/block-editor').getSelectedBlock();
	const isBlockSelected = selectedBlock?.name === 'tribe/tickets';

	let mappedProps = {
		hasCreatedTickets: selectors.hasCreatedTickets(state),
		hasOrdersPage: Boolean(page),
		showNotSupportedMessage: isRecurring && isBlockSelected,
		showConfirm: true,
		disableSettings: false,
		clientId: ownProps.clientId,
		onConfirmClick: () => {
			// eslint-disable-line wpcalypso/redux-no-bound-selectors
			const { clientId } = ownProps;
			const { getBlockCount } = select('core/block-editor');
			const { insertBlock } = dispatch('core/block-editor');

			const nextChildPosition = getBlockCount(clientId);
			const block = createBlock('tribe/tickets-item', {});
			insertBlock(block, nextChildPosition, clientId);
		},
		isConfirmDisabled: false,
	};

	/**
	 * Filters the properties mapped from the state for the TicketsDashboardAction component.
	 *
	 * @since 5.8.0
	 *
	 * @param {Object} mappedProps      The mapped props.
	 * @param {Object} context.state    The state of the block.
	 * @param {Object} context.ownProps The props passed to the block.
	 */
	mappedProps = applyFilters(
		'tec.tickets.blocks.Tickets.TicketsDashboardAction.mappedProps',
		mappedProps,
		{ state, ownProps, isRecurring }
	);

	return mappedProps;
};

export default compose(withStore(), connect(mapStateToProps))(Template);
