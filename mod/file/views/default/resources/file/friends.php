<?php
/**
 * Friends Files
 *
 * @package ElggFile
 */

$owner = elgg_get_page_owner_entity();
if (!$owner) {
	forward('', '404');
}

elgg_push_breadcrumb(elgg_echo('file'), "file/all");
elgg_push_breadcrumb($owner->name, "file/owner/$owner->username");
elgg_push_breadcrumb(elgg_echo('friends'));

elgg_register_title_button('file', 'add', 'object', 'file');

$title = elgg_echo("file:friends");

$list_type = get_input('list_type', 'gallery');
$item_class = [];
if ($list_type == 'gallery') {
	$item_class = ['col-12', 'col-md-6', 'col-lg-4'];
}

$content = elgg_list_entities_from_relationship([
	'type' => 'object',
	'subtype' => 'file',
	'full_view' => false,
	'relationship' => 'friend',
	'relationship_guid' => $owner->guid,
	'relationship_join_on' => 'owner_guid',
	'no_results' => elgg_echo("file:none"),
	'preload_owners' => true,
	'preload_containers' => true,
	'list_type' => $list_type,
	'item_class' => $item_class,
]);

$sidebar = file_get_type_cloud($owner->guid, true);

$body = elgg_view_layout('content', [
	'filter_context' => 'friends',
	'content' => $content,
	'title' => $title,
	'sidebar' => $sidebar,
]);

echo elgg_view_page($title, $body);
