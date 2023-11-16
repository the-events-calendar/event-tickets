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
import { LabeledItem } from '@moderntribe/common/elements';
import { Ticket as TicketIcon, ECP as ECPIcon } from '@moderntribe/tickets/icons';
import './styles.pcss';

const Type = ({ hasEventsPro, postType, typeDescription, typeIconUrl, typeName }) => {
	const defaultTicketType = {
		typeName: _x(
			'Single Ticket',
			'Default ticket type label.',
			'event-tickets'
		),
		typeDescription: sprintf(
			// translators: %s is the post type name.
			_x(
				'A single ticket is specific to this %s.',
				'Default ticket type description.',
				'event-tickets'
			),
			postType
		),
		typeUpsellDescription:
			_x(
				'For more ticket types, <a href="https://evnt.is/tt-ecp" target="_blank" rel="noopener noreferrer">upgrade</a> to Events Calendar Pro',
				'Default ticket type upsell description.',
				'event-tickets'
			),
		typeIcon: <TicketIcon />,
	};

	const ticketType = {
		typeName: typeName || defaultTicketType.typeName,
		typeDescription: typeDescription || defaultTicketType.typeDescription,
		typeIcon: typeIconUrl ? (
			<img src={typeIconUrl} alt="" />
		) : (
			defaultTicketType.typeIcon
		),
	};

	// This is sanitized in the PHP section, furthermore this description will not go to the backend.
	const htmlTypeDescription = { __html: ticketType.typeDescription };
	const htmlTypeUpsellDescription = { __html: defaultTicketType.typeUpsellDescription };

	return (
		<div
			className={classNames(
				'tribe-editor__ticket__type',
				'tribe-editor__ticket__content-row',
				'tribe-editor__ticket__content-row--type'
			)}
		>
			<LabeledItem
				className="tribe-editor__ticket__type-label"
				isLabel={true}
				label={_x('Type', 'Ticket type label', 'event-tickets')}
			/>

			<div className="tribe-editor__ticket__type__description">
				<div className="tribe-editor__ticket__type__type-title">
					{ticketType.typeIcon}
					<span>{ticketType.typeName}</span>
				</div>
				<div className="tribe-editor__ticket__type__type-description" dangerouslySetInnerHTML={htmlTypeDescription} />
				{
					! hasEventsPro
						? (
							<div className="tribe-editor__ticket__type__type-upsell-description">
								<ECPIcon />
								<span dangerouslySetInnerHTML={htmlTypeUpsellDescription} />
							</div>

						)
						: null
				}
			</div>
		</div>
	);
};

Type.propTypes = {
	hasEventsPro: PropTypes.bool,
	postType: PropTypes.string,
	typeDescription: PropTypes.string,
	typeIconUrl: PropTypes.string,
	typeName: PropTypes.string,
};

export default Type;
