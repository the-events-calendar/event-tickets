export type FeeSubType = 'flat' | 'percentage';
export type FeeStatus = 'active' | 'draft' | 'inactive';

export type Fee = {
	amount: number;
	id: number;
	label: string;
	slug: string;
	subType: FeeSubType;
	status: FeeStatus;
};
