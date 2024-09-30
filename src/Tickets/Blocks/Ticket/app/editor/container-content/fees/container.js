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

/**
const mapStateToProps = ( state, ownProps ) => ({
	// Fetching the selected and active fees from the state
	selectedFees: state.selectedFees,
	activeFees: state.activeFees,
});
	**/

const mapStateToProps = (state, ownProps) => ({
	// Mock data for testing active and selected fees
	activeFees: [
		{ label: 'Service Fee', value: '123' },
		{ label: 'Handling Fee', value: '456' },
		{ label: 'Convenience Fee', value: '789' },
	],
	selectedFees: [
		{ label: 'Service Fee', value: '8965' },
	],
});

const mapDispatchToProps = ( dispatch, ownProps ) => ({
	// Action for updating selected fees
	onSelectedFeesChange: ( selected ) => {
		const { clientId } = ownProps;
		dispatch( actions.setSelectedFees( clientId, selected.map( fee => fee.value ) ) );
		dispatch( actions.setTicketHasChanges( clientId, true ) );
	},

	// Optionally, you can add other actions if needed for active fees or other features
});


export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
