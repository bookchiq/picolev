/**
 * Picolev
 * http://wpmoxie.com/
 *
 * Copyright (c) 2013 Sarah Lewis
 * Licensed under the GPLv2+ license.
 */(function(e,t){"use strict";jQuery(document).ready(function(e){jQuery.timeago.settings.allowFuture=!0;e("abbr.timeago").timeago();e(".item-list-tabs ul li").on("click",function(){setTimeout(function(){e("abbr.timeago").timeago()},2e3)});if(0===e("#fb-root").length){var t="<div id=\"fb-root\"></div><script>(function(d, s, id) { var js, fjs = d.getElementsByTagName(s)[0]; if (d.getElementById(id)) return; js = d.createElement(s); js.id = id; js.src = \"//connect.facebook.net/en_US/all.js#xfbml=1&appId=213988808759618\"; fjs.parentNode.insertBefore(js, fjs); }(document, 'script', 'facebook-jssdk'));</script>";e("body").prepend(t)}})})(this);