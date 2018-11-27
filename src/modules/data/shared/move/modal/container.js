/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import { withStore } from '@moderntribe/common/hoc';
import * as selectors from '@moderntribe/tickets/data/shared/move/selectors';
import { INITIALIZE_MODAL, HIDE_MODAL } from '@moderntribe/tickets/data/shared/move/types';
import Template from './template';

const mapStateToProps = ( state, ownProps ) => ( {
	isModalShowing: selectors.isModalShowing( state ),
	isFetchingPosts: selectors.isFetchingPosts( state ),
	isFetchingPostTypes: selectors.isFetchingPostTypes( state ),
	postTypes: selectors.getPostTypes( state ),
	posts: selectors.getPosts( state ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	initialize: () => dispatch( { type: INITIALIZE_MODAL } ),
	hideModal: () => dispatch( { type: HIDE_MODAL } ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( Template );

