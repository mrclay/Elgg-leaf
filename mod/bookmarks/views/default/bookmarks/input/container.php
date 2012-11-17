<?php
/**
 * Select a container
 *
 * @uses $vars['user_guid']
 * @uses $vars['container_guid']
 * @uses $vars['name']
 * @uses $vars['type']
 * @uses $vars['subtype']
 */

$name = elgg_extract('name', $vars, 'container_guid');
$type = elgg_extract('type', $vars, 'all');
$subtype = elgg_extract('subtype', $vars, 'all');
$user_guid = (int) elgg_extract('user_guid', $vars);
if (!$user_guid) {
	$user_guid = elgg_get_logged_in_user_guid();
}
$container_guid = (int) elgg_extract('container_guid', $vars, $user_guid);

if (!function_exists('get_users_membership')) {
	echo elgg_view('input/hidden', array(
		'name' => $name,
		'value' => $user_guid,
	));
	return;
}

$container_options_values = array($user_guid => elgg_echo('bookmarks:container:me'));
foreach (get_users_membership($user_guid) as $group) {
	if (can_write_to_container($user_guid, $group->guid, $type, $subtype)) {
		$container_options_values[$group->guid] = elgg_echo('groups:acl', array($group->name));
	}
}

echo elgg_view('input/dropdown', array(
	'name' => $name,
	'options_values' => $container_options_values,
	'value' => $container_guid,
));
