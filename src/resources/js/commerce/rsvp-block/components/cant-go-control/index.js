/**
 * Can't Go Control component for RSVP block
 *
 * @since TBD
 */

import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
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
const CantGoControl = ( { attributes, setAttributes, isLoading } ) => {
	const { showNotGoingOption, rsvpId } = attributes || {};
	const updateMutation = useUpdateRSVP();

	/**
	 * Handle toggle change for Can't Go option.
	 *
	 * @since TBD
	 *
	 * @param {boolean} value The new value.
	 */
	const handleChange = async ( value ) => {
		// Update the block attribute optimistically.
		setAttributes( { showNotGoingOption: value } );

		// If we have an RSVP ID, save to the server.
		if ( rsvpId ) {
			try {
				await updateMutation.mutateAsync( {
					rsvpId,
					additionalData: {
						tec_tickets_rsvp_enable_cannot_go: value ? 'yes' : 'no',
					},
				} );
			} catch ( error ) {
				console.error( 'Failed to update Can\'t Go option:', error );
				// Revert on error.
				setAttributes( { showNotGoingOption: ! value } );
			}
		}
	};

	return (
		<ToggleControl
			label={ __( 'Enable "Can\'t Go" responses', 'event-tickets' ) }
			checked={ showNotGoingOption }
			onChange={ handleChange }
			help={ __( 'Allow users to indicate they cannot attend', 'event-tickets' ) }
			disabled={ isLoading || updateMutation.isLoading }
		/>
	);
};

export default CantGoControl;