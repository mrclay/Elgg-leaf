<?php

/**
 * Default entity delete action
 */
$guid = get_input('guid');
$entity = get_entity($guid);

if (!$entity instanceof ElggEntity) {
	return elgg_error_response(elgg_echo('entity:delete:item_not_found'), REFERRER, ELGG_HTTP_NOT_FOUND);
}

if (!$entity->canDelete()) {
	return elgg_error_response(elgg_echo('entity:delete:permission_denied'), REFERRER, ELGG_HTTP_FORBIDDEN);
}

set_time_limit(0);

// determine what name to show on success
$display_name = $entity->getDisplayName();
if (!$display_name) {
	$display_name = elgg_echo('entity:delete:item');
}

$type = $entity->getType();
$subtype = $entity->getSubtype();
$container = $entity->getContainerEntity();

// Get a reference of an entity to send back to the client
// Doing this before the entity is deleted to avoid problems with calling ElggEntity::toObject() after it is deleted
$response_data = [
	'entity' => (array) $entity->toObject(),
];

if (!$entity->canDelete() || !$entity->delete()) {
	return elgg_error_response(elgg_echo('entity:delete:fail', array($display_name)), REFERRER, ELGG_HTTP_FORBIDDEN);
}

// determine forward URL
$forward_url = get_input('forward_url');
if (!$forward_url) {
	$forward_url = REFERRER;
	$referrer_url = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	$site_url = elgg_get_site_url();
	if ($referrer_url && 0 == strpos($referrer_url, $site_url)) {
		$referrer_path = substr($referrer_url, strlen($site_url));
		$segments = explode('/', $referrer_path);
		if (in_array($guid, $segments)) {
			// referrer URL contains a reference to the entity that will be deleted
			$forward_url = ($container) ? $container->getURL() : '';
		}
	} else if ($container) {
		$forward_url = $container->getURL() ? : '';
	}
}

$success_keys = array(
	"entity:delete:$type:$subtype:success",
	"entity:delete:$type:success",
	"entity:delete:success",
);

$message = '';
foreach ($success_keys as $success_key) {
	if (elgg_language_key_exists($success_key)) {
		$message = elgg_echo($success_key, array($display_name));
	}
}

return elgg_ok_response($response_data, $message, $forward_url);
