import { Ticket } from './Ticket';

export type StoreState = {
	tickets: Ticket[];
	currentPostId: number | null;
	isLoading: boolean;
	error: string | null;
}
