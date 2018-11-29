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
	const startDateMoment = selectors.getRSVPTempStartDateMoment( state );
	const endDateMoment = selectors.getRSVPTempEndDateMoment( state );
	return momentUtil.isSameDay( startDateMoment, endDateMoment );
};

const onFromDateChange = ( stateProps, dispatch ) => ( date, modifiers, dayPickerInput ) => {
	/* TODO: prevent onchange to type/select a date after toDate */
	const startDateMoment = date ? moment( date ) : undefined;
	const startDate = date ? momentUtil.toDatabaseDate( startDateMoment ) : '';
	dispatch( actions.setRSVPTempStartDate( startDate ) );
	dispatch( actions.setRSVPTempStartDateInput( dayPickerInput.state.value ) );
	dispatch( actions.setRSVPTempStartDateMoment( startDateMoment ) );
	dispatch( actions.setRSVPHasChanges( true ) );
};

const onFromTimePickerChange = ( stateProps, dispatch ) => ( e ) => {
	/* TODO: prevent change to a time out of range */
	const startTime = e.target.value;
	if ( startTime ) {
		dispatch( actions.setRSVPTempStartTime( `${ startTime }:00` ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	}
};

const onFromTimePickerClick = ( dispatch ) => ( value, onClose ) => {
	const startTime = timeUtil.fromSeconds( value, timeUtil.TIME_FORMAT_HH_MM );
	dispatch( actions.setRSVPTempStartTime( `${ startTime }:00` ) );
	dispatch( actions.setRSVPHasChanges( true ) );
	onClose();
};

const onToDateChange = ( stateProps, dispatch ) => ( date, modifiers, dayPickerInput ) => {
	/* TODO: prevent onchange to type/select a date before fromDate */
	const endDateMoment = date ? moment( date ) : undefined;
	const endDate = date ? momentUtil.toDatabaseDate( endDateMoment ) : '';
	dispatch( actions.setRSVPTempEndDate( endDate ) );
	dispatch( actions.setRSVPTempEndDateInput( dayPickerInput.state.value ) );
	dispatch( actions.setRSVPTempEndDateMoment( endDateMoment ) );
	dispatch( actions.setRSVPHasChanges( true ) );
};

const onToTimePickerChange = ( stateProps, dispatch ) => ( e ) => {
	/* TODO: prevent change to a time out of range */
	const endTime = e.target.value;
	if ( endTime ) {
		dispatch( actions.setRSVPTempEndTime( `${ endTime }:00` ) );
		dispatch( actions.setRSVPHasChanges( true ) );
	}
};

const onToTimePickerClick = ( dispatch ) => ( value, onClose ) => {
	const endTime = timeUtil.fromSeconds( value, timeUtil.TIME_FORMAT_HH_MM );
	dispatch( actions.setRSVPTempEndTime( `${ endTime }:00` ) );
	dispatch( actions.setRSVPHasChanges( true ) );
	onClose();
};

const mapStateToProps = ( state ) => {
	const isDisabled = selectors.getRSVPIsLoading( state )
		|| selectors.getRSVPSettingsOpen( state );

	return {
		fromDate: selectors.getRSVPTempStartDateInput( state ),
		fromDateDisabled: isDisabled,
		fromTime: selectors.getRSVPTempStartTimeNoSeconds( state ),
		fromTimeDisabled: isDisabled,
		isSameDay: getIsSameDay( state ),
		toDate: selectors.getRSVPTempEndDateInput( state ),
		toDateDisabled: isDisabled,
		toTime: selectors.getRSVPTempEndTimeNoSeconds( state ),
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
