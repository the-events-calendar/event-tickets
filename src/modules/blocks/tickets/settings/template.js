/**
 * External dependencies
 */
import React, { Fragment } from 'react';
import PropTypes from 'prop-types';
import noop from 'lodash';

/**
 * Internal dependencies
 */
import { SettingsDashboard } from '@moderntribe/tickets/elements';
import CapacityTable from '@moderntribe/tickets/blocks/tickets/capacity-table/container';
import TicketImage from './../header-image/container';

const TicketsSettingsDashboard = ( { onCloseClick, content, hasTicketsPlus } ) => (
	<SettingsDashboard
		className="tribe-editor__tickets__settings-dashboard"
		content={ (
			<Fragment>
				{ hasTicketsPlus && <CapacityTable /> }
				<TicketImage />
			</Fragment>
		) }
		onCloseClick={ onCloseClick }
	/>
);

TicketsSettingsDashboard.propTypes = {
	onCloseClick: PropTypes.func.isRequired,
	hasTicketsPlus: PropTypes.bool,
};

TicketsSettingsDashboard.defaultProps = {
	onCloseClick: noop,
	hasTicketsPlus: false,
};

export default TicketsSettingsDashboard;
