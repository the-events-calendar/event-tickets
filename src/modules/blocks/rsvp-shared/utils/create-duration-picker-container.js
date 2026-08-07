/**
 * External dependencies
 */
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import DateTimeRangePicker from '../templates/duration-picker/template';
import { withStore } from '@moderntribe/common/hoc';
import { globals, moment as momentUtil } from '@moderntribe/common/utils';

/**
 * Creates a duration picker container with optional autosave support.
 *
 * @param {Object}  options             Factory options.
 * @param {Object}  options.actions     RSVP action creators.
 * @param {Object}  options.selectors   RSVP selectors.
 * @param {boolean} options.autosave    Default autosave when the prop is not passed.
 * @param {Function} options.onAutosave Called after a change when autosave is enabled.
 * @return {Function} Connected duration picker container component.
 */
export const createDurationPickerContainer = ( { actions, selectors, autosave = false, onAutosave } ) => {
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
		const autosaveEnabled = ownProps.autosave ?? autosave;

		const maybeAutosave = () => {
			if ( autosaveEnabled && onAutosave ) {
				onAutosave( dispatch );
			}
		};

		return {
			...ownProps,
			...restStateProps,
			onFromDateChange: ( date, modifiers, dayPickerInput ) => {
				dispatch(
					actions.handleRSVPStartDate( {
						date,
						dayPickerInput,
					} )
				);
				maybeAutosave();
			},
			onFromTimePickerChange: ( e ) => dispatch( actions.setRSVPTempStartTimeInput( e.target.value ) ),
			onFromTimePickerClick: ( value, onClose ) => {
				dispatch( actions.handleRSVPStartTime( value ) );
				onClose();
				maybeAutosave();
			},
			onToDateChange: ( date, modifiers, dayPickerInput ) => {
				dispatch(
					actions.handleRSVPEndDate( {
						date,
						dayPickerInput,
					} )
				);
				maybeAutosave();
			},
			onToTimePickerChange: ( e ) => dispatch( actions.setRSVPTempEndTimeInput( e.target.value ) ),
			onToTimePickerClick: ( value, onClose ) => {
				dispatch( actions.handleRSVPEndTime( value ) );
				onClose();
				maybeAutosave();
			},
			onFromTimePickerBlur: ( e ) => {
				let startTimeMoment = momentUtil.toMoment( e.target.value, momentUtil.TIME_FORMAT, false );
				if ( ! startTimeMoment.isValid() ) {
					const startTimeInput = selectors.getRSVPStartTimeInput( state );
					startTimeMoment = momentUtil.toMoment( startTimeInput, momentUtil.TIME_FORMAT, false );
				}
				const seconds = momentUtil.totalSeconds( startTimeMoment );
				dispatch( actions.handleRSVPStartTime( seconds ) );
				maybeAutosave();
			},
			onToTimePickerBlur: ( e ) => {
				let endTimeMoment = momentUtil.toMoment( e.target.value, momentUtil.TIME_FORMAT, false );
				if ( ! endTimeMoment.isValid() ) {
					const endTimeInput = selectors.getRSVPEndTimeInput( state );
					endTimeMoment = momentUtil.toMoment( endTimeInput, momentUtil.TIME_FORMAT, false );
				}
				const seconds = momentUtil.totalSeconds( endTimeMoment );
				dispatch( actions.handleRSVPEndTime( seconds ) );
				maybeAutosave();
			},
		};
	};

	return compose(
		withStore(),
		connect( mapStateToProps, mapDispatchToProps, mergeProps )
	)( DateTimeRangePicker );
};
