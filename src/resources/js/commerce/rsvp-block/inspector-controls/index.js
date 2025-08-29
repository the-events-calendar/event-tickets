/**
 * Inspector controls for RSVP block
 *
 * @since TBD
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { RSVPSettingsSlot, RSVPAdvancedSlot, RSVPExtensionsSlot } from './slots';

/**
 * Main inspector controls component
 *
 * @since TBD
 *
 * @param {Object} props Component props.
 *
 * @return {JSX.Element} The inspector controls component.
 */
export function RSVPInspectorControls( props ) {
	return (
		<InspectorControls>
			<PanelBody
				title={ __( 'RSVP Settings', 'event-tickets' ) }
				initialOpen={ true }
			>
				<RSVPSettingsSlot fillProps={ props } />
			</PanelBody>

			<PanelBody
				title={ __( 'Advanced Options', 'event-tickets' ) }
				initialOpen={ false }
			>
				<RSVPAdvancedSlot fillProps={ props } />
			</PanelBody>

			{ /* Extensions panel only shows if there are registered fills */ }
			<RSVPExtensionsSlot>
				{ ( fills ) => {
					if ( ! fills || fills.length === 0 ) {
						return null;
					}
					return (
						<PanelBody
							title={ __( 'Additional Options', 'event-tickets' ) }
							initialOpen={ false }
						>
							{ fills }
						</PanelBody>
					);
				} }
			</RSVPExtensionsSlot>
		</InspectorControls>
	);
}