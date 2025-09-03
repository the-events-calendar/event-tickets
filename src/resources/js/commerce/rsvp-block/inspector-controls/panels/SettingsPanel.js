/**
 * Settings panel for RSVP block inspector controls
 *
 * @since TBD
 */
import { RSVPSettingsFill } from '../slots';
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Settings panel component
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
 * @return {JSX.Element} The settings panel component.
 */
export function SettingsPanel() {
	return (
		<RSVPSettingsFill>
			{ ( { attributes, setAttributes, isLoading } ) => {
				const { showNotGoingOption } = attributes || {};

				const handleShowNotGoingChange = ( value ) => {
					setAttributes( { showNotGoingOption: value } );
				};

				return (
					<>
						<ToggleControl
							label={ __( 'Enable "Can\'t Go" responses', 'event-tickets' ) }
							checked={ showNotGoingOption }
							onChange={ handleShowNotGoingChange }
							help={ __( 'Allow users to indicate they cannot attend', 'event-tickets' ) }
							disabled={ isLoading }
						/>
						{ /* Date fields will be added here later */ }
						{ /* Attendee info collection field will be added here later */ }
					</>
				);
			} }
		</RSVPSettingsFill>
	);
}