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
import { RawHTML } from '@wordpress/element';
import { InspectorControls } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import './style.pcss';

/**
 * @todo: create radio input element, move this over to element
 */

const RadioInput = ( { provider, onProviderChange, ...additionalProps } ) => (
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

RadioInput.propTypes = {
	provider: PropTypes.shape( {
		name: PropTypes.string,
		class: PropTypes.string,
	} ),
	onProviderChange: PropTypes.func,

};

const Controls = ( {
	hasMultipleProviders,
	providers,
	selectedProvider,
	onProviderChange,
	multipleProvidersNotice,
	choiceDisabled
} ) => (
	hasMultipleProviders && (
		<InspectorControls key="inspector">
			<PanelBody title={ __( 'Tickets Settings', 'event-tickets' ) }>
				<PanelRow>
					<fieldset className="tribe-editor__tickets-controls-provider">
						<legend>{ __( 'Sell tickets using', 'event-tickets' ) }</legend>
						<p>
							{ RawHTML ( { children: multipleProvidersNotice } ) }
						</p>
						{ providers.map( ( provider, key ) => (
							<RadioInput
								key={ `provider-option-${ key + 1 }` }
								provider={ provider }
								onProviderChange={ onProviderChange }
								checked={ selectedProvider === provider.class }
								disabled={ choiceDisabled }
							/>
						) ) }
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
	multipleProvidersNotice: PropTypes.string,
	choiceDisabled: PropTypes.bool
};

export default Controls;
