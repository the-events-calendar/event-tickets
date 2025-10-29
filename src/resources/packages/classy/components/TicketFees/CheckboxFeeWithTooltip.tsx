import { CheckboxControl, Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import * as React from 'react';
import { Fee } from '../../types/Fee';

type CheckboxFeeWithTooltipProps = {
	fee: Fee;
	isChecked: boolean;
	isDisabled: boolean;
	onChange: () => void;
	tooltipText: string;
};

const getFeeLabel = ( fee: Fee ): string => {
	const { amount, label, subType } = fee;
	
	if ( subType === 'percentage' ) {
		return `${ label } (${ amount }%)`;
	}
	
	return `${ label } ($${ amount })`;
};

export default function CheckboxFeeWithTooltip( props: CheckboxFeeWithTooltipProps ): React.JSX.Element | null {
	const { fee, isChecked, isDisabled, onChange, tooltipText } = props;
	
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
				onChange={ onChange }
			/>
			<Tooltip text={ tooltipText }>
				<label htmlFor={ checkboxName } className="classy-field__checkbox__label classy-field__checkbox__label--with-tooltip">
					{ feeLabel }
				</label>
			</Tooltip>
		</div>
	);
}
