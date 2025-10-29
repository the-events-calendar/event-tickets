import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as React from 'react';
import { Fee } from '../../types/Fee';

type CheckboxFeeProps = {
	fee: Fee;
	isChecked: boolean;
	isDisabled: boolean;
	onChange: ( event: React.ChangeEvent< HTMLInputElement > ) => void;
};

const getFeeLabel = ( fee: Fee ): string => {
	const { amount, label, subType } = fee;
	
	if ( subType === 'percentage' ) {
		return `${ label } (${ amount }%)`;
	}
	
	return `${ label } ($${ amount })`;
};

export default function CheckboxFee( props: CheckboxFeeProps ): React.JSX.Element | null {
	const { fee, isChecked, isDisabled, onChange } = props;
	
	// Skip inactive fees
	if ( fee.status !== 'active' ) {
		return null;
	}

	const checkboxName = `classy-ticket-fee-${ fee.id }`;
	const feeLabel = getFeeLabel( fee );

	return (
		<div className="classy-field__checkbox classy-field__fee-checkbox">
			<CheckboxControl
				checked={ isChecked }
				className="classy-field__checkbox__input"
				disabled={ isDisabled }
				id={ checkboxName }
				name={ checkboxName }
				onChange={ ( checked ) => {
					// Create a synthetic event for compatibility
					const syntheticEvent = {
						target: { value: fee.id.toString() }
					} as React.ChangeEvent< HTMLInputElement >;
					onChange( syntheticEvent );
				} }
			/>
			<label htmlFor={ checkboxName } className="classy-field__checkbox__label">
				{ feeLabel }
			</label>
		</div>
	);
}
