/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '../../../../../../modules/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => ( {
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
	isFuture: selectors.isTicketFuture( state, ownProps ),
	isPast: selectors.isTicketPast( state, ownProps ),
	/** This filter is documented in src/Tickets/Blocks/Ticket/app/editor/container-header/price/container.js */
	isOnSale: applyFilters(
		'tec.tickets.blocks.Ticket.onSale',
		selectors.getTicketOnSale( state, ownProps ),
		ownProps.clientId
	),
} );

export default compose( withStore(), connect( mapStateToProps ) )( Template );
