/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

/**
 * Internal dependencies
 */
import { withSaveData, withStore } from '@moderntribe/common/hoc';
import * as actions from '@moderntribe/tickets/data/blocks/attendees/actions';
import * as selectors from '@moderntribe/tickets/data/blocks/attendees/selectors';
import Attendees from './template';

/**
 * Module Code
 */

const mapStateToProps = ( state ) => ( {
	title: selectors.getTitle( state ),
	displayTitle: selectors.getDisplayTitle( state ),
	displaySubtitle: selectors.getDisplaySubtitle( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	...bindActionCreators( actions, dispatch ),
	onSetDisplayTitleChange: onSetDisplayTitleChange( dispatch ),
	onSetDisplaySubtitleChange: onSetDisplaySubtitleChange( dispatch ),
} );

const onSetDisplayTitleChange = ( dispatch ) => ( checked ) => (
	dispatch( actions.setDisplayTitle( checked ) )
);

const onSetDisplaySubtitleChange = ( dispatch ) => ( checked ) => (
	dispatch( actions.setDisplaySubtitle( checked ) )
);

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
	withSaveData(),
)( Attendees );
