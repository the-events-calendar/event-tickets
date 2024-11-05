import { Notice } from '@moderntribe/tickets/elements';
import { Fragment } from 'react';
import { __ } from '@wordpress/i18n';

const SeriesNotice = () => {
	return (
		<Fragment>
			<Notice
				description={ __( 'Assigned seating is not yet supported for events that are in series.', 'event-tickets' ) }
			/>
		</Fragment>
	);
};

export default SeriesNotice;