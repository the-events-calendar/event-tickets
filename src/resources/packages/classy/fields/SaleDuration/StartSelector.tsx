import * as React from 'react';
import { MouseEventHandler } from 'react';
import { useSelect } from '@wordpress/data';
import { format } from '@wordpress/date';
import { RefObject, useRef } from '@wordpress/element';
import { DatePicker, TimePicker } from '@tec/common/classy/components';
import type { StartOfWeek } from '@tec/common/classy/types/StartOfWeek';
import { _x } from '@wordpress/i18n';

export default function StartSelector( props: {
	dateWithYearFormat: string;
	endDate: Date;
	highightTime: boolean;
	isMultiday: boolean;
	isSelectingDate: 'start' | 'end' | false;
	onChange: ( selecting: 'start' | 'end', date: string ) => void;
	onClick: MouseEventHandler;
	onClose: () => void;
	startDate: Date;
	startOfWeek: StartOfWeek;
	timeFormat: string;
} ) {
	const {
		dateWithYearFormat,
		endDate,
		highightTime,
		isMultiday,
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
		onChange( 'start', format( 'Y-m-d H:i:s', date ) );
	};

	return (
		<div className="classy-field__group">
			<div className="classy-field__input classy-field__input--start-date" ref={ ref }>
				<DatePicker
					anchor={ ref.current }
					dateWithYearFormat={ dateWithYearFormat }
					endDate={ endDate }
					isSelectingDate={ isSelectingDate }
					isMultiday={ isMultiday }
					onClick={ onClick }
					onClose={ onClose }
					onChange={ onChange }
					showPopover={ isSelectingDate === 'start' }
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
					endDate={ isMultiday ? null : endDate }
					highlight={ highightTime }
					onChange={ onTimeChange }
					timeFormat={ timeFormat }
					timeInterval={ timeInterval }
				/>
			</div>
		</div>
	);
}
