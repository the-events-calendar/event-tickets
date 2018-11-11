/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { withSaveData, withStore } from '@moderntribe/common/src/modules/hoc';
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';

const mapStateToProps = ( state ) => ( {
	isBlockSelected: selectors.getParentOrChildSelected( state ),
	isEditing: selectors.hasActiveBlockId( state ),
	header: `${ selectors.getImageId( state ) }`,
	sharedCapacity: `${ selectors.getSharedCapacity( state ) }`,
} );

const mapDispatchToProps = ( dispatch ) => ( {
	setIsSelected( selected ) {
		dispatch( actions.setParentBlockSelected( selected ) );
	},
	setInitialState: ( props ) => {
		dispatch( actions.setInitialState( props ) );
	},
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
	withSaveData(),
)( Template );
