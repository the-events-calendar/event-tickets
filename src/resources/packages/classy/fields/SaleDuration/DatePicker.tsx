import * as React from 'react';
import { MouseEventHandler } from 'react';
import {
	__experimentalInputControl as InputControl,
	__experimentalInputControlSuffixWrapper as SuffixWrapper,
	DatePicker as WPDatePicker,
	Popover,
} from '@wordpress/components';
import { VirtualElement } from '@wordpress/components/build-types/popover/types';
import { format } from '@wordpress/date';
import { IconCalendar } from '@tec/common/classy/components';
import { getDatePickerEventsBetweenDates } from '@tec/common/classy/functions';
import { DateUpdateType } from '@tec/common/classy/types/FieldProps';
import { StartOfWeek } from '@tec/common/classy/types/StartOfWeek';

export type DatePickerProps = {
	anchor: Element | VirtualElement | null;
	dateWithYearFormat: string;
	endDate: Date;
	isSelectingDate: DateUpdateType | false;
	onChange: ( selecting: DateUpdateType, newDate: string ) => void;
	onClick: MouseEventHandler< HTMLInputElement >;
	onClose: () => void;
	showPopover: boolean;
	startDate: Date;
	startOfWeek: StartOfWeek;
	currentDate: Date;
};

export default function DatePicker( props: DatePickerProps ): React.JSX.Element {
	const {
		anchor,
		dateWithYearFormat,
		endDate,
		isSelectingDate,
		onChange,
		onClick,
		onClose,
		showPopover,
		startDate,
		startOfWeek,
		currentDate,
	} = props;

	const events = getDatePickerEventsBetweenDates( startDate, endDate );

	return (
		<React.Fragment>
			<InputControl
				__next40pxDefaultSize
				className="classy-field__control classy-field__control--input classy-field__control--date-picker"
				value={ format( dateWithYearFormat, currentDate ) }
				onClick={ onClick }
				suffix={
					<SuffixWrapper onClick={ onClick } style={ { cursor: 'pointer' } }>
						<IconCalendar />
					</SuffixWrapper>
				}
			/>

			{ showPopover && (
				<Popover
					anchor={ anchor }
					className="classy-component__popover classy-component__popover--calendar"
					expandOnMobile={ true }
					placement="bottom"
					noArrow={ false }
					offset={ 4 }
					onClose={ onClose }
				>
					<WPDatePicker
						startOfWeek={ startOfWeek }
						currentDate={ currentDate }
						onChange={ ( newDate: string ): void => onChange( isSelectingDate as DateUpdateType, newDate ) }
						events={ events }
					/>
				</Popover>
			) }
		</React.Fragment>
	);
}
