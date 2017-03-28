<?php
/**
 * Group members sidebar
 *
 * @package ElggGroups
 *
 * @uses $vars['entity'] Group entity
 * @uses $vars['limit']  The number of members to display
 */

$limit = elgg_extract('limit', $vars, 14);

$all_link = elgg_view('output/url', [
	'href' => 'groups/members/' . $vars['entity']->guid,
	'text' => elgg_echo('groups:members:more'),
	'is_trusted' => true,
]);

$body = elgg_list_entities_from_relationship([
	'relationship' => 'member',
	'relationship_guid' => $vars['entity']->guid,
	'inverse_relationship' => true,
	'type' => 'user',
	'limit' => $limit,
	'order_by' => 'r.time_created DESC',
	'pagination' => false,
	'list_type' => 'gallery',
	'gallery_class' => 'elgg-gallery-users card-block d-flex flex-wrap justify-content-center p-1',
]);

$body .= "<div class='center card-block'>$all_link</div>";

echo elgg_view_module('aside', elgg_echo('groups:members'), $body, [
	'class' => 'card',
]);
