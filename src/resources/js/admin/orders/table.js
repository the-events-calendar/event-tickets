( function( $ ) {
	/**
	 * Setup Datepicker
	 */
	const $dateFormat = $( '[data-datepicker_format]' );
	const _MS_PER_DAY = 1000 * 60 * 60 * 24;
	let datepickerFormat = 0;

	/**
	 * Returns the number of months to display in
	 * the datepicker based on the viewport width
	 *
	 * @returns {number} The number of months to display
	 */
	function getDatepickerNumMonths() {
		const windowWidth = $( window ).width();

		if ( windowWidth < 800 ) {
			return 1;
		}

		if ( windowWidth <= 1100 ) {
			return 2;
		}

		return 3;
	}

	function dateDiffInDays( a, b ) {
		const utc1 = Date.UTC( a.getFullYear(), a.getMonth(), a.getDate() );
		const utc2 = Date.UTC( b.getFullYear(), b.getMonth(), b.getDate() );

		return Math.floor( ( utc2 - utc1 ) / _MS_PER_DAY );
	}

	if ( typeof tribe_l10n_datatables.datepicker !== 'undefined' ) { // eslint-disable-line no-undef
		let tribeDatepickerOpts = {};

		let dateFormat = 'yy-mm-dd';

		if ( $dateFormat.length && $dateFormat.attr( 'data-datepicker_format' ).length >= 1 ) {
			datepickerFormat = $dateFormat.attr( 'data-datepicker_format' );
			dateFormat = datepicker_formats.main[ datepickerFormat ]; // eslint-disable-line no-undef
		}

		const $startDate = $( document.getElementById( 'EventStartDate' ) );
		const $endDate = $( document.getElementById( 'EventEndDate' ) );
		const $eventDetails = $( document.getElementById( 'tribe_events_event_details' ) );

		tribeDatepickerOpts = {
			dateFormat: dateFormat,
			showAnim: 'fadeIn',
			changeMonth: true,
			changeYear: true,
			numberOfMonths: getDatepickerNumMonths(),
			showButtonPanel: false,
			beforeShow: function( element, object ) {
				object.input.datepicker( 'option', 'numberOfMonths', getDatepickerNumMonths() );
				object.input.data( 'prevDate', object.input.datepicker( 'getDate' ) );

				// allow single datepicker fields to specify a min or max date
				// using the `data-datapicker-(min|max)Date` attribute
				if ( undefined !== object.input.data( 'datepicker-min-date' ) ) {
					object.input.datepicker(
						'option',
						'minDate',
						object.input.data( 'datepicker-min-date' ),
					);
				}

				if ( undefined !== object.input.data( 'datepicker-max-date' ) ) {
					object.input.datepicker(
						'option',
						'maxDate',
						object.input.data( 'datepicker-max-date' ),
					);
				}

				// Capture the datepicker div here; it's dynamically generated so best to grab here instead
				// of elsewhere.
				const $dpDiv = $( object.dpDiv );

				// "Namespace" our CSS a bit so that our custom jquery-ui-datepicker styles don't interfere
				// with other plugins'/themes'.
				$dpDiv.addClass( 'tribe-ui-datepicker' );

				$eventDetails.trigger( 'tribe.ui-datepicker-div-beforeshow', [ object ] );

				$dpDiv.attrchange( {
					trackValues: true,
					callback: function( attr ) {
						// This is a non-ideal, but very reliable way to look for the closing of the ui-datepicker box,
						// since onClose method is often occluded by other plugins, including Events Calender PRO.
						if (
							'string' === typeof attr.newValue &&
							(
								attr.newValue.indexOf( 'display: none' ) >= 0 ||
								attr.newValue.indexOf( 'display:none' ) >= 0
							)
						) {
							$dpDiv.removeClass( 'tribe-ui-datepicker' );
							$eventDetails.trigger( 'tribe.ui-datepicker-div-closed', [ object ] );
						}
					},
				} );
			},
			onSelect: function( selectedDate ) {
				const instance = $( this ).data( 'datepicker' );
				const date = $.datepicker.parseDate(
					instance.settings.dateFormat || $.datepicker._defaults.dateFormat,
					selectedDate,
					instance.settings,
				);

				// If the start date was adjusted, then let's modify the minimum acceptable end date
				if ( this.id === 'EventStartDate' ) {
					const startDate = $( document.getElementById( 'EventStartDate' ) ).data( 'prevDate' );
					// eslint-disable-next-line max-len
					const dateDiff = null == startDate ? 0 : dateDiffInDays( startDate, $endDate.datepicker( 'getDate' ) );
					const endDate = new Date( date.setDate( date.getDate() + dateDiff ) );

					$endDate
						.datepicker( 'option', 'minDate', $startDate.datepicker( 'getDate' ) )
						.datepicker( 'setDate', endDate )
						.datepicker_format;
				}

				// fire the change and blur handlers on the field
				$( this ).trigger( 'change' );
				$( this ).trigger( 'blur' );
			},
		};

		// eslint-disable-next-line no-undef
		$.extend( tribeDatepickerOpts, tribe_l10n_datatables.datepicker );

		$( '.tribe-datepicker' ).datepicker( tribeDatepickerOpts );
	}
} )( jQuery );
