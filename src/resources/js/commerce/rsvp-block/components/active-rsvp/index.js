/**
 * Active RSVP display component
 *
 * @since TBD
 */
import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl, Spinner } from '@wordpress/components';
import { format } from '@wordpress/date';

/**
 * Internal dependencies
 */
import './styles.pcss';

const ActiveRSVP = ( {
	rsvpId,
	attributes,
	setAttributes,
	onUpdate,
	onDelete,
	isSaving
} ) => {
	const [ isLimitModalOpen, setIsLimitModalOpen ] = useState( false );
	const [ isWindowModalOpen, setIsWindowModalOpen ] = useState( false );
	const [ editLimit, setEditLimit ] = useState( attributes.limit || '' );
	const [ editOpenDate, setEditOpenDate ] = useState( attributes.openRsvpDate || '' );
	const [ editOpenTime, setEditOpenTime ] = useState( attributes.openRsvpTime || '12:00:00' );
	const [ editCloseDate, setEditCloseDate ] = useState( attributes.closeRsvpDate || '' );
	const [ editCloseTime, setEditCloseTime ] = useState( attributes.closeRsvpTime || '12:00:00' );

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

	// Calculate remaining capacity
	const remaining = limit ? Math.max( 0, parseInt( limit, 10 ) - goingCount ) : null;

	// Format dates for display
	const formatDateRange = () => {
		if ( ! openRsvpDate || ! closeRsvpDate ) {
			return __( 'Date not set', 'event-tickets' );
		}
		
		const openMonth = format( 'n', new Date( openRsvpDate ) );
		const openDay = format( 'j', new Date( openRsvpDate ) );
		const openYear = format( 'y', new Date( openRsvpDate ) );
		
		const closeMonth = format( 'n', new Date( closeRsvpDate ) );
		const closeDay = format( 'j', new Date( closeRsvpDate ) );
		const closeYear = format( 'y', new Date( closeRsvpDate ) );
		
		return `${openMonth}/${openDay}/${openYear} - ${closeMonth}/${closeDay}/${closeYear}`;
	};

	const handleOpenLimitModal = () => {
		// Reset edit value to current value
		setEditLimit( limit || '' );
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
		const updates = {
			limit: editLimit
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
			<div className="tec-rsvp-block__active-header">
				<h3>{ __( 'RSVP', 'event-tickets' ) }</h3>
			</div>

			<div className="tec-rsvp-block__active-content">
				{/* Attendance Statistics */}
				<div className="tec-rsvp-block__stats-section">
					<div className="tec-rsvp-block__main-stat">
						<span className="tec-rsvp-block__stat-number">{ goingCount }</span>
						<span className="tec-rsvp-block__stat-label">{ __( 'Going', 'event-tickets' ) }</span>
					</div>
					
					<a href="#" className="tec-rsvp-block__view-attendees">
						{ __( 'View Attendees', 'event-tickets' ) }
					</a>
				</div>

				{/* Action Buttons */}
				<div className="tec-rsvp-block__action-buttons">
					<Button variant="primary" className="tec-rsvp-block__going-btn">
						{ __( 'Going', 'event-tickets' ) }
					</Button>
					{ showNotGoingOption && (
						<Button variant="secondary" className="tec-rsvp-block__cant-go-btn">
							{ __( "Can't go", 'event-tickets' ) }
						</Button>
					) }
				</div>

				{/* Additional Stats with Edit */}
				<div className="tec-rsvp-block__additional-stats">
					<div className="tec-rsvp-block__stat-with-edit">
						<div>
							{ remaining !== null ? (
								<div className="tec-rsvp-block__stat-line">
									<span className="tec-rsvp-block__stat-value">{ remaining }</span>
									<span className="tec-rsvp-block__stat-text">{ __( 'Remaining', 'event-tickets' ) }</span>
								</div>
							) : (
								<div className="tec-rsvp-block__stat-line">
									<span className="tec-rsvp-block__stat-text">{ __( 'Unlimited capacity', 'event-tickets' ) }</span>
								</div>
							) }
						</div>
						<Button
							variant="link"
							className="tec-rsvp-block__edit-btn"
							onClick={ handleOpenLimitModal }
							aria-label={ __( 'Edit RSVP limit', 'event-tickets' ) }
						>
							✏️
						</Button>
					</div>
					{ showNotGoingOption && (
						<div className="tec-rsvp-block__stat-line">
							<span className="tec-rsvp-block__stat-value">{ notGoingCount }</span>
							<span className="tec-rsvp-block__stat-text">{ __( 'Not going', 'event-tickets' ) }</span>
						</div>
					) }
				</div>

				{/* RSVP Window */}
				<div className="tec-rsvp-block__window-section">
					<div className="tec-rsvp-block__window-header">
						<span className="tec-rsvp-block__window-label">
							{ __( 'RSVP Window', 'event-tickets' ) }
						</span>
						<Button
							variant="link"
							className="tec-rsvp-block__edit-btn"
							onClick={ handleOpenWindowModal }
							aria-label={ __( 'Edit RSVP window', 'event-tickets' ) }
						>
							✏️
						</Button>
					</div>
					<div className="tec-rsvp-block__window-dates">
						{ formatDateRange() }
					</div>
				</div>

				{/* Remove RSVP */}
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
							placeholder={ __( 'Leave blank for unlimited', 'event-tickets' ) }
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
	isSaving: PropTypes.bool
};

ActiveRSVP.defaultProps = {
	onUpdate: null,
	onDelete: null,
	isSaving: false
};

export default ActiveRSVP;