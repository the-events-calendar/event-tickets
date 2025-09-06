/**
 * Inspector controls for RSVP block
 *
 * @since TBD
 */
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
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
	/**
	 * Filters the inspector control panels for the RSVP block.
	 *
	 * Allows Event Tickets Plus and other extensions to add custom panels
	 * to the RSVP block inspector controls.
	 *
	 * @since TBD
	 *
	 * @param {Array}  panels Array of panel configurations.
	 * @param {Object} props  Component props including attributes and setAttributes.
	 *
	 * @return {Array} Modified array of panel configurations.
	 */
	const additionalPanels = applyFilters(
		'tec.tickets.commerce.rsvp.inspectorPanels',
		[],
		props
	);

	return (
		<InspectorControls>
			<PanelBody
				title={ (
					<>
						<span className="dashicons dashicons-email" style={{ marginRight: '6px' }}></span>
						{ __( 'RSVP', 'event-tickets' ) }
					</>
				) }
				initialOpen={ true }
			>
				<RSVPSettingsSlot fillProps={ props } />
			</PanelBody>

			{ /* Render additional panels from filter */ }
			{ additionalPanels.map( ( panel, index ) => (
				<PanelBody
					key={ `additional-panel-${index}` }
					title={ panel.title }
					initialOpen={ panel.initialOpen || false }
				>
					{ panel.content }
				</PanelBody>
			) ) }

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
