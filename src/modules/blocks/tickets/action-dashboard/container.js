/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { dispatch as wpDispatch, select } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors, constants } from '@moderntribe/tickets/data/blocks/ticket';
import { hasRecurrenceRules, noTicketsOnRecurring } from '@moderntribe/common/utils/recurrence';

const mapStateToProps = ( state, ownProps ) => {
	const provider = selectors.getTicketsProvider( state );
	const page = constants.TICKET_ORDERS_PAGE_SLUG[ provider ];

	return {
		hasCreatedTickets: selectors.hasCreatedTickets( state ),
		hasOrdersPage: Boolean( page ),
		hasRecurrenceRules: hasRecurrenceRules( state ),
		noTicketsOnRecurring: noTicketsOnRecurring(),
		onConfirmClick: () => { // eslint-disable-line wpcalypso/redux-no-bound-selectors
			const { clientId } = ownProps;
			const { getBlockCount } = select( 'core/editor' );
			const { insertBlock } = wpDispatch( 'core/editor' );

			const nextChildPosition = getBlockCount( clientId );
			const block = createBlock( 'tribe/tickets-item', {} );
			insertBlock( block, nextChildPosition, clientId );
		},
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );
