<?php
/**
 * Elgg long text input (plaintext)
 * Displays a long text input field that should not be overridden by wysiwyg editors.
 *
 * @package Elgg
 * @subpackage Core
 *
 * @uses $vars['value']    The current value, if any
 * @uses $vars['name']     The name of the input field
 * @uses $vars['class']    Additional CSS class
 * @uses $vars['disabled']
 */

$vars['class'] = elgg_extract_class($vars, 'elgg-input-plaintext form-control');

$defaults = [
	'value' => '',
	'rows' => '10',
	'cols' => '50',
	'disabled' => false,
];

$vars = array_merge($defaults, $vars);

$value = htmlspecialchars($vars['value'], ENT_QUOTES, 'UTF-8');
unset($vars['value']);

echo elgg_format_element('textarea', $vars, $value);
