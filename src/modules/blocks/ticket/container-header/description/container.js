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

const mapStateToProps = ( state, ownProps ) => ( {
	isDisabled: selectors.isTicketDisabled( state, ownProps ),
	tempDescription: selectors.getTicketTempDescription( state, ownProps ),
	description: selectors.getTicketDescription( state, ownProps ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onTempDescriptionChange: ( e ) => {
		const { blockId } = ownProps;
		dispatch( actions.setTicketTempDescription( blockId, e.target.value ) );
		dispatch( actions.setTicketHasChanges( blockId, true ) );
	},
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
