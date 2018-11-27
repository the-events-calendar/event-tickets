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
import { actions } from '@moderntribe/tickets/data/blocks/ticket';

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	removeTicket: () => {
		dispatch( actions.deleteTicket( ownProps.blockId ) );
	},
	moveTicket: () => console.warn( 'Implement me' ),
} );

export default compose(
	withStore(),
	connect( null, mapDispatchToProps ),
)( Template );
