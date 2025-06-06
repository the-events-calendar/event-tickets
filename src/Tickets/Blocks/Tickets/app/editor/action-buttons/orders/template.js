/**
 * External dependencies
 */
import React from 'react';
import PropTypes from 'prop-types';

/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { ActionButton } from '../../../../../../../modules/elements';
import { Orders } from '../../../../../../../modules/icons';

const OrdersActionButton = ( { href } ) =>
	href ? (
		<ActionButton asLink={ true } href={ href } icon={ <Orders /> } target="_blank">
			{ __( 'Orders', 'event-tickets' ) }
		</ActionButton>
	) : null;

OrdersActionButton.propTypes = {
	href: PropTypes.string.isRequired,
};

export default OrdersActionButton;
