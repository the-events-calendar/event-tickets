import * as React from 'react';
import { MouseEventHandler } from 'react';
import { StartOfWeek } from '@tec/common/classy/types/StartOfWeek';
import { RefObject, useRef } from '@wordpress/element';
import { format } from '@wordpress/date';
import { _x } from '@wordpress/i18n';
import { DatePicker, TimePicker } from '@tec/common/classy/components';
import { useSelect } from '@wordpress/data';

export default function EndSelector( props: {
	dateWithYearFormat: string;
	endDate: Date;
	highlightTime: boolean;
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
		highlightTime,
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
		onChange( 'end', format( 'Y-m-d H:i:s', date ) );
	};

	return (
		<div className="classy-field__group">
			<div className="classy-field__input classy-field__input--end-date" ref={ ref }>
				<DatePicker
					anchor={ ref.current }
					dateWithYearFormat={ dateWithYearFormat }
					endDate={ endDate }
					isSelectingDate={ isSelectingDate }
					isMultiday={ isMultiday }
					onChange={ onChange }
					onClick={ onClick }
					onClose={ onClose }
					showPopover={ isSelectingDate === 'end' }
					startDate={ startDate }
					startOfWeek={ startOfWeek }
					currentDate={ endDate }
				/>
			</div>

			<span className="classy-field__separator">
				{ _x( 'at', 'multi-day start and end date separator', 'the-events-calendar' ) }
			</span>

			<div className="classy-field__input classy-field__input--end-time">
				<TimePicker
					currentDate={ endDate }
					highlight={ highlightTime }
					startDate={ isMultiday ? null : startDate }
					timeFormat={ timeFormat }
					timeInterval={ timeInterval }
					onChange={ onTimeChange }
				/>
			</div>
		</div>
	);
}
