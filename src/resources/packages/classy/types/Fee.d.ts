export type FeeSubType = 'flat' | 'percentage';

export type Fee = {
	amount: number;
	id: number;
	label: string;
	slug: string;
	subType: FeeSubType;
	type: string;
};
