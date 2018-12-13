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
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state ) => {
	const headerImageId = selectors.getTicketsHeaderImageId( state );
	return {
		header: headerImageId ? `${ headerImageId }` : '',
		hasProviders: selectors.hasTicketProviders(),
		isSettingsOpen: selectors.getTicketsIsSettingsOpen( state ),
		provider: selectors.getTicketsProvider( state ),
		sharedCapacity: selectors.getTicketsSharedCapacity( state ),
	};
};

const mapDispatchToProps = ( dispatch ) => ( {
	setInitialState: ( props ) => {
		dispatch( actions.setTicketsInitialState( props ) );
	},
	onBlockRemoved: () => {
		dispatch( actions.resetTicketsBlock() );
	},
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
	withSaveData(),
)( Template );
