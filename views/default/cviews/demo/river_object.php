<?php

if (empty($vars['river_id'])) {
	return;
}

$river_items = elgg_get_river([
	'ids' => $vars['river_id'],
]);
/* @var ElggRiverItem[] $river_items */

if ($river_items[0]->subtype === 'comment') {
	$entity = get_entity($river_items[0]->target_guid);
} else {
	$entity = get_entity($river_items[0]->object_guid);
}

echo "<h3>Preview</h3>";
echo "<div class='elgg-border-plain pam'>" . elgg_view_entity($entity) . "</div>";
