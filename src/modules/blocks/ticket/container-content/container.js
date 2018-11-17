/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import Template from './template';
import { plugins } from '@moderntribe/common/data';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps ),
)( Template );

