/**
 * V2 RSVP Duration Picker Container
 *
 * Wraps the V1 duration picker and autosaves changes via REST.
 */

/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import DateTimeRangePicker from '../../rsvp/duration-picker/template';
import { actions, selectors } from '../../../data/blocks/rsvp-v2';
import { withStore } from '@moderntribe/common/hoc';
import { globals, moment as momentUtil } from '@moderntribe/common/utils';
import { schedulePersistRSVP } from '../utils/schedule-persist-rsvp';

const onFromDateChange = ( dispatch, autosave ) => ( date, modifiers, dayPickerInput ) => {
	dispatch(
		actions.handleRSVPStartDate( {
			date,
			dayPickerInput,
		} )
	);
	if ( autosave ) {
		schedulePersistRSVP( dispatch );
	}
};

const onFromTimePickerChange = ( dispatch ) => ( e ) => dispatch( actions.setRSVPTempStartTimeInput( e.target.value ) );

const onFromTimePickerClick = ( dispatch, autosave ) => ( value, onClose ) => {
	dispatch( actions.handleRSVPStartTime( value ) );
	onClose();
	if ( autosave ) {
		schedulePersistRSVP( dispatch );
	}
};

const onToDateChange = ( dispatch, autosave ) => ( date, modifiers, dayPickerInput ) => {
	dispatch(
		actions.handleRSVPEndDate( {
			date,
			dayPickerInput,
		} )
	);
	if ( autosave ) {
		schedulePersistRSVP( dispatch );
	}
};

const onToTimePickerChange = ( dispatch ) => ( e ) => dispatch( actions.setRSVPTempEndTimeInput( e.target.value ) );

const onToTimePickerClick = ( dispatch, autosave ) => ( value, onClose ) => {
	dispatch( actions.handleRSVPEndTime( value ) );
	onClose();
	if ( autosave ) {
		schedulePersistRSVP( dispatch );
	}
};

const onFromTimePickerBlur = ( state, dispatch, autosave ) => ( e ) => {
	let startTimeMoment = momentUtil.toMoment( e.target.value, momentUtil.TIME_FORMAT, false );
	if ( ! startTimeMoment.isValid() ) {
		const startTimeInput = selectors.getRSVPStartTimeInput( state );
		startTimeMoment = momentUtil.toMoment( startTimeInput, momentUtil.TIME_FORMAT, false );
	}
	const seconds = momentUtil.totalSeconds( startTimeMoment );
	dispatch( actions.handleRSVPStartTime( seconds ) );
	if ( autosave ) {
		schedulePersistRSVP( dispatch );
	}
};

const onToTimePickerBlur = ( state, dispatch, autosave ) => ( e ) => {
	let endTimeMoment = momentUtil.toMoment( e.target.value, momentUtil.TIME_FORMAT, false );
	if ( ! endTimeMoment.isValid() ) {
		const endTimeInput = selectors.getRSVPEndTimeInput( state );
		endTimeMoment = momentUtil.toMoment( endTimeInput, momentUtil.TIME_FORMAT, false );
	}
	const seconds = momentUtil.totalSeconds( endTimeMoment );
	dispatch( actions.handleRSVPEndTime( seconds ) );
	if ( autosave ) {
		schedulePersistRSVP( dispatch );
	}
};

const mapStateToProps = ( state ) => {
	const datePickerFormat = globals.tecDateSettings().datepickerFormat
		? momentUtil.toFormat( globals.tecDateSettings().datepickerFormat )
		: 'LL';
	const isDisabled = selectors.getRSVPIsLoading( state ) || selectors.getRSVPSettingsOpen( state );

	const startDateMoment = selectors.getRSVPTempStartDateMoment( state );
	const endDateMoment = selectors.getRSVPTempEndDateMoment( state );
	const fromDate = startDateMoment && startDateMoment.toDate();
	const toDate = endDateMoment && endDateMoment.toDate();

	return {
		fromDate,
		fromDateInput: selectors.getRSVPTempStartDateInput( state ),
		fromDateDisabled: isDisabled,
		fromDateFormat: datePickerFormat,
		fromTime: selectors.getRSVPTempStartTimeInput( state ),
		fromTimeDisabled: isDisabled,
		toDate,
		toDateInput: selectors.getRSVPTempEndDateInput( state ),
		toDateDisabled: isDisabled,
		toDateFormat: datePickerFormat,
		toTime: selectors.getRSVPTempEndTimeInput( state ),
		toTimeDisabled: isDisabled,
		state,
	};
};

const mapDispatchToProps = ( dispatch ) => ( {
	dispatch,
} );

const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	const { state, ...restStateProps } = stateProps;
	const { dispatch } = dispatchProps;
	const autosave = ownProps.autosave !== false;

	return {
		...ownProps,
		...restStateProps,
		onFromDateChange: onFromDateChange( dispatch, autosave ),
		onFromTimePickerChange: onFromTimePickerChange( dispatch ),
		onFromTimePickerClick: onFromTimePickerClick( dispatch, autosave ),
		onToDateChange: onToDateChange( dispatch, autosave ),
		onToTimePickerChange: onToTimePickerChange( dispatch ),
		onToTimePickerClick: onToTimePickerClick( dispatch, autosave ),
		onFromTimePickerBlur: onFromTimePickerBlur( state, dispatch, autosave ),
		onToTimePickerBlur: onToTimePickerBlur( state, dispatch, autosave ),
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, mapDispatchToProps, mergeProps )
)( DateTimeRangePicker );
