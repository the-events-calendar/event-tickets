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
import { selectors } from '../../../../../../../modules/data/blocks/ticket';

const getSharedSold = ( state, isShared ) => ( isShared ? selectors.getSharedTicketsSold( state ) : 0 );

const mapStateToProps = ( state, ownProps ) => {
	const isShared = selectors.isSharedTicket( state, ownProps );

	const mappedProps = {
		isDisabled: selectors.isTicketDisabled( state, ownProps ),
		isShared,
		isUnlimited: selectors.isUnlimitedTicket( state, ownProps ),
		sold: selectors.getTicketSold( state, ownProps ),
		capacity: selectors.getTicketCapacityInt( state, ownProps ),
		sharedSold: getSharedSold( state, isShared ),
		sharedCapacity: selectors.getTicketsSharedCapacityInt( state ),
		isHidden: false,
	};

	/**
	 * Filters the properties mapped from the state for a ticket's sold count and bar.
	 *
	 * @since TBD
	 *
	 * @param {Object} mappedProps      The mapped props; `isHidden` leaves the count and bar out.
	 * @param {Object} context.state    The state of the block.
	 * @param {Object} context.ownProps The props passed to the block.
	 */
	return applyFilters( 'tec.tickets.blocks.Ticket.Quantity.mappedProps', mappedProps, { state, ownProps } );
};

export default compose( withStore(), connect( mapStateToProps ) )( Template );
