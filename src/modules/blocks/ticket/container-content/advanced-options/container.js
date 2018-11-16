/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
<<<<<<< HEAD:src/modules/blocks/ticket/container-content/advanced-options/container.js
=======

import { withSaveData, withStore } from '@moderntribe/common/hoc';
>>>>>>> release/F18.3:src/modules/blocks/ticket/display-container/container.js
import { selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state, ownProps ) => ( {
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );
