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
	value: selectors.getTicketSKU( state, ownProps ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onChange( type ) {
		const { blockId } = ownProps;
		dispatch( actions.setSKU( blockId, type ) );
	},
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
