import { CurrencyPosition } from '@tec/common/classy/types/CurrencyPosition';

export type CostDetails = {
	currencySymbol: string;
	currencyPosition: CurrencyPosition;
	currencyDecimalSeparator: string;
	currencyThousandSeparator: string;
	suffix?: string;
	values: number[];
};
