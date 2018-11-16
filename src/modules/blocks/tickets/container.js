/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withSaveData, withStore } from '@moderntribe/common/hoc';
<<<<<<< HEAD
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
=======
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';
>>>>>>> release/F18.3

const mapStateToProps = ( state ) => ( {
	header: selectors.getTicketsHeaderImageId( state ),
	isSettingsOpen: selectors.getTicketsIsSettingsOpen( state ),
	provider: selectors.getTicketsProvider( state ),
	sharedCapacity: selectors.getTicketsSharedCapacity( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	setInitialState: ( props ) => {
		dispatch( actions.setTicketsInitialState( props ) );
	},
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
	withSaveData(),
)( Template );
