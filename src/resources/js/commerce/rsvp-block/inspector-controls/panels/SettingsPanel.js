/**
 * Settings panel for RSVP block inspector controls
 *
 * @since TBD
 */
import { RSVPSettingsFill } from '../slots';
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

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

				/**
				 * Filters the additional settings for the RSVP settings panel.
				 *
				 * @since TBD
				 *
				 * @param {JSX|null} additionalSettings Additional settings JSX or null.
				 * @param {Object}   props              Settings panel props.
				 *
				 * @return {JSX|null} Additional settings to render.
				 */
				const additionalSettings = applyFilters(
					'tec.tickets.commerce.rsvp.settingsPanel',
					null,
					{ attributes, setAttributes, isLoading }
				);

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
						{ additionalSettings }
					</>
				);
			} }
		</RSVPSettingsFill>
	);
}