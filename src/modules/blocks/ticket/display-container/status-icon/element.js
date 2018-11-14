/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import {
	ClockActive,
	ClockInactive,
	TicketActive,
	TicketInactive,
} from '@moderntribe/tickets/icons';

const StatusIcon = ( { expires, disabled } ) => {
	if ( expires ) {
		return disabled ? <ClockInactive /> : <ClockActive />;
	}
	return disabled ? <TicketInactive /> : <TicketActive />;
};

StatusIcon.defaultProps = {
	expires: false,
	disabled: false,
}

StatusIcon.propTypes = {
	expires: PropTypes.bool,
	disabled: PropTypes.bool,
}

export default StatusIcon;
