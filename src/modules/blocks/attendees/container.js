/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

/**
 * Internal dependencies
 */
import { withSaveData, withStore } from '@moderntribe/common/hoc';
import * as UIActions from '@moderntribe/tickets/data/blocks/attendees/actions';
import * as UISelectors from '@moderntribe/tickets/data/blocks/attendees/selectors';
import Attendees from './template';

/**
 * Module Code
 */

const mapStateToProps = ( state ) => ( {
	title: UISelectors.getTitle( state ),
	displayTitle: UISelectors.getDisplayTitle( state ),
	displaySubtitle: UISelectors.getDisplaySubtitle( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	...bindActionCreators( UIActions, dispatch ),
	onSetDisplayTitleChange: onSetDisplayTitleChange( dispatch ),
	onSetDisplaySubtitleChange: onSetDisplaySubtitleChange( dispatch ),
} );

const onSetDisplayTitleChange = ( dispatch ) => ( checked ) => (
	dispatch( UIActions.setDisplayTitle( checked ) )
);

const onSetDisplaySubtitleChange = ( dispatch ) => ( checked ) => (
	dispatch( UIActions.setDisplaySubtitle( checked ) )
);

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
	withSaveData(),
)( Attendees );
