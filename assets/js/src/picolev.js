/**
 * Picolev
 * http://wpmoxie.com/
 *
 * Copyright (c) 2013 Sarah Lewis
 * Licensed under the GPLv2+ license.
 */
 
( function( window, undefined ) {
	'use strict';
	
	jQuery(document).ready(function( $ ) {
		jQuery.timeago.settings.allowFuture = true;
		
		$("abbr.timeago").timeago();

		$( '.item-list-tabs ul li' ).on( 'click', function() {
			setTimeout( function() {
				$("abbr.timeago").timeago();
			}, 2000 );
		} );

		// Add Facebook SDK if it's not already there
		if ( 0 === $( '#fb-root' ).length ) {
			// Refactor: use a plugin option to get appID (right now, it's hardcoded)
			var fbSDK = '<div id="fb-root"></div><script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=213988808759618"; fjs.parentNode.insertBefore(js, fjs); }(document, \'script\', \'facebook-jssdk\'));</script>';

			$( 'body' ).prepend( fbSDK );
		}
		
	});

} )( this );