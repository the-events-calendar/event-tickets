/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import moment from 'moment/moment';

/**
 * Internal dependencies
 */
import DateTimeRangePicker from './template';
import { actions, selectors } from '@moderntribe/tickets/data/blocks/rsvp';
import { withStore } from '@moderntribe/common/hoc';
import {
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';

const getIsSameDay = ( state ) => {
	const startDateObj = selectors.getRSVPTempStartDateObj( state );
	const endDateObj = selectors.getRSVPTempEndDateObj( state );
	return momentUtil.isSameDay( startDateObj, endDateObj );
};

const onFromDateChange = ( stateProps, dispatch ) => ( date, modifiers, dayPickerInput ) => {
	/* TODO: prevent onchange to type/select a date after toDate */
	let startDate;

	if ( date ) {
		startDate = momentUtil.toDate( moment( date ) );
	} else {
		startDate = dayPickerInput.state.value;
	}

	dispatch( actions.setRSVPTempStartDate( startDate ) );
	dispatch( actions.setRSVPTempStartDateObj( date ) );
	dispatch( actions.setRSVPHasChanges( true ) );
};

const onFromTimePickerChange = ( stateProps, dispatch ) => ( e ) => {
	/* TODO: prevent change to a time out of range */
	const startTime = e.target.value;
	if ( startTime ) {
		dispatch( actions.setRSVPTempStartTime( startTime ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	}
};

const onFromTimePickerClick = ( dispatch ) => ( value, onClose ) => {
	const startTime = timeUtil.fromSeconds( value, timeUtil.TIME_FORMAT_HH_MM );
	dispatch( actions.setRSVPTempStartTime( startTime ) );
	dispatch( actions.setRSVPHasChanges( true ) );
	onClose();
};

const onToDateChange = ( stateProps, dispatch ) => ( date, modifiers, dayPickerInput ) => {
	/* TODO: prevent onchange to type/select a date before fromDate */
	let endDate;

	if ( date ) {
		endDate = momentUtil.toDate( moment( date ) );
	} else {
		endDate = dayPickerInput.state.value;
	}

	dispatch( actions.setRSVPTempEndDate( endDate ) );
	dispatch( actions.setRSVPTempEndDateObj( date ) );
	dispatch( actions.setRSVPHasChanges( true ) );
};

const onToTimePickerChange = ( stateProps, dispatch ) => ( e ) => {
	/* TODO: prevent change to a time out of range */
	const endTime = e.target.value;
	if ( endTime ) {
		dispatch( actions.setRSVPTempEndTime( endTime ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	}
};

const onToTimePickerClick = ( dispatch ) => ( value, onClose ) => {
	const endTime = timeUtil.fromSeconds( value, timeUtil.TIME_FORMAT_HH_MM );
	dispatch( actions.setRSVPTempEndTime( endTime ) );
	dispatch( actions.setRSVPHasChanges( true ) );
	onClose();
};

const mapStateToProps = ( state ) => {
	const isDisabled = selectors.getRSVPIsLoading( state )
		|| selectors.getRSVPSettingsOpen( state );

	return {
		fromDate: selectors.getRSVPTempStartDate( state ),
		fromDateDisabled: isDisabled,
		fromTime: selectors.getRSVPTempStartTime( state ),
		fromTimeDisabled: isDisabled,
		isSameDay: getIsSameDay( state ),
		toDate: selectors.getRSVPTempEndDate( state ),
		toDateDisabled: isDisabled,
		toTime: selectors.getRSVPTempEndTime( state ),
		toTimeDisabled: isDisabled,
	};
};

const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	const { dispatch } = dispatchProps;

	return {
		...ownProps,
		...stateProps,
		onFromDateChange: onFromDateChange( stateProps, dispatch ),
		onFromTimePickerChange: onFromTimePickerChange( stateProps, dispatch ),
		onFromTimePickerClick: onFromTimePickerClick( dispatch ),
		onToDateChange: onToDateChange( stateProps, dispatch ),
		onToTimePickerChange: onToTimePickerChange( stateProps, dispatch ),
		onToTimePickerClick: onToTimePickerClick( dispatch ),
	};
};

export default compose(
	withStore(),
	connect( mapStateToProps, null, mergeProps ),
)( DateTimeRangePicker );
