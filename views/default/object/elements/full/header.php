<?php

/**
 * Object full view header
 *
 * @uses $vars['icon']        HTML for the content icon
 * @uses $vars['summary']     HTML for the content summary
 * @uses $vars['header_params'] Vars to pass to image block/header wrapper
 */

$summary = elgg_extract('summary', $vars);
if (!$summary) {
	return;
}

$icon = elgg_extract('icon', $vars);

$header_params = (array) elgg_extract('header_params', $vars, []);
$header_params['class'] = elgg_extract_class($header_params, ['elgg-listing-full-header', 'card-block']);

echo elgg_view_image_block($icon, $summary, $header_params);
