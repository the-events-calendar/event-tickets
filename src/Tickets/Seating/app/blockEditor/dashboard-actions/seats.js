import React from "react";
import { ActionButton } from "../../../../../modules/elements";
import { getLink, getLocalizedString } from '../../utils';
import { Seat } from '../../../../../modules/icons';

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