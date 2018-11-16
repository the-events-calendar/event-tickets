/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
<<<<<<< HEAD:src/modules/blocks/ticket/container/container.js

=======
import { plugins } from '@moderntribe/common/data';
>>>>>>> release/F18.3:src/modules/blocks/ticket/edit-container/container.js
import { withStore } from '@moderntribe/common/hoc';
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => ( {
<<<<<<< HEAD:src/modules/blocks/ticket/container/container.js
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
=======
	expires: selectors.getTicketExpires( state, ownProps ),
	isTicketDisabled: selectors.isTicketDisabled( state, ownProps ),
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
>>>>>>> release/F18.3:src/modules/blocks/ticket/edit-container/container.js
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );

