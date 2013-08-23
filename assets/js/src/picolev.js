/**
 * Picolev
 * http://wpmoxie.com/
 *
 * Copyright (c) 2013 Sarah Lewis
 * Licensed under the GPLv2+ license.
 */
 
// ( function( window, undefined ) {
// 	'use strict';
	
	jQuery(document).ready(function( $ ) {
		jQuery.timeago.settings.allowFuture = true;
		
		jQuery("abbr.timeago").timeago();


	});

// } )( this );