/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * WordPress dependencies
 */
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import RSVPHeaderImage from './template';
import { selectors, thunks } from '@moderntribe/tickets/data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';

/**
 * Full payload from gutenberg media upload is not used,
 * only id, alt, and src are used for this specific case.
 */
const mapStateToProps = ( state ) => ( {
	image: {
		id: selectors.getRSVPHeaderImageId( state ),
		alt: selectors.getRSVPHeaderImageAlt( state ),
		src: selectors.getRSVPHeaderImageSrc( state ),
	},
	isSettingsLoading: selectors.getRSVPIsSettingsLoading( state ),
} );

const mapDispatchToProps = ( dispatch ) => {
	const postId = select( 'core/editor' ).getCurrentPostId();
	return {
		onRemove: () => dispatch( thunks.deleteRSVPHeaderImage( postId ) ),
		/**
		 * Full payload from gutenberg media upload is not used,
		 * only id, alt, and medium src are used for this specific case.
		 */
		onSelect: ( image ) => dispatch(
			thunks.updateRSVPHeaderImage( postId, image )
		),
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( RSVPHeaderImage );
