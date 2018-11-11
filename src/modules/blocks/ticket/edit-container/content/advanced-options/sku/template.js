/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */
import { LabelWithTooltip } from '@moderntribe/tickets/elements';

class SKU extends PureComponent {
	static propTypes = {
		label: PropTypes.string,
		tooltip: PropTypes.string,
		onChange: PropTypes.func.isRequired,
		value: PropTypes.string,
	};

	static defaultProps = {
		label: __( 'Ticket SKU', 'events-gutenberg' ),
		tooltip: __(
			'A unique identifying code for each ticket type you\'re selling',
			'events-gutenberg',
		),
		value: '',
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-sku' );
	}

	onChange = ( event ) => {
		const { onChange } = this.props;
		onChange( event.target.value );
	}

	render() {
		const { value, label, tooltip } = this.props;
		return (
			<div className="tribe-editor__container-panel__row tribe-editor__container-panel__row--sku">
				<LabelWithTooltip
					className="tribe-editor__container-panel__label"
					forId={ this.id }
					isLabel={ true }
					label={ label }
					tooltipText={ tooltip }
					tooltipLabel={ <Dashicon icon="info-outline" /> }
				/>
				<div className="tribe-editor__container-panel__input-group">
					<input
						className="tribe-editor__ticket-field__sku"
						id={ this.id }
						type="text"
						value={ value }
						onChange={ this.onChange }
					/>
				</div>
			</div>
		);
	}
}

export default SKU;
