/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LabeledItem, Link } from '@moderntribe/common/elements';
import { constants } from '../../../../../../../../modules/data/blocks/ticket';
import './style.pcss';

const { EDD, WOO, PROVIDER_TYPES, TICKET_LABELS } = constants;
const EDIT_TICKET = 'edit-ticket';
const REPORT = 'report';
const LINK_TYPES = [ EDIT_TICKET ];

const EcommerceOptions = ( { editTicketLink, isDisabled, provider, reportLink, showEcommerceOptions } ) => {
	const getEditTicketLinkLabel = ( ticketProvider ) => {
		let label = '';

		if ( ticketProvider === EDD ) {
			// eslint-disable-next-line no-undef
			label = sprintf(
				/* Translators: %s - the singular label for a ticket. */
				__( 'Edit %s in Easy Digital Downloads', 'event-tickets' ),
				TICKET_LABELS.ticket.singular // eslint-disable-line camelcase, no-undef
			);
		} else if ( ticketProvider === WOO ) {
			// eslint-disable-next-line no-undef
			label = sprintf(
				/* Translators: %s - the singular label for a ticket. */
				__( 'Edit %s in WooCommerce', 'event-tickets' ),
				TICKET_LABELS.ticket.singular // eslint-disable-line camelcase, no-undef
			);
		}

		return label;
	};

	const getLink = ( linkType ) => {
		const className = classNames(
			'tribe-editor__ticket__ecommerce-options-link',
			`tribe-editor__ticket__ecommerce-options-link--${ linkType }`
		);
		const href = linkType === REPORT ? reportLink : editTicketLink;
		const label =
			linkType === REPORT ? __( 'View Sales Report', 'event-tickets' ) : getEditTicketLinkLabel( provider );

		return isDisabled ? (
			<span className={ className }>{ label }</span>
		) : (
			<Link className={ className } href={ href } target="_blank">
				{ label }
			</Link>
		);
	};

	return (
		showEcommerceOptions && (
			<LabeledItem
				className={ classNames(
					'tribe-editor__ticket__ecommerce-options',
					'tribe-editor__ticket__content-row',
					'tribe-editor__ticket__content-row--ecommerce-options'
				) }
				label={ __( 'Ecommerce', 'event-tickets' ) }
			>
				<div className="tribe-editor__ticket__ecommerce-options-links">
					{ LINK_TYPES.map( ( linkType ) => (
						<span key={ linkType } className="tribe-editor__ticket__ecommerce-options-link-wrapper">
							{ getLink( linkType ) }
						</span>
					) ) }
				</div>
			</LabeledItem>
		)
	);
};

EcommerceOptions.propTypes = {
	editTicketLink: PropTypes.string,
	isDisabled: PropTypes.bool,
	// Add the `tc` provider, short for Tickets Commerce and coming from some legacy blocks.
	provider: PropTypes.oneOf( [ ...PROVIDER_TYPES, '', 'tc' ] ),
	reportLink: PropTypes.string,
	showEcommerceOptions: PropTypes.bool,
};

export default EcommerceOptions;
