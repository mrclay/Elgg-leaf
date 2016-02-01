<?php
/**
 * Elgg profile plugin edit default profile action removal
 *
 */

$id = get_input('id');
if (!is_string($id) || !preg_match('~^[0-9a-z]+$~', $id)) {
	register_error(elgg_echo('profile:editdefault:delete:fail'));
	forward(REFERER);
}

$fieldlist = (string)elgg_get_config('profile_custom_fields');
if ($fieldlist === '') {
	$fields = [];
} else {
	$fields = explode(',', $fieldlist);
}

$fields = array_diff($fields, [$id]);

$fieldlist = implode(',', $fields);

if (unset_config("admin_defined_profile_$id") &&
	unset_config("admin_defined_profile_type_$id") &&
	elgg_save_config('profile_custom_fields', $fieldlist)) {
	
	system_message(elgg_echo('profile:editdefault:delete:success'));
} else {
	register_error(elgg_echo('profile:editdefault:delete:fail'));
}

forward(REFERER);