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
import { ECP as ECPIcon } from '@moderntribe/tickets/icons';
import './styles.pcss';

const Type = ({
	hasEventsPro,
	typeName,
	typeDescription,
	typeUpsellDescription,
	typeIcon,
}) => {
	// This is sanitized in the PHP section, furthermore this description will not go to the backend.
	const htmlTypeDescription = { __html: typeDescription };
	const htmlTypeUpsellDescription = {
		__html: typeUpsellDescription,
	};

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
					{typeIcon}
					<span>{typeName}</span>
				</div>
				<div
					className="tribe-editor__ticket__type__type-description"
					dangerouslySetInnerHTML={htmlTypeDescription}
				/>
				{!hasEventsPro ? (
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
	hasEventsPro: PropTypes.bool,
	typeName: PropTypes.string,
	typeDescription: PropTypes.string,
	typeUpsellDescription: PropTypes.string,
	typeIcon: PropTypes.node,
};

export default Type;
