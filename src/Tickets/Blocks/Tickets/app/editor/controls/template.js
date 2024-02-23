/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';
import './style.pcss';

const RadioInput = ({ provider, onProviderChange, ...additionalProps }) => (
	<div className="tribe-editor__tickets-control-container">
		<input
			className="tribe-editor__tickets-control__input tribe-editor__tickets-control__input--radio"
			type="radio"
			id={provider.class}
			name={provider.class}
			onChange={onProviderChange}
			{...additionalProps}
		/>
		<label
			className="tribe-editor__tickets-control__label"
			htmlFor={provider.class}
		>
			{provider.name}
		</label>
	</div>
);

RadioInput.propTypes = {
	provider: PropTypes.shape({
		name: PropTypes.string,
		class: PropTypes.string,
	}),
	onProviderChange: PropTypes.func,
};

const Controls = ({
	disabled,
	hasMultipleProviders,
	message,
	onProviderChange,
	providers,
	selectedProvider,
}) =>
	hasMultipleProviders && (
		<InspectorControls key="inspector">
			<PanelBody
				// eslint-disable-next-line no-undef
				title={sprintf(
					/* Translators: %s - Ticket plural label. */
					__('%s Settings', 'event-tickets'),
					TICKET_LABELS.ticket.plural
				)}
			>
				<PanelRow>
					<fieldset className="tribe-editor__tickets-controls-provider">
						<legend>
							{
								// eslint-disable-next-line no-undef
								sprintf(
									/* Translators: %s - Ticket plural label. */
									__('Sell %s using', 'event-tickets'),
									TICKET_LABELS.ticket.pluralLowercase
								)
							}
						</legend>
						{message}
						{providers.map((provider, key) => (
							<RadioInput
								key={`provider-option-${key + 1}`}
								provider={provider}
								onProviderChange={onProviderChange}
								checked={selectedProvider === provider.class}
								disabled={disabled}
							/>
						))}
					</fieldset>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	);

Controls.propTypes = {
	disabled: PropTypes.bool,
	hasMultipleProviders: PropTypes.bool,
	message: PropTypes.node,
	onProviderChange: PropTypes.func,
	providers: PropTypes.arrayOf(
		PropTypes.shape({ name: PropTypes.string, class: PropTypes.string })
	),
	selectedProvider: PropTypes.string,
};

export default Controls;
