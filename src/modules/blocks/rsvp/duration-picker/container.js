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
	let startTimeMoment = momentUtil.toMoment( e.target.value, momentUtil.TIME_FORMAT, false );
	if ( startTimeMoment.isValid() ) {
		const startTimeInput = selectors.getRSVPStartTimeInput( state )
		startTimeMoment = momentUtil.toMoment( startTimeInput, momentUtil.TIME_FORMAT, false );
	}
	const seconds = momentUtil.totalSeconds( startTimeMoment );
	dispatch( actions.handleRSVPStartTime( seconds ) );
};

const onFromTimePickerChange = ( dispatch ) => ( e ) => (
	dispatch( actions.setRSVPTempStartTimeInput( e.target.value ) )
);

const onFromTimePickerClick = ( dispatch ) => ( value, onClose ) => {
	dispatch( actions.handleRSVPStartTime( value ) );
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
	let endTimeMoment = momentUtil.toMoment( e.target.value, momentUtil.TIME_FORMAT, false );
	if ( endTimeMoment.isValid() ) {
		const endTimeInput = selectors.getRSVPEndTimeInput( state )
		endTimeMoment = momentUtil.toMoment( endTimeInput, momentUtil.TIME_FORMAT, false );
	}
	const seconds = momentUtil.totalSeconds( endTimeMoment );
	dispatch( actions.handleRSVPEndTime( seconds ) );
};

const onToTimePickerChange = ( dispatch ) => ( e ) => (
	dispatch( actions.setRSVPTempEndTimeInput( e.target.value ) )
);

const onToTimePickerClick = ( dispatch ) => ( value, onClose ) => {
	dispatch( actions.handleRSVPEndTime( value ) );
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
		fromTime: selectors.getRSVPTempStartTimeInput( state ),
		fromTimeDisabled: isDisabled,
		toDate: selectors.getRSVPTempEndDateInput( state ),
		toDateDisabled: isDisabled,
		toDateFormat: datePickerFormat,
		toTime: selectors.getRSVPTempEndTimeInput( state ),
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
