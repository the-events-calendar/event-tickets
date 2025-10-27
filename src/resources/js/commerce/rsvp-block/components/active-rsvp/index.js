/**
 * Active RSVP display component
 *
 * @since TBD
 */
import React, { useState, useEffect, useMemo } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl, Spinner } from '@wordpress/components';
import { format } from '@wordpress/date';
import { applyFilters } from '@wordpress/hooks';
import { select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CalendarIcon as PencilIcon } from '../../assets/pencil';
import { globals } from '@moderntribe/common/utils';
import './styles.pcss';

const ActiveRSVP = ( {
	rsvpId,
	attributes,
	setAttributes,
	onUpdate = null,
	onDelete = null,
	isSaving = false,
	isSelected = false
} ) => {
	const [ isLimitModalOpen, setIsLimitModalOpen ] = useState( false );
	const [ isWindowModalOpen, setIsWindowModalOpen ] = useState( false );
	const [ editLimit, setEditLimit ] = useState( attributes.limit || '' );
	const [ editOpenDate, setEditOpenDate ] = useState( attributes.openRsvpDate || '' );
	const [ editOpenTime, setEditOpenTime ] = useState( attributes.openRsvpTime || '12:00:00' );
	const [ editCloseDate, setEditCloseDate ] = useState( attributes.closeRsvpDate || '' );
	const [ editCloseTime, setEditCloseTime ] = useState( attributes.closeRsvpTime || '12:00:00' );
	const [ attendeeInfo, setAttendeeInfo ] = useState( { content: __( 'None', 'event-tickets' ), onClick: null } );

	const {
		limit,
		openRsvpDate,
		openRsvpTime,
		closeRsvpDate,
		closeRsvpTime,
		showNotGoingOption,
		goingCount = 0,
		notGoingCount = 0
	} = attributes;

	// Calculate remaining capacity based on actual going count.
	const remaining = limit > 0 ? Math.max( 0, parseInt( limit, 10 ) - (goingCount || 0) ) : null;

	// Build attendees URL dynamically using WordPress data API.
	const attendeesUrl = useMemo( () => {
		const adminURL = globals.adminUrl();
		const postType = select( 'core/editor' )?.getCurrentPostType() || 'tribe_events';
		const postId = select( 'core/editor' )?.getCurrentPostId();

		if ( ! postId ) {
			return '#';
		}

		return `${ adminURL }edit.php?post_type=${ postType }&page=tickets-attendees&event_id=${ postId }`;
	}, [] );

	// Listen for attendee information updates from ET+.
	useEffect( () => {
		const handleAttendeeInfoUpdate = ( event ) => {
			if ( event.detail && event.detail.rsvpId === rsvpId ) {
				setAttendeeInfo( {
					content: event.detail.content || __( 'Name, Email', 'event-tickets' ),
					onClick: event.detail.onClick || null
				} );
			}
		};

		document.addEventListener( 'rsvpAttendeeInfoUpdate', handleAttendeeInfoUpdate );

		return () => {
			document.removeEventListener( 'rsvpAttendeeInfoUpdate', handleAttendeeInfoUpdate );
		};
	}, [ rsvpId ] );

	// Format dates for display
	const formatDateRange = () => {
		if ( ! openRsvpDate || ! closeRsvpDate ) {
			return __( 'Date not set', 'event-tickets' );
		}

		// Parse dates as local dates to avoid timezone conversion issues.
		// Appending 'T00:00:00' ensures the date is treated as local midnight, not UTC.
		const openMonth = format( 'n', new Date( `${openRsvpDate}T00:00:00` ) );
		const openDay = format( 'j', new Date( `${openRsvpDate}T00:00:00` ) );
		const openYear = format( 'y', new Date( `${openRsvpDate}T00:00:00` ) );

		const closeMonth = format( 'n', new Date( `${closeRsvpDate}T00:00:00` ) );
		const closeDay = format( 'j', new Date( `${closeRsvpDate}T00:00:00` ) );
		const closeYear = format( 'y', new Date( `${closeRsvpDate}T00:00:00` ) );

		return `${openMonth}/${openDay}/${openYear} - ${closeMonth}/${closeDay}/${closeYear}`;
	};

	const handleOpenLimitModal = () => {
		// Reset edit value to current value. Show blank for unlimited.
		setEditLimit( limit && limit > 0 ? limit : '' );
		setIsLimitModalOpen( true );
	};

	const handleOpenWindowModal = () => {
		// Reset edit values to current values
		setEditOpenDate( openRsvpDate || '' );
		setEditOpenTime( openRsvpTime || '12:00:00' );
		setEditCloseDate( closeRsvpDate || '' );
		setEditCloseTime( closeRsvpTime || '12:00:00' );
		setIsWindowModalOpen( true );
	};

	const handleSaveLimit = async () => {
		// Convert empty string to -1 (unlimited), or parse the integer value.
		const limitValue = editLimit === '' ? -1 : parseInt( editLimit, 10 );

		const updates = {
			limit: limitValue
		};

		setAttributes( updates );

		if ( onUpdate ) {
			await onUpdate( updates );
		}

		setIsLimitModalOpen( false );
	};

	const handleSaveWindow = async () => {
		const updates = {
			openRsvpDate: editOpenDate,
			openRsvpTime: editOpenTime,
			closeRsvpDate: editCloseDate,
			closeRsvpTime: editCloseTime
		};

		setAttributes( updates );

		if ( onUpdate ) {
			await onUpdate( updates );
		}

		setIsWindowModalOpen( false );
	};

	const handleRemoveRSVP = async () => {
		if ( window.confirm( __( 'Are you sure you want to remove this RSVP?', 'event-tickets' ) ) ) {
			if ( onDelete ) {
				await onDelete();
			}
		}
	};

	return (
		<div className="tec-rsvp-block__active-wrapper">
			<div className="tec-rsvp-block__active-content">
				{/* Main Layout: Left side content, Right side actions */}
				<div className="tec-rsvp-block__main-layout">
					<div className="tec-rsvp-block__left-content">
						{/* RSVP Title */}
						<h3 className="tec-rsvp-block__title">{ __( 'RSVP', 'event-tickets' ) }</h3>

						{/* Attendance Statistics */}
						<div className="tec-rsvp-block__stats-section">
							<div className="tec-rsvp-block__main-stat">
								<span className="tec-rsvp-block__stat-number">{ goingCount }</span>
								<a
									href={ attendeesUrl }
									className="tec-rsvp-block__view-attendees"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'View Attendees', 'event-tickets' ) }
								</a>
							</div>
							<span className="tec-rsvp-block__stat-label">{ __( 'Going', 'event-tickets' ) }</span>
						</div>

						{/* Combined Remaining and Not Going Stats */}
						<div className="tec-rsvp-block__combined-stats">
							<div className="tec-rsvp-block__stat-text">
								<span className="tec-rsvp-block__stat-value">{ remaining !== null ? remaining : '—' }</span>
								{ __( ' Remaining', 'event-tickets' ) }
								{ isSelected && (
									<Button
										variant="link"
										className="tec-rsvp-block__inline-edit-icon"
										onClick={ handleOpenLimitModal }
										aria-label={ __( 'Edit RSVP limit', 'event-tickets' ) }
									>
										{ PencilIcon }
									</Button>
								) }
							</div>
							{ showNotGoingOption && (
								<div className="tec-rsvp-block__stat-text">
									<span className="tec-rsvp-block__stat-value">{ notGoingCount }</span>
									{ __( ' Not going', 'event-tickets' ) }
								</div>
							) }
						</div>
					</div>

					{/* Right side: Action Buttons */}
					<div className="tec-rsvp-block__right-actions tribe-common">
						<Button variant="primary" className="tec-rsvp-block__going-btn tribe-common-c-btn">
							{ __( 'Going', 'event-tickets' ) }
						</Button>
						{ showNotGoingOption && (
							<a href="#" className="tec-rsvp-block__cant-go-btn">
								{ __( "Can't go", 'event-tickets' ) }
							</a>
						) }
					</div>
				</div>

				{/* RSVP Window Section with Edit Icon - only show when selected */}
				{ isSelected && (
					<div className="tec-rsvp-block__window-section tec-rsvp-block__section-hover is-selected">
						<div className="tec-rsvp-block__section-header">
							<span className="tec-rsvp-block__section-label">
								{ __( 'RSVP Window', 'event-tickets' ) }
							</span>
							<Button
								variant="link"
								className="tec-rsvp-block__edit-icon"
								onClick={ handleOpenWindowModal }
								aria-label={ __( 'Edit RSVP window', 'event-tickets' ) }
							>
								{ PencilIcon }
							</Button>
						</div>
						<div className="tec-rsvp-block__section-content">
							{ formatDateRange() }
						</div>
					</div>
				) }

				{/* Attendee Information Section - only show when selected */}
				{ isSelected && (
					<div className="tec-rsvp-block__attendee-section tec-rsvp-block__section-hover is-selected">
						<div className="tec-rsvp-block__section-header">
							<span className="tec-rsvp-block__section-label">
								{ __( 'Attendee Information', 'event-tickets' ) }
							</span>
							<Button
								variant="link"
								className="tec-rsvp-block__edit-icon"
								onClick={ attendeeInfo.onClick }
								aria-label={ __( 'Edit attendee information', 'event-tickets' ) }
							>
								{ PencilIcon }
							</Button>
						</div>
						<div className="tec-rsvp-block__section-content">
							{ attendeeInfo.content }
						</div>
					</div>
				) }

				{/* Event Tickets Plus Integration Point */}
				{ applyFilters(
					'tec.tickets.commerce.rsvp.formFields',
					null,
					{
						rsvpId,
						attributes,
						setAttributes,
						isSaving
					}
				) }

				{/* Remove RSVP - only show when selected */}
				{ isSelected && (
					<div className="tec-rsvp-block__remove-section">
						<Button
							variant="link"
							isDestructive
							className="tec-rsvp-block__remove-btn"
							onClick={ handleRemoveRSVP }
							disabled={ isSaving }
						>
							{ __( 'Remove RSVP', 'event-tickets' ) }
						</Button>
					</div>
				) }
			</div>

			{/* RSVP Limit Modal */}
			{ isLimitModalOpen && (
				<Modal
					title={ __( 'RSVP Limit', 'event-tickets' ) }
					onRequestClose={ () => setIsLimitModalOpen( false ) }
					className="tec-rsvp-block__limit-modal"
				>
					<div className="tec-rsvp-block__modal-content">
						<TextControl
							type="text"
							value={ editLimit }
							onChange={ setEditLimit }
						/>
						<p className="tec-rsvp-block__modal-help">
							{ __( 'Leave blank for unlimited', 'event-tickets' ) }
						</p>
					</div>

					<div className="tec-rsvp-block__modal-footer">
						<Button
							variant="secondary"
							onClick={ () => setIsLimitModalOpen( false ) }
							disabled={ isSaving }
						>
							{ __( 'Cancel', 'event-tickets' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleSaveLimit }
							disabled={ isSaving }
						>
							{ isSaving ? (
								<>
									<Spinner />
									{ __( 'Saving...', 'event-tickets' ) }
								</>
							) : (
								__( 'Save', 'event-tickets' )
							) }
						</Button>
					</div>
				</Modal>
			) }

			{/* RSVP Window Modal */}
			{ isWindowModalOpen && (
				<Modal
					title={ __( 'RSVP Window', 'event-tickets' ) }
					onRequestClose={ () => setIsWindowModalOpen( false ) }
					className="tec-rsvp-block__window-modal"
				>
					<div className="tec-rsvp-block__modal-content">
						{/* Open RSVP Date/Time */}
						<div className="tec-rsvp-block__modal-field">
							<label>{ __( 'Open RSVP:', 'event-tickets' ) }</label>
							<div className="tec-rsvp-block__datetime-inputs">
								<input
									type="date"
									value={ editOpenDate }
									onChange={ ( e ) => setEditOpenDate( e.target.value ) }
									className="tec-rsvp-block__date-input"
								/>
								<span className="tec-rsvp-block__datetime-at">{ __( 'at', 'event-tickets' ) }</span>
								<input
									type="time"
									value={ editOpenTime.substring( 0, 5 ) }
									onChange={ ( e ) => setEditOpenTime( e.target.value + ':00' ) }
									className="tec-rsvp-block__time-input"
								/>
							</div>
						</div>

						{/* Close RSVP Date/Time */}
						<div className="tec-rsvp-block__modal-field">
							<label>{ __( 'Close RSVP:', 'event-tickets' ) }</label>
							<div className="tec-rsvp-block__datetime-inputs">
								<input
									type="date"
									value={ editCloseDate }
									onChange={ ( e ) => setEditCloseDate( e.target.value ) }
									className="tec-rsvp-block__date-input"
								/>
								<span className="tec-rsvp-block__datetime-at">{ __( 'at', 'event-tickets' ) }</span>
								<input
									type="time"
									value={ editCloseTime.substring( 0, 5 ) }
									onChange={ ( e ) => setEditCloseTime( e.target.value + ':00' ) }
									className="tec-rsvp-block__time-input"
								/>
							</div>
						</div>
					</div>

					<div className="tec-rsvp-block__modal-footer">
						<Button
							variant="secondary"
							onClick={ () => setIsWindowModalOpen( false ) }
							disabled={ isSaving }
						>
							{ __( 'Cancel', 'event-tickets' ) }
						</Button>
						<Button
							variant="primary"
							onClick={ handleSaveWindow }
							disabled={ isSaving }
						>
							{ isSaving ? (
								<>
									<Spinner />
									{ __( 'Saving...', 'event-tickets' ) }
								</>
							) : (
								__( 'Save', 'event-tickets' )
							) }
						</Button>
					</div>
				</Modal>
			) }
		</div>
	);
};

ActiveRSVP.propTypes = {
	rsvpId: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.number
	] ).isRequired,
	attributes: PropTypes.object.isRequired,
	setAttributes: PropTypes.func.isRequired,
	onUpdate: PropTypes.func,
	onDelete: PropTypes.func,
	isSaving: PropTypes.bool,
	isSelected: PropTypes.bool
};

export default ActiveRSVP;
