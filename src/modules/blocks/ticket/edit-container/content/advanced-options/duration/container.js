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
import { withStore } from '@moderntribe/common/src/modules/hoc';
import { selectors, actions } from '@moderntribe/tickets/data/blocks/ticket';
import {
	moment as momentUtil,
	time as timeUtil,
} from '@moderntribe/common/utils';

const mapStateToProps = ( state, ownProps ) => ( {
	fromDate: selectors.getTicketStartDate( state, ownProps ),
	fromTime: selectors.getTicketStartTime( state, ownProps ),
	isSameDay: momentUtil.isSameDay(
		selectors.getTicketStartDateMoment( state, ownProps ),
		selectors.getTicketEndDateMoment( state, ownProps ),
	),
	toDate: selectors.getTicketEndDate( state, ownProps ),
	toTime: selectors.getTicketEndTime( state, ownProps ),
} );

const mapDispatchToProps = ( dispatch, ownProps ) => ( {
	onFromDateChange: ( date ) => {
		const { blockId } = ownProps;
		const momentObj = moment( date );
		dispatch( actions.setStartDate( blockId, momentUtil.toDate( momentObj ) ) );
		dispatch( actions.setTicketStartDateMoment( blockId, momentObj ) );
	},
	onFromTimePickerChange: ( e ) => {
		const { blockId } = ownProps;
		const startTime = e.target.value;
		if ( startTime ) {
			dispatch( actions.setStartTime( blockId, startTime ) );
		}
	},
	onFromTimePickerClick: ( value, onClose ) => {
		const { blockId } = ownProps;
		const endTime = timeUtil.fromSeconds( value, timeUtil.TIME_FORMAT_HH_MM );
		dispatch( actions.setStartTime( blockId, endTime ) );
		onClose();
	},
	onToDateChange: ( date ) => {
		const { blockId } = ownProps;
		const momentObj = moment( date );
		dispatch( actions.setEndDate( blockId, momentUtil.toDate( momentObj ) ) );
		dispatch( actions.setTicketEndDateMoment( blockId, momentObj ) );
		dispatch( actions.setTicketDateIsPristine( blockId, false ) );
	},
	onToTimePickerChange: ( e ) => {
		const { blockId } = ownProps;
		const endTime = e.target.value;
		if ( endTime ) {
			dispatch( actions.setEndTime( blockId, endTime ) );
			dispatch( actions.setTicketDateIsPristine( blockId, false ) );
		}
	},
	onToTimePickerClick: ( value, onClose ) => {
		const { blockId } = ownProps;
		const endTime = timeUtil.fromSeconds( value, timeUtil.TIME_FORMAT_HH_MM );
		dispatch( actions.setEndTime( blockId, endTime ) );
		dispatch( actions.setTicketDateIsPristine( blockId, false ) );
		onClose();
	},
} );

export default compose(
	withStore(),
	connect(
		mapStateToProps,
		mapDispatchToProps,
	),
)( Template );
