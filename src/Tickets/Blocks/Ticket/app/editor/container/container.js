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
import { selectors } from '../../../../../../modules/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => ( {
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
	isFuture: selectors.isTicketFuture( state, ownProps ),
	isPast: selectors.isTicketPast( state, ownProps ),
	isOnSale: selectors.getTicketOnSale( state, ownProps ),
} );

export default compose( withStore(), connect( mapStateToProps ) )( Template );
