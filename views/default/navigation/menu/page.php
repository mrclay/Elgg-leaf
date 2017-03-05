<?php
/**
 * Page menu
 *
 * @uses $vars['menu']
 * @uses $vars['selected_item']
 * @uses $vars['class']
 * @uses $vars['name']
 * @uses $vars['show_section_headers']
 */

$headers = elgg_extract('show_section_headers', $vars, false);

if (empty($vars['name'])) {
	$msg = elgg_echo('view:missing_param', array('name', 'navigation/menu/page'));
	elgg_log($msg, 'WARNING');
	$vars['name'] = '';
}

$class = 'elgg-menu elgg-menu-page';
if (isset($vars['class'])) {
	$class = "$class {$vars['class']}";
}

if (isset($vars['selected_item'])) {
	$parent = $vars['selected_item']->getParent();

	while ($parent) {
		$parent->setSelected();
		$parent = $parent->getParent();
	}
}

foreach ($vars['menu'] as $section => $menu_items) {
	foreach ($menu_items as &$menu_item) {
		if ($menu_item instanceof ElggMenuItem) {
			$child_menu_vars = $menu_item->getChildMenuOptions();
			$child_menu_vars['display'] = 'toggle';
			$menu_item->setChildMenuOptions($child_menu_vars);
		}
	}
	
	echo elgg_view('navigation/menu/elements/section', array(
		'items' => $menu_items,
		'class' => "$class elgg-menu-page-$section",
		'section' => $section,
		'name' => $vars['name'],
		'show_section_headers' => $headers
	));
}
