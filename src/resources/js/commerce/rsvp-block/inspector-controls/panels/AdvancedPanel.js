/**
 * Advanced panel for RSVP block inspector controls
 *
 * @since TBD
 */
import { RSVPAdvancedFill } from '../slots';
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Advanced panel component
 *
 * @since TBD
 *
 * @param {Object}   props                 Component props.
 * @param {Object}   props.attributes      Block attributes.
 * @param {Function} props.setAttributes   Function to update attributes.
 * @param {boolean}  props.isLoading       Whether data is loading.
 * @param {Object}   props.error           Error object if any.
 * @param {Object}   props.rsvpData        Current RSVP data.
 * @param {Object}   props.updateMutation  React Query mutation object.
 *
 * @return {JSX.Element} The advanced panel component.
 */
export function AdvancedPanel() {
	return (
		<RSVPAdvancedFill>
			{ ( { attributes, setAttributes, isLoading } ) => {
				const { showNotGoingOption } = attributes || {};

				const handleNotGoingToggle = ( value ) => {
					setAttributes( { showNotGoingOption: value } );
				};

				return (
					<>
						<ToggleControl
							label={ __( 'Show "Can\'t go" option', 'event-tickets' ) }
							help={ __(
								'Allow users to indicate they cannot attend the event',
								'event-tickets'
							) }
							checked={ showNotGoingOption }
							onChange={ handleNotGoingToggle }
							disabled={ isLoading }
						/>
						{ /* Additional advanced options can be added here */ }
					</>
				);
			} }
		</RSVPAdvancedFill>
	);
}