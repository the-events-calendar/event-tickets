/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPRsvpWindow from './template';
import { selectors } from '../../../data/blocks/rsvp-v2';
import { withStore } from '@moderntribe/common/hoc';
import { formatRsvpWindow } from '../utils/format-rsvp-window';
import { showEditAffordances as getShowEditAffordances } from '../utils/block-state';

const mapStateToProps = ( state, ownProps ) => {
	const startDateMoment = selectors.getRSVPStartDateMoment( state );
	const endDateMoment = selectors.getRSVPEndDateMoment( state );

	return {
		dateRange: formatRsvpWindow( startDateMoment, endDateMoment ),
		showEditAffordances: getShowEditAffordances( {
			created: selectors.getRSVPCreated( state ),
			isSelected: ownProps.isSelected,
		} ),
	};
};

export default compose( withStore(), connect( mapStateToProps ) )( RSVPRsvpWindow );
