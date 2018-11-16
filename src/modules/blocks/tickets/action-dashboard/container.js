/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { dispatch as wpDispatch, select } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state, ownProps ) => ( {
	onConfirmClick: () => {
		const { clientId } = ownProps;
		const { getBlockCount } = select( 'core/editor' );
		const { insertBlock } = wpDispatch( 'core/editor' );

		const nextChildPosition = getBlockCount( clientId );
		const block = createBlock( 'tribe/tickets-item', {} );
		insertBlock( block, nextChildPosition, clientId );
	},
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );
