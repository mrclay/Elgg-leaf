<?php
/**
 * Elgg Entity export.
 * Displays an entity using the current view.
 *
 * @package Elgg
 * @subpackage Core
 */

$entity = $vars['entity'];
if (!$entity) {
	throw new InvalidParameterException(elgg_echo('InvalidParameterException:NoEntityFound'));
}
$options = array(
	'guid' => $entity->guid,
	'limit' => 0
);
$metadata = elgg_get_metadata($options);
$annotations = elgg_get_annotations($options);
$relationships = get_entity_relationships($entity->guid);

$exportable_values = $entity->getExportableValues();
?>
<div>
<h2><?php echo elgg_echo('Entity'); ?></h2>
<table class="elgg-table elgg-table-alt">
	<?php
		foreach ($entity as $k => $v) {
			if ((in_array($k, $exportable_values)) || (elgg_is_admin_logged_in())) {
				$k = htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
				$v = htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
?>
			<tr><th><?php echo $k; ?></th><td><?php echo $v; ?></td></tr>
<?php
			}
		}
	?>
</table>
</div>

<?php if ($metadata) { ?>
<div id="metadata" class="mtm">
<h2><?php echo elgg_echo('metadata'); ?></h2>
<table class="elgg-table elgg-table-alt">
	<?php
		foreach ($metadata as $m) {
			$k = htmlspecialchars($m->name, ENT_QUOTES, 'UTF-8');
			$v = htmlspecialchars($m->value, ENT_QUOTES, 'UTF-8');
?>
			<tr><th><?php echo $k; ?></th><td><?php echo $v; ?></td></tr>
<?php
		}
	?>
</table>
</div>
<?php } ?>

<?php if ($annotations) { ?>
<div id="annotations" class="mtm">
<h2><?php echo elgg_echo('annotations'); ?></h2>
<table class="elgg-table elgg-table-alt">
	<?php
		foreach ($annotations as $a) {
			$k = htmlspecialchars($a->name, ENT_QUOTES, 'UTF-8');
			$v = htmlspecialchars($a->value, ENT_QUOTES, 'UTF-8');
?>
			<tr><th><?php echo $k; ?></th><td><?php echo $v; ?></td></tr>
<?php
		}
	?>
</table>
</div>
<?php } ?>

<?php if ($relationships) { ?>
<div id="relationship" class="mtm">
<h2><?php echo elgg_echo('relationships'); ?></h2>
<table class="elgg-table elgg-table-alt">
	<?php
		foreach ($relationships as $r) {
			$n = htmlspecialchars($r->relationship, ENT_QUOTES, 'UTF-8');
?>
			<tr><th><?php echo $n; ?></th><td><?php echo $r->guid_two; ?></td></tr>
<?php
		}
	?>
</table>
</div>
<?php } ?>
