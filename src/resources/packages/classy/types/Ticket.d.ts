import { Capacity } from './Capacity';
import { Fee } from './Fee';

export type Ticket = {
	name: string;
	description: string;
	price: string;
	hasSalePrice: boolean;
	salePrice: string;
	capacityType: Capacity;
	capacity?: number;
	selectedFees: Fee[];
	displayedFees: Fee[];
};
