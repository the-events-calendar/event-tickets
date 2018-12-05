/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import DateTimeRangePicker from './template';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';
import {
	globals,
	moment as momentUtil,
} from '@moderntribe/common/utils';

const onFromDateChange = ( dispatch ) => ( date, modifiers, dayPickerInput ) => {
	const payload = {
		date,
		dayPickerInput,
	};
	dispatch( actions.handleRSVPStartDate( payload ) );
};

const onFromTimePickerBlur = ( dispatch ) => ( e ) => {
	const { value } = e.target;
	const payload = {
		value,
		isSeconds: false,
	};
	dispatch( actions.handleRSVPStartTime( payload ) );
};

const onFromTimePickerChange = ( dispatch ) => ( e ) => (
	dispatch( actions.setRSVPTempStartTimeInput( e.target.value ) )
);

const onFromTimePickerClick = ( dispatch ) => ( value, onClose ) => {
	const payload = {
		value,
		isSeconds: true,
	};
	dispatch( actions.handleRSVPStartTime( payload ) );
	onClose();
};

const onToDateChange = ( dispatch ) => ( date, modifiers, dayPickerInput ) => {
	const payload = {
		date,
		dayPickerInput,
	};
	dispatch( actions.handleRSVPEndDate( payload ) );
};

const onToTimePickerBlur = ( dispatch ) => ( e ) => {
	const { value } = e.target;
	const payload = {
		value,
		isSeconds: false,
	};
	dispatch( actions.handleRSVPEndTime( payload ) );
};

const onToTimePickerChange = ( dispatch ) => ( e ) => (
	dispatch( actions.setRSVPTempEndTimeInput( e.target.value ) )
);

const onToTimePickerClick = ( dispatch ) => ( value, onClose ) => {
	const payload = {
		value,
		isSeconds: true,
	};
	dispatch( actions.handleRSVPEndTime( payload ) );
	onClose();
};

const mapStateToProps = ( state ) => {
	const datePickerFormat = globals.tecDateSettings().datepickerFormat
		? momentUtil.toFormat( globals.tecDateSettings().datepickerFormat )
		: 'LL';
	const isDisabled = selectors.getRSVPIsLoading( state )
		|| selectors.getRSVPSettingsOpen( state );

	return {
		fromDate: selectors.getRSVPTempStartDateInput( state ),
		fromDateDisabled: isDisabled,
		fromDateFormat: datePickerFormat,
		fromTime: selectors.getRSVPTempStartTimeNoSeconds( state ),
		fromTimeDisabled: isDisabled,
		toDate: selectors.getRSVPTempEndDateInput( state ),
		toDateDisabled: isDisabled,
		toDateFormat: datePickerFormat,
		toTime: selectors.getRSVPTempEndTimeNoSeconds( state ),
		toTimeDisabled: isDisabled,
	};
};

const mapDispatchToProps = ( dispatch ) => ( {
	onFromDateChange: onFromDateChange( dispatch ),
	onFromTimePickerBlur: onFromTimePickerBlur( dispatch ),
	onFromTimePickerChange: onFromTimePickerChange( dispatch ),
	onFromTimePickerClick: onFromTimePickerClick( dispatch ),
	onToDateChange: onToDateChange( dispatch ),
	onToTimePickerBlur: onToTimePickerBlur( dispatch ),
	onToTimePickerChange: onToTimePickerChange( dispatch ),
	onToTimePickerClick: onToTimePickerClick( dispatch ),
} );

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps ),
)( DateTimeRangePicker );
