/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';


const mapStateToProps = ( state ) => ( {
	image: {
		id: selectors.getImageId( state ),
		alt: selectors.getImageAlt( state ),
		src: selectors.getHeaderSize( state, { size: 'medium' } ),
	},
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onSelect( image ) {
		dispatch( actions.setHeader( image ) );
	},
	onRemove() {
		dispatch( actions.setHeader( null ) );
	},
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( Template );
