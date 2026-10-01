/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import RSVPV2DurationFields from './template';
import { selectors } from '../../../data/blocks/rsvp-v2';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	hasDurationError: selectors.getRSVPHasDurationError( state ),
} );

export default compose( withStore(), connect( mapStateToProps ) )( RSVPV2DurationFields );
