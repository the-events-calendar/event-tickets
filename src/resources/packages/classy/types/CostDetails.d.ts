import { CurrencyPosition } from '@tec/common/classy/types/CurrencyPosition';

export type CostDetails = {
	symbol: string;
	position: CurrencyPosition;
	decimalSeparator: string;
	thousandSeparator: string;
	suffix?: string;
	value?: number;
	precision: number;
};
