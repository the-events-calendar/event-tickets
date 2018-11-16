/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { bindActionCreators, compose } from 'redux';

/**
 * Internal dependencies
 */
import withSaveData from '@moderntribe/common/hoc/with-save-data';
import withStore from '@moderntribe/common/hoc/with-store';
import * as actions from '@moderntribe/tickets/data/blocks/attendees/actions';
import * as selectors from '@moderntribe/tickets/data/blocks/attendees/selectors';
import Attendees from './template';

/**
 * Module Code
 */

const mapStateToProps = ( state ) => ( {
	title: selectors.getTitle( state ),
} );

const mapDispatchToProps = ( dispatch ) => bindActionCreators( actions, dispatch );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
	withSaveData(),
)( Attendees );
