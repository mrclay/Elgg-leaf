<?php

if (empty($vars['cvid']) || empty($vars['view'])) {
	elgg_log('The cviews/wrapper view requires params cvid and view', 'ERROR');
	return;
}

if (!preg_match('~^[a-zA-Z0-9\\-]+$~', $vars['cvid'])) {
	elgg_log('cvid may contain only [a-zA-Z0-9] and hyphens', 'ERROR');
	return;
}

if (0 !== strpos($vars['view'], 'cviews/')) {
	elgg_log("The view did not begin with 'cviews/'", 'ERROR');
	return;
}

if (!elgg_view_exists($vars['view'])) {
	elgg_log("The view {$vars['view']} does not exist.", 'ERROR');
	return;
}

if (isset($vars['vars']['CONFIG'])) {
	elgg_log("The view vars should not be copied from \$vars passed into a view.", 'ERROR');
	return;
}

$element = empty($vars['inline']) ? 'div' : 'span';
$view_vars = (object)(empty($vars['vars']) ? [] : $vars['vars']);

$json = json_encode([
	'cvid' => $vars['cvid'],
	'view' => $vars['view'],
	'vars' => $view_vars,
]);
if (!$json) {
	elgg_log("The view vars could not be JSON encoded.", 'ERROR');
	return;
}

$attrs = elgg_format_attributes([
	'id' => "elgg-cview-{$vars['cvid']}",
	'data-elgg-cview' => $json,
]);

echo "<$element $attrs>";
echo elgg_view($vars['view'], $vars['vars']);
echo "</$element>";
