<?php
/**
 * Profile fields.
 * 
 * @todo Needs some review
 */

// List form elements
$n = 0;
$defaults = _elgg_get_default_profile_fields();
$disabled_fields = $defaults;
$loaded_defaults = array();
$items = array();
$fieldlist = elgg_get_config('profile_custom_fields');
if ($fieldlist || $fieldlist === '0') {
	$fieldlistarray = explode(',', $fieldlist);
	foreach ($fieldlistarray as $listitem) {
		// handle built-in fields
		if (isset($defaults[$listitem])) {
			unset($disabled_fields[$listitem]);
			$items[] = (object)[
				'translation' => elgg_echo("profile:$listitem"),
				'shortname' => $listitem,
				'name' => $listitem,
				'type' => $defaults[$listitem],
			];
			continue;
		}

		$translation = elgg_get_config("admin_defined_profile_$listitem");
		$type = elgg_get_config("admin_defined_profile_type_$listitem");
		if ($translation && $type) {
			$items[] = (object)[
				'translation' => $translation,
				'shortname' => $listitem,
				'name' => "admin_defined_profile_$listitem",
				'type' => elgg_echo("profile:field:$type"),
			];
		}
	}
} else {
	// using default fields
	$disabled_fields = [];
}
?>
<ul id="elgg-profile-fields" class="mvm">
<?php

foreach ($items as $item) {
	echo elgg_view("profile/", array('value' => $item->translation));

	//$even_odd = ( 'odd' != $even_odd ) ? 'odd' : 'even';
	$url = elgg_view('output/url', array(
		'href' => "action/profile/fields/delete?id={$item->shortname}",
		'text' => elgg_view_icon('delete-alt'),
		'is_action' => true,
		'is_trusted' => true,
	));
	$type = elgg_echo($item->type);
	$drag_arrow = elgg_view_icon("drag-arrow", "elgg-state-draggable");
	echo <<<HTML
<li id="$item->shortname" class="clearfix">
	$drag_arrow
	<b><span id="elgg-profile-field-{$item->shortname}" class="elgg-state-editable">{$item->translation}</span></b> [$type] $url
</li>
HTML;
}

?>
</ul>
<?php

if (!$disabled_fields) {
	return;
}

?>
<h3>Add a predefined field</h3>
<ul id="elgg-disabled-profile-fields" class="mll mtm">
<?php

foreach ($disabled_fields as $shortname => $type) {

	echo "<li>";
	$text = elgg_view_icon('round-plus')
			. " <b>" . elgg_echo("profile:$shortname") . "</b> [" . elgg_echo("profile:field:$type") . "]";
	echo elgg_view('output/url', array(
		'href' => "action/profile/fields/enable?id={$shortname}",
		'text' => $text,
		'is_action' => true,
	));
	echo "</li>";
}

?>
</ul>