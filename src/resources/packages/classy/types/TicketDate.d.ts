import { Hours } from '@tec/common/classy/types/Hours';
import { Minutes } from '@tec/common/classy/types/Minutes';
import { Months } from '@tec/common/classy/types/Months';

type Seconds = Minutes;

export type TicketDate = {
	year: number;
	month: Months;
	day: number;
	hour?: Hours;
	minute?: Minutes;
	second?: Seconds;
}
