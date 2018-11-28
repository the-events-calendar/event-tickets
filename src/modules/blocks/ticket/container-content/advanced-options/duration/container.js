/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';
import moment from 'moment';

/**
 * Internal dependencies
 */
import Template from './template';
import { withStore } from '@moderntribe/common/hoc';
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';
import {
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';

const onFromDateChange = ( dispatch, ownProps ) => ( date, modifiers, dayPickerInput ) => {
	const { blockId } = ownProps;
	const startDateMoment = date ? moment( date ) : undefined;
	const startDate = date ? momentUtil.toDatabaseDate( startDateMoment ) : '';
	dispatch( actions.setTicketTempStartDate( blockId, startDate ) );
	dispatch( actions.setTicketTempStartDateInput( blockId, dayPickerInput.state.value ) );
	dispatch( actions.setTicketTempStartDateMoment( blockId, startDateMoment ) );
	dispatch( actions.setTicketHasChanges( blockId, true ) );
};

const onFromTimePickerChange = ( dispatch, ownProps ) => ( e ) => {
	const { blockId } = ownProps;
	const startTime = e.target.value;
	if ( startTime ) {
		dispatch( actions.setTicketTempStartTime( blockId, `${ startTime }:00` ) );
		dispatch( actions.setTicketHasChanges( blockId, true ) );
	}
};

const onFromTimePickerClick = ( dispatch, ownProps ) => ( value, onClose ) => {
	const { blockId } = ownProps;
	const startTime = timeUtil.fromSeconds( value, timeUtil.TIME_FORMAT_HH_MM );
	dispatch( actions.setTicketTempStartTime( blockId, `${ startTime }:00` ) );
	dispatch( actions.setTicketHasChanges( blockId, true ) );
	onClose();
};

const onToDateChange = ( dispatch, ownProps ) => ( date, modifiers, dayPickerInput ) => {
	const { blockId } = ownProps;
	const endDateMoment = date ? moment( date ) : undefined;
	const endDate = date ? momentUtil.toDatabaseDate( endDateMoment ) : '';
	dispatch( actions.setTicketTempEndDate( blockId, endDate ) );
	dispatch( actions.setTicketTempEndDateInput( blockId, dayPickerInput.state.value ) );
	dispatch( actions.setTicketTempEndDateMoment( blockId, endDateMoment ) );
	dispatch( actions.setTicketHasChanges( blockId, true ) );
};

const onToTimePickerChange = ( dispatch, ownProps ) => ( e ) => {
	const { blockId } = ownProps;
	const endTime = e.target.value;
	if ( endTime ) {
		dispatch( actions.setTicketTempEndTime( blockId, `${ endTime }:00` ) );
		dispatch( actions.setTicketHasChanges( blockId, true ) );
	}
};

const onToTimePickerClick = ( dispatch, ownProps ) => ( value, onClose ) => {
	const { blockId } = ownProps;
	const endTime = timeUtil.fromSeconds( value, timeUtil.TIME_FORMAT_HH_MM );
	dispatch( actions.setTicketTempEndTime( blockId, `${ endTime }:00` ) );
	dispatch( actions.setTicketHasChanges( blockId, true ) );
	onClose();
};

const mapStateToProps = ( state, ownProps ) => {
	const isDisabled = selectors.isTicketDisabled( state, ownProps );

	return {
		fromDate: selectors.getTicketTempStartDateInput( state, ownProps ),
		fromDateDisabled: isDisabled,
		fromTime: selectors.getTicketTempStartTimeNoSeconds( state, ownProps ),
		fromTimeDisabled: isDisabled,
		isSameDay: momentUtil.isSameDay(
			selectors.getTicketTempStartDateMoment( state, ownProps ),
			selectors.getTicketTempEndDateMoment( state, ownProps ),
		),
		toDate: selectors.getTicketTempEndDateInput( state, ownProps ),
		toDateDisabled: isDisabled,
		toTime: selectors.getTicketTempEndTimeNoSeconds( state, ownProps ),
		toTimeDisabled: isDisabled,
	};
};

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onFromDateChange: onFromDateChange( dispatch, ownProps ),
	onFromTimePickerChange: onFromTimePickerChange( dispatch, ownProps ),
	onFromTimePickerClick: onFromTimePickerClick( dispatch, ownProps ),
	onToDateChange: onToDateChange( dispatch, ownProps ),
	onToTimePickerChange: onToTimePickerChange( dispatch, ownProps ),
	onToTimePickerClick: onToTimePickerClick( dispatch, ownProps ),
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
