/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import TicketsSettingsDashboard from './template';
<<<<<<< HEAD:src/modules/blocks/tickets/settings-dashboard/container.js
import { actions, selectors } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	isSettingsLoading: selectors.getTicketsIsSettingsLoading( state ),
=======
import { plugins } from '@moderntribe/common/data';
import { actions } from '@moderntribe/tickets/data/blocks/ticket';
import { withStore } from '@moderntribe/common/hoc';

const mapStateToProps = ( state ) => ( {
	hasTicketsPlus: plugins.selectors.hasPlugin( state )( plugins.constants.TICKETS_PLUS ),
>>>>>>> release/F18.3:src/modules/blocks/tickets/settings/container.js
} );

const mapDispatchToProps = ( dispatch ) => ( {
	onCloseClick: () => dispatch( actions.closeSettings() ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( TicketsSettingsDashboard );

