( function( $, obj ) {
	obj.$window = $( window );

	obj.selectors = {
		adminVideo: '.tribe-events-admin-video',
		thickbox: 'a.thickbox',
	};

	obj.onReady = function() {
		obj.$window.resize(); // call the resize event at document ready to do this on load as well
	};

	obj.onResize = function() {
		// resize the video embed to keep the right aspect ratio
		$( obj.selectors.adminVideo ).each( function() {
			const $video = $( this );
			const w = $video.width();
			let newHeight = 0;

			// 16:9 ratio
			newHeight = w * 0.5625;

			$video.height( newHeight );
			$video.find( 'iframe' ).height( newHeight );
		} );

		// find window size to adjust the thickbox links
		const height = parseInt( obj.$window.height() * 0.9, 10 );
		const width = parseInt( obj.$window.width() * 0.9, 10 );

		// adjust thickbox links height and width to be 90% of the browser height and width
		$( obj.selectors.thickbox ).each( function() {
			const $link = $( this );
			const href = new URL( $link.prop( 'href' ) );

			href.searchParams.set( 'height', height );
			href.searchParams.set( 'width', width );

			$link.prop( 'href', href.toString() );
		} );
	};

	obj.$window.on( 'resize', obj.onResize );
	$( obj.onReady );
} )( jQuery, {} );
