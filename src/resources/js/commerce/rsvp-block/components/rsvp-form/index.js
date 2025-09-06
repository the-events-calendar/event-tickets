/**
 * External dependencies
 */
import React, { useMemo } from 'react';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import {
	TextControl,
	BaseControl,
	DateTimePicker,
	Dropdown,
	Button,
	PanelBody,
	PanelRow,
	ToggleControl,
	Spinner,
	Slot,
	Fill
} from '@wordpress/components';
import { format } from '@wordpress/date';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import './styles.pcss';

/**
 * Slot for Event Tickets Plus to add attendee meta fields.
 *
 * @since TBD
 */
export const RSVPAttendeeMetaSlot = ( { fillProps } ) => (
	<Slot name="RSVPAttendeeMetaSlot" fillProps={ fillProps } />
);

/**
 * Fill for Event Tickets Plus to add attendee meta fields.
 *
 * @since TBD
 */
export const RSVPAttendeeMetaFill = ( { children } ) => (
	<Fill name="RSVPAttendeeMetaSlot">{ children }</Fill>
);

const RSVPForm = ( {
	rsvpId = null,
	limit = '',
	onLimitChange,
	attributes = {},
	setAttributes = () => {},
	onAttributeChange = () => {},
	isActive = false,
	onSave = () => {},
	isSaving = false
} ) => {
	const {
		openRsvpDate,
		openRsvpTime,
		closeRsvpDate,
		closeRsvpTime,
		attendeeInfoCollectionEnabled,
		showNotGoingOption,
		goingCount,
		notGoingCount
	} = attributes;

	// Validate limit input
	const handleLimitChange = ( value ) => {
		// Allow empty string for unlimited
		if ( value === '' ) {
			onLimitChange( '' );
			return;
		}

		// Ensure positive integer
		const numValue = parseInt( value, 10 );
		if ( ! isNaN( numValue ) && numValue >= 0 ) {
			onLimitChange( String( numValue ) );
		}
	};

	// Combine date and time for DateTime picker
	const openDateTime = useMemo( () => {
		if ( openRsvpDate ) {
			return new Date( `${openRsvpDate}T${openRsvpTime}` );
		}
		return new Date();
	}, [ openRsvpDate, openRsvpTime ] );

	const closeDateTime = useMemo( () => {
		if ( closeRsvpDate ) {
			return new Date( `${closeRsvpDate}T${closeRsvpTime}` );
		}
		const tomorrow = new Date();
		tomorrow.setDate( tomorrow.getDate() + 1 );
		return tomorrow;
	}, [ closeRsvpDate, closeRsvpTime ] );

	// Handle date/time changes
	const handleOpenDateTimeChange = ( dateTime ) => {
		if ( dateTime ) {
			const date = dateTime.split( 'T' )[ 0 ];
			const time = dateTime.split( 'T' )[ 1 ] || '00:00:00';
			onAttributeChange( {
				openRsvpDate: date,
				openRsvpTime: time
			} );
		}
	};

	const handleCloseDateTimeChange = ( dateTime ) => {
		if ( dateTime ) {
			const date = dateTime.split( 'T' )[ 0 ];
			const time = dateTime.split( 'T' )[ 1 ] || '00:00:00';
			onAttributeChange( {
				closeRsvpDate: date,
				closeRsvpTime: time
			} );
		}
	};

	// Format date for display
	const formatDateTime = ( date ) => {
		if ( ! date ) return __( 'Select date & time', 'event-tickets' );
		return format( 'F j, Y g:i a', date );
	};

	// Calculate remaining capacity
	const remaining = limit ? Math.max( 0, parseInt( limit, 10 ) - goingCount ) : null;

	return (
		<div className="tec-rsvp-block__form-wrapper">
			<div className="tec-rsvp-block__form-header">
				<h3>{ isActive ? __( 'RSVP', 'event-tickets' ) : __( 'Add RSVP', 'event-tickets' ) }</h3>

				{ isActive && (
					<div className="tec-rsvp-block__stats">
						<span className="tec-rsvp-block__stat">
							{ __( 'Going:', 'event-tickets' ) } { goingCount }
						</span>
						{ remaining !== null && (
							<span className="tec-rsvp-block__stat">
								{ __( 'Remaining:', 'event-tickets' ) } { remaining }
							</span>
						) }
						{ showNotGoingOption && (
							<span className="tec-rsvp-block__stat">
								{ __( 'Not Going:', 'event-tickets' ) } { notGoingCount }
							</span>
						) }
					</div>
				) }
			</div>

			<div className="tec-rsvp-block__form-fields">
				{/* Hidden RSVP ID field */}
				<input
					type="hidden"
					name="rsvp_id"
					value={ rsvpId || '' }
				/>

				{/* Limit field */}
				<div className="tec-rsvp-block__field">
					<label htmlFor="tec-rsvp-limit">{ __( 'Limit:', 'event-tickets' ) }</label>
					<TextControl
						id="tec-rsvp-limit"
						type="text"
						value={ limit }
						onChange={ handleLimitChange }
						placeholder={ __( 'Leave blank for unlimited', 'event-tickets' ) }
						min="0"
						step="1"
						disabled={ isSaving }
					/>
				</div>

				{/* Open RSVP Date/Time */}
				<div className="tec-rsvp-block__datetime-row">
					<label>{ __( 'Open RSVP:', 'event-tickets' ) }</label>
					<div className="tec-rsvp-block__datetime-inputs">
						<input
							type="date"
							value={ openRsvpDate || '' }
							onChange={ ( e ) => onAttributeChange( { openRsvpDate: e.target.value } ) }
							className="tec-rsvp-block__date-input"
							disabled={ isSaving }
						/>
						<span className="tec-rsvp-block__datetime-at">{ __( 'at', 'event-tickets' ) }</span>
						<input
							type="time"
							value={ openRsvpTime ? openRsvpTime.substring(0, 5) : '12:00' }
							onChange={ ( e ) => onAttributeChange( { openRsvpTime: e.target.value + ':00' } ) }
							className="tec-rsvp-block__time-input"
							disabled={ isSaving }
						/>
					</div>
				</div>

				{/* Close RSVP Date/Time */}
				<div className="tec-rsvp-block__datetime-row">
					<label>{ __( 'Close RSVP:', 'event-tickets' ) }</label>
					<div className="tec-rsvp-block__datetime-inputs">
						<input
							type="date"
							value={ closeRsvpDate || '' }
							onChange={ ( e ) => onAttributeChange( { closeRsvpDate: e.target.value } ) }
							className="tec-rsvp-block__date-input"
							disabled={ isSaving }
						/>
						<span className="tec-rsvp-block__datetime-at">{ __( 'at', 'event-tickets' ) }</span>
						<input
							type="time"
							value={ closeRsvpTime ? closeRsvpTime.substring(0, 5) : '12:00' }
							onChange={ ( e ) => onAttributeChange( { closeRsvpTime: e.target.value + ':00' } ) }
							className="tec-rsvp-block__time-input"
							disabled={ isSaving }
						/>
					</div>
				</div>

				{/* 
				 * Slot for Event Tickets Plus to add attendee meta fields
				 * This allows ET+ to inject custom fields for collecting attendee information
				 * 
				 * @since TBD
				 */}
				<RSVPAttendeeMetaSlot
					fillProps={ {
						rsvpId,
						attributes,
						setAttributes,
						onAttributeChange,
						isSaving
					} }
				/>
				
				{/* 
				 * Filter to allow additional fields to be added to the RSVP form
				 * This provides an alternative hook point for extensions
				 * 
				 * @since TBD
				 */}
				{ applyFilters(
					'tec.tickets.commerce.rsvp.formFields',
					null,
					{
						rsvpId,
						attributes,
						setAttributes,
						onAttributeChange,
						isSaving
					}
				) }
				
				{ rsvpId && (
					<div className="tec-rsvp-block__form-actions">
						<Button
							variant="primary"
							onClick={ onSave }
							disabled={ isSaving }
						>
							{ isSaving ? (
								<>
									<Spinner />
									{ __( 'Saving...', 'event-tickets' ) }
								</>
							) : (
								__( 'Update RSVP', 'event-tickets' )
							) }
						</Button>
					</div>
				) }
			</div>
		</div>
	);
};

RSVPForm.propTypes = {
	rsvpId: PropTypes.oneOfType( [
		PropTypes.string,
		PropTypes.number,
	] ),
	limit: PropTypes.string,
	onLimitChange: PropTypes.func.isRequired,
	attributes: PropTypes.object,
	setAttributes: PropTypes.func,
	onAttributeChange: PropTypes.func,
	isActive: PropTypes.bool,
	onSave: PropTypes.func,
	isSaving: PropTypes.bool,
};

export default RSVPForm;
