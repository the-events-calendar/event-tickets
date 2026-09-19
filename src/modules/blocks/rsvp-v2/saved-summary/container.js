/**
 * External dependencies
 */
import PropTypes from 'prop-types';
import React, { useCallback, useRef, useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

/**
 * Internal dependencies
 */
import { withStore } from '@moderntribe/common/hoc';
import { actions, selectors, thunks } from '../../../data/blocks/rsvp-v2';
import { useListenForCloseOverlays } from '../../rsvp-shared/utils/close-overlays';
import RSVPLimitPopover from '../edit-popovers/limit-popover';
import '../edit-popovers/style.pcss';
import RSVPWindowPopover from '../edit-popovers/window-popover';
import RSVPFrontendMirror from '../frontend-mirror/template';
import RSVPRsvpWindow from '../rsvp-window/template';
import { showEditAffordances as getShowEditAffordances } from '../utils/block-state';
import { formatRsvpWindow } from '../utils/format-rsvp-window';
import './style.pcss';

const buildUpdatePayload = ( state ) => ( {
	capacity: selectors.getRSVPTempCapacity( state ),
	notGoingResponses: selectors.getRSVPNotGoingResponses( state ),
	startDate: selectors.getRSVPTempStartDate( state ),
	startDateInput: selectors.getRSVPTempStartDateInput( state ),
	startDateMoment: selectors.getRSVPTempStartDateMoment( state ),
	endDate: selectors.getRSVPTempEndDate( state ),
	endDateInput: selectors.getRSVPTempEndDateInput( state ),
	endDateMoment: selectors.getRSVPTempEndDateMoment( state ),
	startTime: selectors.getRSVPTempStartTime( state ),
	endTime: selectors.getRSVPTempEndTime( state ),
	startTimeInput: selectors.getRSVPTempStartTimeInput( state ),
	endTimeInput: selectors.getRSVPTempEndTimeInput( state ),
	id: selectors.getRSVPId( state ),
} );

const RSVPSavedSummary = ( {
	available,
	dateRange,
	goingCount,
	hasDurationError,
	isLoading,
	isSelected,
	notGoingCount,
	onCancelLimitEdit,
	onCancelWindowEdit,
	onOpenLimitEdit,
	onOpenWindowEdit,
	onSaveLimit,
	onSaveWindow,
	onTempCapacityChange,
	showEditAffordances,
	showNotGoing,
	tempCapacity,
	title,
} ) => {
	const [ isLimitOpen, setIsLimitOpen ] = useState( false );
	const [ isWindowOpen, setIsWindowOpen ] = useState( false );
	const remainingRef = useRef( null );
	const windowAnchorRef = useRef( null );
	const suppressWindowOpenRef = useRef( false );

	const handleOpenLimit = useCallback( () => {
		onOpenLimitEdit();
		setIsLimitOpen( true );
	}, [ onOpenLimitEdit ] );

	const handleOpenWindow = useCallback( () => {
		if ( suppressWindowOpenRef.current || isWindowOpen ) {
			return;
		}

		onOpenWindowEdit();
		setIsWindowOpen( true );
	}, [ isWindowOpen, onOpenWindowEdit ] );

	const handleCancelWindow = useCallback( () => {
		onCancelWindowEdit();
		setIsWindowOpen( false );
		suppressWindowOpenRef.current = true;
		window.setTimeout( () => {
			suppressWindowOpenRef.current = false;
		}, 0 );
	}, [ onCancelWindowEdit ] );

	const handleCancelLimit = useCallback( () => {
		onCancelLimitEdit();
		setIsLimitOpen( false );
	}, [ onCancelLimitEdit ] );

	const handleSaveLimit = useCallback( async () => {
		await onSaveLimit();
		setIsLimitOpen( false );
	}, [ onSaveLimit ] );

	const handleSaveWindow = useCallback( async () => {
		await onSaveWindow();
		setIsWindowOpen( false );
		suppressWindowOpenRef.current = true;
		window.setTimeout( () => {
			suppressWindowOpenRef.current = false;
		}, 0 );
	}, [ onSaveWindow ] );

	const closeLocalOverlays = useCallback( () => {
		if ( isLimitOpen ) {
			handleCancelLimit();
		}

		if ( isWindowOpen ) {
			handleCancelWindow();
		}
	}, [ handleCancelLimit, handleCancelWindow, isLimitOpen, isWindowOpen ] );

	useListenForCloseOverlays( closeLocalOverlays );

	return (
		<div className="tribe-editor__rsvp-saved-summary">
			<RSVPFrontendMirror
				available={ available }
				goingCount={ goingCount }
				notGoingCount={ notGoingCount }
				onEditRemaining={ handleOpenLimit }
				remainingRef={ remainingRef }
				showEditAffordances={ showEditAffordances }
				showNotGoing={ showNotGoing }
				title={ title }
			/>

			{ isSelected && (
				<div className="tribe-editor__rsvp-saved-summary__meta">
					<RSVPRsvpWindow
						anchorRef={ windowAnchorRef }
						dateRange={ dateRange }
						isWindowOpen={ isWindowOpen }
						onEditWindow={ handleOpenWindow }
						showEditAffordances={ showEditAffordances }
					/>
				</div>
			) }

			<RSVPLimitPopover
				anchorRef={ remainingRef }
				isOpen={ isLimitOpen }
				isSaving={ isLoading }
				onCancel={ handleCancelLimit }
				onSave={ handleSaveLimit }
				onTempCapacityChange={ onTempCapacityChange }
				tempCapacity={ tempCapacity }
			/>

			<RSVPWindowPopover
				anchorRef={ windowAnchorRef }
				hasDurationError={ hasDurationError }
				isOpen={ isWindowOpen }
				isSaving={ isLoading }
				onCancel={ handleCancelWindow }
				onSave={ handleSaveWindow }
			/>
		</div>
	);
};

RSVPSavedSummary.propTypes = {
	available: PropTypes.number,
	dateRange: PropTypes.string,
	goingCount: PropTypes.number,
	hasDurationError: PropTypes.bool,
	isLoading: PropTypes.bool,
	isSelected: PropTypes.bool,
	notGoingCount: PropTypes.number,
	onCancelLimitEdit: PropTypes.func.isRequired,
	onCancelWindowEdit: PropTypes.func.isRequired,
	onOpenLimitEdit: PropTypes.func.isRequired,
	onOpenWindowEdit: PropTypes.func.isRequired,
	onSaveLimit: PropTypes.func.isRequired,
	onSaveWindow: PropTypes.func.isRequired,
	onTempCapacityChange: PropTypes.func.isRequired,
	showEditAffordances: PropTypes.bool,
	showNotGoing: PropTypes.bool,
	tempCapacity: PropTypes.string,
	title: PropTypes.string,
};

const mapStateToProps = ( state, ownProps ) => {
	const startDateMoment = selectors.getRSVPStartDateMoment( state );
	const endDateMoment = selectors.getRSVPEndDateMoment(state);

	return {
		available: selectors.getRSVPAvailable( state ),
		dateRange: formatRsvpWindow( startDateMoment, endDateMoment ),
		goingCount: selectors.getRSVPGoingCount( state ),
		hasDurationError: selectors.getRSVPHasDurationError( state ),
		isLoading: selectors.getRSVPIsLoading( state ),
		notGoingCount: selectors.getRSVPNotGoingCount( state ),
		showEditAffordances: getShowEditAffordances( {
			created: selectors.getRSVPCreated( state ),
			isSelected: ownProps.isSelected,
		} ),
		showNotGoing: selectors.getRSVPNotGoingResponses( state ),
		tempCapacity: selectors.getRSVPTempCapacity( state ),
		title: selectors.getRSVPTitle( state ),
		state,
	};
};

const mapDispatchToProps = ( dispatch ) => ( {
	onTempCapacityChange: ( e ) => {
		dispatch( actions.setRSVPTempCapacity( e.target.value ) );
	},
	dispatch,
} );

const mergeProps = ( stateProps, dispatchProps, ownProps ) => {
	const { state, ...restStateProps } = stateProps;
	const { dispatch, ...restDispatchProps } = dispatchProps;

	const resetTempCapacity = () => {
		dispatch( actions.setRSVPTempCapacity( selectors.getRSVPCapacity( state ) ) );
	};

	const openLimitEdit = () => {
		dispatch( actions.setRSVPTempCapacity( selectors.getRSVPCapacity( state ) ) );
	};

	const openWindowEdit = () => {
		dispatch(
			actions.setRSVPTempDetails( {
				tempCapacity: selectors.getRSVPCapacity( state ),
				tempNotGoingResponses: selectors.getRSVPNotGoingResponses( state ),
				tempStartDate: selectors.getRSVPStartDate( state ),
				tempStartDateInput: selectors.getRSVPStartDateInput( state ),
				tempStartDateMoment: selectors.getRSVPStartDateMoment( state ),
				tempEndDate: selectors.getRSVPEndDate( state ),
				tempEndDateInput: selectors.getRSVPEndDateInput( state ),
				tempEndDateMoment: selectors.getRSVPEndDateMoment( state ),
				tempStartTime: selectors.getRSVPStartTime( state ),
				tempEndTime: selectors.getRSVPEndTime( state ),
				tempStartTimeInput: selectors.getRSVPStartTimeInput( state ),
				tempEndTimeInput: selectors.getRSVPEndTimeInput( state ),
			} )
		);
	};

	const resetTempDates = () => {
		dispatch(
			actions.setRSVPTempDetails( {
				tempCapacity: selectors.getRSVPCapacity( state ),
				tempNotGoingResponses: selectors.getRSVPNotGoingResponses( state ),
				tempStartDate: selectors.getRSVPStartDate( state ),
				tempStartDateInput: selectors.getRSVPStartDateInput( state ),
				tempStartDateMoment: selectors.getRSVPStartDateMoment( state ),
				tempEndDate: selectors.getRSVPEndDate( state ),
				tempEndDateInput: selectors.getRSVPEndDateInput( state ),
				tempEndDateMoment: selectors.getRSVPEndDateMoment( state ),
				tempStartTime: selectors.getRSVPStartTime( state ),
				tempEndTime: selectors.getRSVPEndTime( state ),
				tempStartTimeInput: selectors.getRSVPStartTimeInput( state ),
				tempEndTimeInput: selectors.getRSVPEndTimeInput( state ),
			} )
		);
	};

	return {
		...ownProps,
		...restStateProps,
		...restDispatchProps,
		onCancelLimitEdit: resetTempCapacity,
		onCancelWindowEdit: resetTempDates,
		onOpenLimitEdit: openLimitEdit,
		onOpenWindowEdit: openWindowEdit,
		onSaveLimit: () => dispatch( thunks.updateRSVP( buildUpdatePayload( state ) ) ),
		onSaveWindow: () => dispatch( thunks.updateRSVP( buildUpdatePayload( state ) ) ),
	};
};

export default compose( withStore(), connect( mapStateToProps, mapDispatchToProps, mergeProps ) )( RSVPSavedSummary );
