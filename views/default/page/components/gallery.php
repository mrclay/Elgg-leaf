<?php
/**
 * Gallery view
 *
 * Implemented as an unordered list
 *
 * @uses $vars['items']         Array of ElggEntity or ElggAnnotation objects
 * @uses $vars['offset']        Index of the first list item in complete list
 * @uses $vars['limit']         Number of items per page
 * @uses $vars['count']         Number of items in the complete list
 * @uses $vars['pagination']    Show pagination? (default: true)
 * @uses $vars['position']      Position of the pagination: before, after, or both
 * @uses $vars['full_view']     Show the full view of the items (default: false)
 * @uses $vars['gallery_class'] Additional CSS class for the <ul> element
 * @uses $vars['item_class']    Additional CSS class for the <li> elements
 * @uses $vars['no_results']    Message to display if no results
 * @uses $vars['item_renderer'] Callable to render list item (default: 'elgg_view_list_item')
 */

$items = $vars['items'];
$offset = $vars['offset'];
$limit = $vars['limit'];
$count = $vars['count'];
$pagination = elgg_extract('pagination', $vars, true);
$offset_key = elgg_extract('offset_key', $vars, 'offset');
$position = elgg_extract('position', $vars, 'after');
$no_results = elgg_extract('no_results', $vars, '');
$item_renderer = elgg_extract('item_renderer', $vars, 'elgg_view_list_item');

if (!$items && $no_results) {
	echo "<p>$no_results</p>";
	return;
}

if (!is_array($items) || count($items) == 0) {
	return;
}

elgg_push_context('gallery');

$gallery_class = 'elgg-gallery';
if (isset($vars['gallery_class'])) {
	$gallery_class = "$gallery_class {$vars['gallery_class']}";
}

$item_class = 'elgg-item';
if (isset($vars['item_class'])) {
	$item_class = "$item_class {$vars['item_class']}";
}

$nav = '';
if ($pagination && $count) {
	$nav .= elgg_view('navigation/pagination', array(
		'offset' => $offset,
		'count' => $count,
		'limit' => $limit,
		'offset_key' => $offset_key,
	));
}

if ($position == 'before' || $position == 'both') {
	echo $nav;
}

echo "<ul class=\"$gallery_class\">";
foreach ($items as $item) {
	if ($item instanceof ElggEntity) {
		$id = "elgg-{$item->getType()}-{$item->getGUID()}";
	} else {
		$id = "item-{$item->getType()}-{$item->id}";
	}
	echo "<li id=\"$id\" class=\"$item_class\">";
	echo call_user_func($item_renderer, $item, $vars);
	echo "</li>";
}
echo "</ul>";

if ($position == 'after' || $position == 'both') {
	echo $nav;
}

elgg_pop_context();
