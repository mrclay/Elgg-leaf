<?php
/**
 * Elgg relationship export.
 * Displays a relationship using the current view.
 *
 * @package Elgg
 * @subpackage Core
 */

$r = $vars['relationship'];
/* @var ElggRelationship $r */

$e1 = get_entity($r->guid_one);
$link1 = "GUID:{$r->guid_one}";
if ($e1) {
	$url = htmlspecialchars($e1->getURL(), ENT_QUOTES, 'UTF-8');
	$link1 = "<a href=\"$url\">$link1</a>";
}

$e2 = get_entity($r->guid_two);
$link2 = "GUID:{$r->guid_two}";
if ($e2) {
	$url = htmlspecialchars($e2->getURL(), ENT_QUOTES, 'UTF-8');
	$link2 = "<a href=\"$url\">$link2</a>";
}

$name = htmlspecialchars($r->relationship, ENT_QUOTES, 'UTF-8');

?>
<p class="margin-none"><?php echo $link1; ?><b><?php echo $name; ?></b><?php echo $link2; ?></p>