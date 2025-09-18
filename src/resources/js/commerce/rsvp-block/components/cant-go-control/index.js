/**
 * Can't Go Control component for RSVP block
 *
 * @since TBD
 */

import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import { useUpdateRSVP } from '../../api/hooks';

/**
 * Can't Go toggle control component.
 *
 * @since TBD
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {boolean}  props.isLoading     Whether data is loading.
 *
 * @return {JSX.Element} The Can't Go toggle control.
 */
const CantGoControl = ( { attributes, setAttributes, isLoading, refetchRsvp, onUpdateStart, onUpdateEnd } ) => {
	const { showNotGoingOption: showNotGoingOptionFromAttributes, rsvpId } = attributes || {};
	const updateMutation = useUpdateRSVP();

	// Use local state to track the value for immediate UI updates
	const [ showNotGoingOption, setShowNotGoingOption ] = useState( showNotGoingOptionFromAttributes || false );
	const [ isUpdating, setIsUpdating ] = useState( false );

	// Sync local state with attributes when they change (but not while updating)
	useEffect( () => {
		if ( ! isUpdating ) {
			setShowNotGoingOption( showNotGoingOptionFromAttributes || false );
		}
	}, [ showNotGoingOptionFromAttributes, isUpdating ] );

	/**
	 * Handle toggle change for Can't Go option.
	 *
	 * @since TBD
	 *
	 * @param {boolean} value The new value.
	 */
	const handleChange = async ( value ) => {
		// Set updating flag to prevent sync conflicts
		setIsUpdating( true );

		// Notify parent about update start
		if ( onUpdateStart ) {
			onUpdateStart();
		}

		// Update local state immediately for UI responsiveness
		setShowNotGoingOption( value );

		// Update the block attribute
		setAttributes( { showNotGoingOption: value } );

		// If we have an RSVP ID, save to the server.
		if ( rsvpId ) {
			// Get ET+ field values to preserve them
			const showAttendees = attributes?.showAttendees ?? false;
			const waitlistBeforeSale = attributes?.waitlistBeforeSale ?? false;
			const waitlistSoldOut = attributes?.waitlistSoldOut ?? false;

			try {
				await updateMutation.mutateAsync( {
					rsvpId,
					// Pass current RSVP values to avoid sending undefined
					limit: attributes?.limit,
					openRsvpDate: attributes?.openRsvpDate,
					openRsvpTime: attributes?.openRsvpTime,
					closeRsvpDate: attributes?.closeRsvpDate,
					closeRsvpTime: attributes?.closeRsvpTime,
					showNotGoingOption: value,
					// Include ET+ fields to preserve them
					additionalData: {
						tec_tickets_rsvp_show_attendees: showAttendees ? 'yes' : 'no',
						tec_tickets_rsvp_waitlist_before_sale: waitlistBeforeSale ? 'yes' : 'no',
						tec_tickets_rsvp_waitlist_sold_out: waitlistSoldOut ? 'yes' : 'no',
					},
				} );

				// Trigger refetch to update the rsvpData with the new values
				if ( refetchRsvp ) {
					await refetchRsvp();
				}
			} catch ( error ) {
				console.error( 'Failed to update Can\'t Go option:', error );
				// Revert both local state and attribute on error
				setShowNotGoingOption( ! value );
				setAttributes( { showNotGoingOption: ! value } );
			} finally {
				// Clear updating flag after a delay to let the refetch complete
				setTimeout( () => {
					setIsUpdating( false );
					if ( onUpdateEnd ) {
						onUpdateEnd();
					}
				}, 500 );
			}
		} else {
			setIsUpdating( false );
			if ( onUpdateEnd ) {
				onUpdateEnd();
			}
		}
	};

	return (
		<ToggleControl
			label={ __( 'Enable "Can\'t Go" responses', 'event-tickets' ) }
			checked={ showNotGoingOption }
			onChange={ handleChange }
			help={ __( 'Allow users to indicate they cannot attend', 'event-tickets' ) }
			disabled={ isLoading }
		/>
	);
};

export default CantGoControl;