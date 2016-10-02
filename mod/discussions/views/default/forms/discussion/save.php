<?php

/**
 * Discussion topic add/edit form body
 *
 */
$title = elgg_extract('title', $vars, '');
$desc = elgg_extract('description', $vars, '');
$status = elgg_extract('status', $vars, '');
$tags = elgg_extract('tags', $vars, '');
$access_id = elgg_extract('access_id', $vars, ACCESS_DEFAULT);
$container_guid = elgg_extract('container_guid', $vars);
$guid = elgg_extract('guid', $vars, null);

$fields = [
	[
		'input_type' => 'text',
		'name' => 'title',
		'value' => $title,
		'label' => elgg_echo('title'),
		'required' => true,
	],
	[
		'input_type' => 'longtext',
		'name' => 'description',
		'value' => $desc,
		'label' => elgg_echo('discussion:topic:description'),
		'required' => true,
	],
	[
		'input_type' => 'tags',
		'name' => 'tags',
		'value' => $tags,
		'label' => elgg_echo('tags'),
	],
	[
		'input_type' => 'select',
		'name' => 'status',
		'value' => $status,
		'options_values' => array(
			'open' => elgg_echo('status:open'),
			'closed' => elgg_echo('status:closed'),
		),
		'label' => elgg_echo('discussion:topic:status'),
	],
	[
		'input_type' => 'access',
		'name' => 'access_id',
		'value' => $access_id,
		'entity' => get_entity($guid),
		'entity_type' => 'object',
		'entity_subtype' => 'discussion',
		'label' => elgg_echo('access'),
	],
	[
		'input_type' => 'hidden',
		'name' => 'container_guid',
		'value' => $container_guid,
	],
	[
		'input_type' => 'hidden',
		'name' => 'topic_guid',
		'value' => $guid,
	],
];

foreach ($fields as $field) {
	echo elgg_view_input($field);
}

$footer = elgg_view_input('submit', [
	'value' => elgg_echo('save'),
]);
elgg_set_form_footer($footer);