<?php
/**
 * Icon display
 *
 * @package ElggGroups
 */

if (!isset($page)) {
	die('This file cannot be called directly.');
}

$group_guid = get_input('group_guid');

/* @var ElggGroup $group */
$group = get_entity($group_guid);
if (!($group instanceof ElggGroup)) {
	header("HTTP/1.1 404 Not Found");
	exit;
}

// If is the same ETag, content didn't changed.
$etag = $group->icontime . $group_guid;
if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == "\"$etag\"") {
	header("HTTP/1.1 304 Not Modified");
	exit;
}

$size = strtolower(get_input('size'));
if (!in_array($size, array('large', 'medium', 'small', 'tiny', 'master', 'topbar')))
	$size = "medium";

$success = false;

$filehandler = new ElggFile();
$filehandler->owner_guid = $group->owner_guid;
$filehandler->setFilename("groups/" . $group->guid . $size . ".jpg");

$success = false;
if ($filehandler->open("read")) {
	if ($contents = $filehandler->read($filehandler->getSize())) {
		$success = true;
	}
}

if (!$success) {
	$view = "groups/default{$size}.gif";
	$location = elgg_get_view_location($view) . "default/$view";
	$contents = @file_get_contents($location);
	header("Content-type: image/gif");
} else {
	header("Content-type: image/jpeg");
}

header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', strtotime("+10 days")), true);
header("Pragma: public");
header("Cache-Control: public");
header("Content-Length: " . strlen($contents));
header("ETag: \"$etag\"");
echo $contents;
