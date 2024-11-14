/**
 * External dependencies
 */
import {
	select,
	withDispatch,
	withSelect
} from '@wordpress/data';

/**
 * Internal dependencies
 */
import Template from './template';
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';

const storeName = 'tec-tickets-fees';

const mapDispatchToProps = ( dispatch, ownProps ) => ( {

	onSelectedFeesChange: ( selected ) => {
		const { clientId } = ownProps;
		const store = select( storeName );
		const previousSelected = store.getSelectedFees( clientId );

		dispatch( actions.setTicketFees( clientId, selected ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	},

	// Optionally, you can add other actions if needed for active fees or other features
} );

const FeeDisplay = withSelect( ( select, ownProps ) => {
	const store = select( storeName );

	return {
		feesSelected: store.getSelectedFees(),
		feesAutomatic: store.getAutomaticFees(),
		feesAvailable: store.getAvailableFees(),
		shouldDisplay: store.shouldShowFees(),
	};
} )( Template );


export default FeeDisplay;
