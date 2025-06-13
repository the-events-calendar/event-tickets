import { Capacity } from './Capacity';
import { Fee } from './Fee';

export type Ticket = {
	name: string;
	description: string;
	price: number;
	hasSalePrice: boolean;
	salePrice: number;
	capacityType: Capacity;
	capacity: number;
	selectedFees: Fee[];
	displayedFees: Fee[];
};
