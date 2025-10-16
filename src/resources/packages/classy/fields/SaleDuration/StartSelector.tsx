import * as React from 'react';
import { useSelect } from '@wordpress/data';
import { format } from '@wordpress/date';
import { RefObject, useRef } from '@wordpress/element';
import { _x } from '@wordpress/i18n';
import { TimePicker } from '@tec/common/classy/components';
import { DateSelectorProps } from '@tec/common/classy/types/DateSelectorProps';
import { default as DatePicker } from './DatePicker';

type StartSelectorProps = Omit< DateSelectorProps, 'isMultiday' >;

export default function StartSelector( props: StartSelectorProps ): React.JSX.Element {
	const {
		dateWithYearFormat,
		endDate,
		highlightTime,
		isSelectingDate,
		onChange,
		onClick,
		onClose,
		startDate,
		startOfWeek,
		timeFormat,
	} = props;

	const ref: RefObject< HTMLDivElement > = useRef( null );
	const timeInterval = useSelect( ( select ) => {
		// @ts-ignore
		return select( 'tec/classy' ).getTimeInterval();
	}, [] );

	const onTimeChange = ( date: Date ): void => {
		onChange( 'startTime', format( 'Y-m-d H:i:s', date ) );
	};

	return (
		<div className="classy-field__group">
			<div className="classy-field__input classy-field__input--start-date" ref={ ref }>
				<DatePicker
					anchor={ ref.current }
					dateWithYearFormat={ dateWithYearFormat }
					endDate={ endDate }
					isSelectingDate={ isSelectingDate }
					onClick={ onClick }
					onClose={ onClose }
					onChange={ onChange }
					showPopover={ isSelectingDate === 'startDate' }
					startDate={ startDate }
					startOfWeek={ startOfWeek }
					currentDate={ startDate }
				/>
			</div>

			<span className="classy-field__separator">
				{ _x( 'at', 'multi-day start and end date separator', 'the-events-calendar' ) }
			</span>

			<div className="classy-field__input classy-field__input--start-time">
				<TimePicker
					currentDate={ startDate }
					endDate={ endDate }
					highlight={ highlightTime }
					onChange={ onTimeChange }
					timeFormat={ timeFormat }
					timeInterval={ timeInterval }
				/>
			</div>
		</div>
	);
}
