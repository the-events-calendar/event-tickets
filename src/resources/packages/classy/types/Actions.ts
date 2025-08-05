import { Action } from 'redux';
import { TicketSettings } from './Ticket';

export const CREATE_TICKET = 'CREATE_TICKET';
export const DELETE_TICKET = 'DELETE_TICKET';
export const SET_IS_LOADING = 'SET_IS_LOADING';
export const SET_TICKETS = 'SET_TICKETS';
export const UPDATE_TICKET = 'UPDATE_TICKET';

export type CreateTicketAction = {
	ticket: TicketSettings;
} & Action<typeof CREATE_TICKET>;

export type DeleteTicketAction = {
	ticketId: number;
} & Action<typeof DELETE_TICKET>;

export type SetIsLoadingAction = {
	isLoading: boolean;
} & Action<typeof SET_IS_LOADING>;

export type SetTicketsAction = {
	tickets: TicketSettings[];
} & Action<typeof SET_TICKETS>;

export type UpdateTicketAction = {
	ticketId: number;
	ticketData: TicketSettings;
} & Action<typeof UPDATE_TICKET>;
