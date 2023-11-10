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
import { Ticket as TicketIcon } from '@moderntribe/tickets/icons';
import './styles.pcss';

const Type = ({ postType, type, typeDescription, typeIconUrl, typeName }) => {
	let ticketType = {};

	if (type === 'default') {
		ticketType = {
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
			typeIcon: <TicketIcon />,
		};
	} else {
		ticketType = {
			typeName,
			typeDescription,
			typeIcon: <img src={typeIconUrl} alt="" />,
		};
	}

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

			<div className="tribe-editor__ticket__type-description">
				<div>
					{ticketType.typeIcon}
					<span>{ticketType.typeName}</span>
				</div>
				<div>{ticketType.typeDescription}</div>
			</div>
		</div>
	);
};

Type.propTypes = {
	postType: PropTypes.string,
	type: PropTypes.string,
	typeDescription: PropTypes.string,
	typeIconUrl: PropTypes.string,
	typeName: PropTypes.string,
};

export default Type;
