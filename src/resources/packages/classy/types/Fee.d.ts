import { FeeSubType } from "./FeeSubType";

export type Fee = {
	amount: number;
	id: number;
	label: string;
	slug: string;
	subType: FeeSubType;
	type: string;
};
