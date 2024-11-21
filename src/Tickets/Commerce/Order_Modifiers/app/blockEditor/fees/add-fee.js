/**
 * External dependencies.
 */
import { __, _x } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const AddFee = ( { onClick } ) => {
	return (
		<Button
			variant="tertiary"
			onClick={ onClick }
			label={ _x(
				'Add a fee to the ticket',
				'aria-label for adding fee to ticket',
				'event-tickets'
			) }
		>
			{ __( '+ Add fee', 'event-tickets' ) }
		</Button>
	);
}

export default AddFee;
