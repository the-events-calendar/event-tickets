/**
 * Internal dependencies
 */
import { isNestedDatePickerInteraction } from '../is-nested-date-picker-interaction';
import * as datePickerPopoverState from '@moderntribe/common/utils/date-picker-popover-state';

describe( 'isNestedDatePickerInteraction', () => {
	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it( 'returns true when a nested date-picker popover is open', () => {
		jest.spyOn( datePickerPopoverState, 'isAnyDatePickerOpen' ).mockReturnValue( true );

		expect( isNestedDatePickerInteraction() ).toBe( true );
	} );

	it( 'returns true when a date-picker interaction is pending', () => {
		jest.spyOn( datePickerPopoverState, 'isAnyDatePickerOpen' ).mockReturnValue( false );
		jest.spyOn( datePickerPopoverState, 'isDatePickerInteractionPending' ).mockReturnValue( true );

		expect( isNestedDatePickerInteraction() ).toBe( true );
	} );

	it( 'returns true when focus moves to a date input', () => {
		jest.spyOn( datePickerPopoverState, 'isAnyDatePickerOpen' ).mockReturnValue( false );

		const container = document.createElement( 'div' );
		container.className = 'tribe-editor__date-input__container';
		const input = document.createElement( 'input' );
		container.appendChild( input );
		document.body.appendChild( container );

		const event = {
			target: document.createElement( 'div' ),
			relatedTarget: input,
		};

		expect( isNestedDatePickerInteraction( event ) ).toBe( true );

		document.body.removeChild( container );
	} );

	it( 'returns true when the active element is inside the calendar', () => {
		jest.spyOn( datePickerPopoverState, 'isAnyDatePickerOpen' ).mockReturnValue( false );

		const calendar = document.createElement( 'div' );
		calendar.className = 'rdp';
		const button = document.createElement( 'button' );
		calendar.appendChild( button );
		document.body.appendChild( calendar );
		button.focus();

		expect( isNestedDatePickerInteraction() ).toBe( true );

		document.body.removeChild( calendar );
	} );

	it( 'returns false for unrelated interactions', () => {
		jest.spyOn( datePickerPopoverState, 'isAnyDatePickerOpen' ).mockReturnValue( false );

		const button = document.createElement( 'button' );
		document.body.appendChild( button );
		button.focus();

		expect( isNestedDatePickerInteraction() ).toBe( false );

		document.body.removeChild( button );
	} );
} );
