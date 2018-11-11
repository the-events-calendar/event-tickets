/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import { DateTimeRangePicker } from '@moderntribe/tickets/elements';
import './style.pcss';

const RSVPDurationPicker = ( props ) => (
	<DateTimeRangePicker { ...props } />
);

RSVPDurationPicker.propTypes = {
	fromDate: PropTypes.string,
	fromTime: PropTypes.string,
	isSameDay: PropTypes.bool,
	onFromDateChange: PropTypes.func,
	onFromTimePickerChange: PropTypes.func,
	onFromTimePickerClick: PropTypes.func,
	onToDateChange: PropTypes.func,
	onToTimePickerChange: PropTypes.func,
	onToTimePickerClick: PropTypes.func,
	toDate: PropTypes.string,
	toTime: PropTypes.string,
};

export default RSVPDurationPicker;
