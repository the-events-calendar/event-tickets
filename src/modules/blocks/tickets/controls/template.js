/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import { noop } from 'lodash';

/**
 * Wordpress dependencies
 */
import { PanelBody, PanelRow } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import './style.pcss';

const message = __(
	'It looks like you have multiple ecommerce plugins active. We recommend running only one at a time. However, if you need to run multiple, please select which one to use to sell tickets for this event. ',
	'events-gutenberg',
);

const note = __(
	'Note: adjusting this setting will only impact new tickets. Existing tickets will not change. We highly recommend that all tickets for one event use the same ecommerce plugin',
	'events-gutenberg',
);

const RadioInput = ( { provider, onProviderChange, ...additionalProps }) => (
	<div className="tribe-editor__tickets-control-container">
		<input
			className="tribe-editor__tickets-control__input tribe-editor__tickets-control__input--radio"
			type="radio"
			id={ provider.class }
			name={ provider.class }
			onChange={ onProviderChange }
			{ ...additionalProps }
		/>
		<label
			className="tribe-editor__tickets-control__label"
			htmlFor={ provider.class }>
			{ provider.name }
		</label>
	</div>
);

const Controls = ( {
	hasMultipleProviders,
	providers,
	selectedProvider,
	onProviderChange,
} ) => (
	hasMultipleProviders && (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Tickets Settings', 'events-gutenberg' ) }>
				<PanelRow>
					<fieldset className="tribe-editor__tickets-controls-provider">
						<legend>{ __( 'Sell tickets using', 'events-gutenberg' ) }</legend>
						<p>
							{ message }
							<em>{ note }</em>
						</p>
						{ providers.map( ( provider, key ) => {
							const inputProps = {
								checked: selectedProvider === provider.class,
							};

							return (
								<RadioInput
									key={ `provider-option-${ key + 1 }` }
									provider={ provider }
									onProviderChange={ onProviderChange }
									{ ...inputProps }
								/>
							);
						} ) }
					</fieldset>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	)
);

Controls.propTypes = {
	hasMultipleProviders: PropTypes.bool,
	providers: PropTypes.arrayOf( PropTypes.shape( {
		name: PropTypes.string,
		class: PropTypes.string,
	} ) ),
	selectedProvider: PropTypes.string,
	onProviderChange: PropTypes.func,
};

Controls.defaultProps = {
	providers: [],
	onProviderChange: noop,
};

export default Controls;
