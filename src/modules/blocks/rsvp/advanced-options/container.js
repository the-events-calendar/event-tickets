/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPAdvancedOptions from './template';
import { plugins } from '@moderntribe/common/data';
import { selectors } from '@moderntribe/tickets/data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';

const getIsDisabled = ( state ) => (
	selectors.getRSVPIsLoading( state ) || selectors.getRSVPSettingsOpen( state )
);

const mapStateToProps = ( state ) => ( {
	isDisabled: getIsDisabled( state ),
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
	hasBeenCreated: selectors.getRSVPCreated( state ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( RSVPAdvancedOptions );
