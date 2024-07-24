import React from "react";
import { ActionButton } from "@moderntribe/tickets/elements";
import { getLink, getLocalizedString } from '@tec/tickets/seating/utils';
import { Seat } from '@moderntribe/tickets/icons';

const getString = (key) => getLocalizedString( key, 'dashboard' );

const Seats = () => {
	const link = getLink( 'layout-edit' );

	if ( ! link ) {
		return null;
	}

	return (
		<ActionButton
			asLink={ true }
			href={ getLink('layout-edit') }
			icon={ <Seat /> }
			target="_blank"
		>
		{ getString( 'seats-action-label' ) }
		</ActionButton>
	);
};

export default Seats;