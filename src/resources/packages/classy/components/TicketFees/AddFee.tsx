import { Button } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import * as React from 'react';

type AddFeeProps = {
	onClick: () => void;
};

export default function AddFee( props: AddFeeProps ): React.JSX.Element {
	const { onClick } = props;

	return (
		<Button
			className="classy-field__add-fee"
			variant="tertiary"
			onClick={ onClick }
			label={ _x( 'Add a fee to the ticket', 'aria-label for adding fee to ticket', 'event-tickets' ) }
		>
			{ __( '+ Add fee', 'event-tickets' ) }
		</Button>
	);
}
