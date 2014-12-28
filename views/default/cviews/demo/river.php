<?php

$options = [
	'limit' => $vars['limit'],
	'offset' => $vars['offset'],
	'position' => 'before',
];

echo elgg_list_river($options);
