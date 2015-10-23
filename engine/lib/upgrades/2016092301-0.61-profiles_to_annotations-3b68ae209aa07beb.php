<?php
/**
 * Elgg 0.61 upgrade 2016092301
 * profiles_to_annotations
 *
 * Migrate profile metadata to annotations
 */

$names = array_keys(elgg_get_config('profile_fields'));
$db = _elgg_services()->db;

foreach ($names as $name) {
	$metadata_name_id = elgg_get_metastring_id($name);
	$annotation_name_id = elgg_get_metastring_id("profile:$name");

	$db->updateData("
		INSERT INTO {$db->prefix}annotations
		      (entity_guid, name_id,               value_id, value_type, owner_guid, access_id, time_created, enabled)
		SELECT entity_guid, {$annotation_name_id}, value_id, value_type, owner_guid, access_id, time_created, enabled
		FROM {$db->prefix}metadata
		WHERE name_id = {$metadata_name_id}
		AND entity_guid IN (
			SELECT guid FROM {$db->prefix}users_entity
		)
	");
}
