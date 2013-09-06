<?php

$full_mb = new WPAlchemy_MetaBox(array
(
	'id' => '_picolev_badge_criteria',
	'title' => 'Badge Criteria',
	'types' => array('picolev_badge'),
	'context' => 'normal', // same as above, defaults to "normal"
	'priority' => 'high', // same as above, defaults to "high"
	'template' => PICOLEV_PATH . 'includes/metaboxes/badges-meta.php'
));

/* eof */