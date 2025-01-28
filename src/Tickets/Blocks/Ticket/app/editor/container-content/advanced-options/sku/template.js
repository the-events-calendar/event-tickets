/**
 * External dependencies
 */
import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import uniqid from 'uniqid';

/**
 * Internal dependencies
 */
import { Input } from '@moderntribe/common/elements';
import { LabelWithTooltip } from '@moderntribe/tickets/elements';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';

class SKU extends PureComponent {
	static propTypes = {
		isDisabled: PropTypes.bool,
		onChange: PropTypes.func.isRequired,
		sku: PropTypes.string,
	};

	constructor( props ) {
		super( props );
		this.id = uniqid( 'ticket-sku' );
	}

	render() {
		const { sku, isDisabled, onChange } = this.props;

		return (
			<div className={ classNames(
				'tribe-editor__ticket__sku',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--sku',
			) }>
				<LabelWithTooltip
					className="tribe-editor__ticket__sku-label-with-tooltip"
					forId={ this.id }
					isLabel={ true }
					// eslint-disable-next-line no-undef
					label={sprintf(
						/* Translators: %s - the singular label for a ticket. */
						__('%s SKU', 'event-tickets'),
						TICKET_LABELS.ticket.singular
					)}
					// eslint-disable-next-line no-undef
					tooltipText={sprintf(
						/* Translators: %s - the singular, lowercase label for a ticket. */
						__(
							"A unique identifying code for each %s type you're selling",
							'event-tickets'
						),
						TICKET_LABELS.ticket.singularLowercase
					)}
					tooltipLabel={
						<Dashicon
							className="tribe-editor__ticket__tooltip-label"
							icon="info-outline"
						/>
					}
				/>
				<Input
					className="tribe-editor__ticket__sku-input"
					id={ this.id }
					type="text"
					value={ sku ?? '' }
					onChange={ onChange }
					disabled={ isDisabled }
				/>
			</div>
		);
	}
}

export default SKU;
