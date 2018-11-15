/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { plugins } from '@moderntribe/common/data';
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => ( {
	expires: selectors.getTicketExpires( state, ownProps ),
	isTicketDisabled: selectors.isTicketDisabled( state, ownProps ),
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );

