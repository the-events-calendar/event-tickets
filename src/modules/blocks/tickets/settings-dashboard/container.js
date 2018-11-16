/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import TicketsSettingsDashboard from './template';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	isSettingsLoading: selectors.getTicketsIsSettingsLoading( state ),
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onCloseClick: () => dispatch( actions.closeSettings() ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( TicketsSettingsDashboard );

