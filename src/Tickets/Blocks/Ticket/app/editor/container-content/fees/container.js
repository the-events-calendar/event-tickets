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
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state, ownProps ) => ({
	// Fetching the selected and active fees from the state
	selectedFees: selectors.getSelectedFees( state, ownProps ),
	activeFees: selectors.getActiveFees( state, ownProps ),
});

const mapDispatchToProps = ( dispatch, ownProps ) => ({
	// Action for updating selected fees
	onSelectedFeesChange: ( selected ) => {
		const { clientId } = ownProps;
		dispatch( actions.setSelectedFees( clientId, selected.map( fee => fee.value ) ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	},
});

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
