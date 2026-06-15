/* ClickPo AI Chatbot — admin JS */
( function ( $ ) {
	'use strict';

	$( function () {
		var $url     = $( '#clickpo_bot_launcher_icon_url' );
		var $preview = $( '#clickpo-bot-icon-preview' );
		var $remove  = $( '#clickpo-bot-remove-icon' );
		var $radio   = $( '#clickpo-bot-icon-image-radio' );
		var frame;

		$( '#clickpo-bot-upload-icon' ).on( 'click', function ( e ) {
			e.preventDefault();
			if ( ! window.wp || ! window.wp.media ) {
				return;
			}
			if ( frame ) {
				frame.open();
				return;
			}
			frame = window.wp.media( {
				title: 'בחירת אייקון לצ׳אט',
				button: { text: 'השתמש באייקון' },
				library: { type: 'image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$url.val( att.url );
				$preview.attr( 'src', att.url ).show();
				$remove.show();
				$radio.prop( 'checked', true ); // Switch the launcher to the uploaded image.
			} );
			frame.open();
		} );

		$remove.on( 'click', function ( e ) {
			e.preventDefault();
			$url.val( '' );
			$preview.attr( 'src', '' ).hide();
			$( this ).hide();
		} );
	} );
} )( jQuery );
