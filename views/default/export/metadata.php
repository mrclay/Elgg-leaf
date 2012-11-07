<?php
/**
 * Elgg metadata export.
 * Displays a metadata item using the current view.
 *
 * @package Elgg
 * @subpackage Core
 */

$m = $vars['metadata'];
/* @var ElggMetadata $m */
$e = get_entity($m->entity_guid);

$link = "GUID:{$m->entity_guid}";
if ($e) {
	$url = htmlspecialchars($e->getURL(), ENT_QUOTES, 'UTF-8');
	$link = "<a href=\"$url\">$link</a>";
}

$n = htmlspecialchars($m->name, ENT_QUOTES, 'UTF-8');
$v = htmlspecialchars($m->value, ENT_QUOTES, 'UTF-8');

?>
<p class="margin-none"><?php echo $link; ?>: <b><?php echo $n; ?></b> <?php echo $v; ?></p>