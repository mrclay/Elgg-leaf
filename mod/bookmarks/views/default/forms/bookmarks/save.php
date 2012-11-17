<?php
/**
 * Edit / add a bookmark
 *
 * @package Bookmarks
 */

// once elgg_view stops throwing all sorts of junk into $vars, we can use extract()
$title = elgg_extract('title', $vars, '');
$desc = elgg_extract('description', $vars, '');
$address = elgg_extract('address', $vars, '');
$tags = elgg_extract('tags', $vars, '');
$access_id = elgg_extract('access_id', $vars, ACCESS_DEFAULT);
$container_guid = elgg_extract('container_guid', $vars);
$guid = elgg_extract('guid', $vars, null);

?>
<div>
	<label><?php echo elgg_echo('title'); ?></label><br />
	<?php echo elgg_view('input/text', array('name' => 'title', 'value' => $title)); ?>
</div>
<div>
	<label><?php echo elgg_echo('bookmarks:address'); ?></label><br />
	<?php echo elgg_view('input/text', array('name' => 'address', 'value' => $address)); ?>
</div>
<div>
	<label><?php echo elgg_echo('description'); ?></label>
	<?php echo elgg_view('input/longtext', array('name' => 'description', 'value' => $desc)); ?>
</div>
<div>
	<label><?php echo elgg_echo('tags'); ?></label>
	<?php echo elgg_view('input/tags', array('name' => 'tags', 'value' => $tags)); ?>
</div>

<?php
echo elgg_view('input/categories', $vars);

if ($container_guid) {
	echo elgg_view('input/hidden', array('name' => 'container_guid', 'value' => $container_guid));
} else {
	$container_selector = elgg_view('bookmarks/input/container', array(
		'type' => 'object',
		'subtype' => 'bookmarks',
	));
	?>
<div>
	<label><?php echo elgg_echo('bookmarks:container'); ?></label><br />
	<?php echo $container_selector; ?>
</div>
<?php } ?>

<div>
	<label><?php echo elgg_echo('access'); ?></label><br />
	<?php echo elgg_view('input/access', array('name' => 'access_id', 'value' => $access_id)); ?>
</div>
<div class="elgg-foot">
<?php

if ($container_guid) {
	echo elgg_view('input/hidden', array('name' => 'container_guid', 'value' => $container_guid));
}

if ($guid) {
	echo elgg_view('input/hidden', array('name' => 'guid', 'value' => $guid));
}

echo elgg_view('input/submit', array('value' => elgg_echo("save")));

?>
</div>