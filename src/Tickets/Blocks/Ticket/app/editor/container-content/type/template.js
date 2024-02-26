/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { _x } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ECP as ECPIcon } from '@moderntribe/tickets/icons';
import './styles.pcss';
import { LabelWithTooltip } from '@moderntribe/tickets/elements';
import { Dashicon } from '@wordpress/components';
import { TICKET_LABELS } from '@moderntribe/tickets/data/blocks/ticket/constants';

const Type = ({ typeName, typeDescription, upsellMessage, typeIcon }) => {
	// This is sanitized in the PHP section, furthermore this description will not go to the backend.
	const htmlTypeUpsellDescription = {
		__html: upsellMessage || '',
	};

	return (
		<div
			className={classNames(
				'tribe-editor__ticket__type',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--type'
			)}
		>
			<LabelWithTooltip
				className="tribe-editor__ticket__type-label"
				forId=""
				isLabel={true}
				// eslint-disable-next-line no-undef
				label={sprintf(
					/* Translators: %s - the singular label for a ticket. */
					_x(
						'%s type',
						'Block Editor Ticket type label',
						'event-tickets'
					),
					TICKET_LABELS.ticket.singular
				)}
				tooltipText={typeDescription}
				tooltipLabel={
					<Dashicon
						className="tribe-editor__ticket__tooltip-label"
						icon="info-outline"
					/>
				}
			/>

			<div className="tribe-editor__ticket__type__description">
				<div className="tribe-editor__ticket__type__type-title">
					{typeIcon}
					<span>{typeName}</span>
				</div>
				{upsellMessage ? (
					<div className="tribe-editor__ticket__type__type-upsell-description">
						<ECPIcon />
						<span
							dangerouslySetInnerHTML={htmlTypeUpsellDescription}
						/>
					</div>
				) : null}
			</div>
		</div>
	);
};

Type.propTypes = {
	typeName: PropTypes.string,
	typeDescription: PropTypes.string,
	upsellMessage: PropTypes.string,
	typeIcon: PropTypes.node,
};

export default Type;
