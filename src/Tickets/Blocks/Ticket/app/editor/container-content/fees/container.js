/**
 * External dependencies
 */
import {
	select,
	withDispatch,
	withSelect
} from '@wordpress/data';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';

const storeName = 'tec-tickets-fees';

const FeeDisplay = withSelect( ( select, ownProps ) => {
	const store = select( storeName );

	return {
		feesSelected: store.getSelectedFees(),
		feesAutomatic: store.getAutomaticFees(),
		feesAvailable: store.getAvailableFees(),
		shouldDisplay: store.shouldShowFees(),
		clientId: ownProps.clientId,
	};
} );


// export default FeeDisplay;
export default compose(
	FeeDisplay
)( Template );
