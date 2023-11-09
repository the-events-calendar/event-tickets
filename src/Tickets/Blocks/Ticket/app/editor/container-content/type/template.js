/**
 * External dependencies
 */
import React, { useCallback } from 'react';
import PropTypes from 'prop-types';
import classNames from 'classnames';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { LabeledItem } from '@moderntribe/common/elements';
import {
	Pass as PassIcon,
	Ticket as TicketIcon
} from '@moderntribe/tickets/icons';
import './styles.pcss';

const TYPES = {
	default: {
		title: 'Single Ticket',
		icon: <TicketIcon />,
	},

	series: {
		title: 'Series Pass',
		icon: <PassIcon />,
	},
}

const Type = ({
	postType,
	seriesTitle,
	type,
}) => {

	const getDescription = useCallback( () => {
		switch ( type ) {
			case 'default':
				return sprintf( 'A single ticket is specific to this %s.', postType );
			case 'series':
				return sprintf( 'A single ticket is specific to this %s. You can add a Series Pass from the %s Series page.', postType, seriesTitle );
			default:
				return '';
		}
	}, [ postType, seriesTitle, type ] );

	return(
		<div className={ classNames(
			'tribe-editor__ticket__type',
			'tribe-editor__ticket__content-row',
			'tribe-editor__ticket__content-row--type',
		) }>
			<LabeledItem
				className="tribe-editor__ticket__type-label"
				isLabel={ true }
				label={ __( 'Type', 'event-tickets' ) }
			/>

			<div className="tribe-editor__ticket__type-description">
				<div>
					{ TYPES[ type ]?.icon }
					<span>{ __( TYPES[ type ]?.title, 'event-tickets' ) }</span>
				</div>
				<div>{ __( getDescription(), 'event_tickets' ) }</div>
			</div>
		</div>
	);
};

Type.propTypes = {
	postType: PropTypes.string,
	seriesTitle: PropTypes.string,
	type: PropTypes.string,
};

export default Type;
