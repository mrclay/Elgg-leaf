<?php
/**
 * Settings form body
 *
 * @uses $vars['values']
 */

elgg_require_js('forms/developers/settings');

if (!elgg_is_xhr()) {
	echo elgg_view('output/longtext', [
		'value' => elgg_echo('elgg_dev_tools:settings:explanation'),
	]);
}

foreach ($vars['data'] as $name => $info) {
	$info['name'] = $name;

	$echo_vars = [];
	if (isset($info['_label_icon'])) {
		$echo_vars[] = elgg_view_icon($info['_label_icon']);
	}
	unset($info['_label_icon']);
	$info['#label'] = elgg_echo("developers:label:$name", $echo_vars);

	$info['#help'] = elgg_echo("developers:help:$name");

	echo elgg_view_field($info);
}

// form footer
$footer = elgg_view_field([
	'#type' => 'submit',
	'value' => elgg_echo('developers:label:submit'),
]);

elgg_set_form_footer($footer);
