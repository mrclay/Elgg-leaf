--
-- Elgg database schema
--

-- record membership in an access collection
CREATE TABLE `prefix_access_collection_membership` (
  `user_guid` int(11) NOT NULL,
  `access_collection_id` int(11) NOT NULL,
  PRIMARY KEY (`user_guid`,`access_collection_id`)
);

-- define an access collection
CREATE TABLE `prefix_access_collections` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` text NOT NULL,
  `owner_guid` int(20) NOT NULL,
  `site_guid` int(20) NOT NULL DEFAULT '0'
);

-- store an annotation on an entity
CREATE TABLE `prefix_annotations` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `entity_guid` int(20) NOT NULL,
  `name_id` int(11) NOT NULL,
  `value_id` int(11) NOT NULL,
  `value_type` text NOT NULL,
  `owner_guid` int(20) NOT NULL,
  `access_id` int(11) NOT NULL,
  `time_created` int(11) NOT NULL,
  `enabled` text NOT NULL DEFAULT 'yes'
);

-- api keys for old web services
CREATE TABLE `prefix_api_users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `site_guid` int(20) DEFAULT NULL,
  `api_key` varchar(40) DEFAULT NULL,
  `secret` varchar(40) NOT NULL,
  `active` int(1) DEFAULT '1'
);

-- site specific configuration
CREATE TABLE `prefix_config` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `site_guid` int(11) NOT NULL,
  PRIMARY KEY (`name`,`site_guid`)
);

-- application specific configuration
CREATE TABLE `prefix_datalists` (
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
);

-- primary entity table
CREATE TABLE `prefix_entities` (
  `guid` INTEGER PRIMARY KEY AUTOINCREMENT,
  `type` text NOT NULL,
  `subtype` int(11) DEFAULT NULL,
  `owner_guid` int(20) NOT NULL,
  `site_guid` int(20) NOT NULL,
  `container_guid` int(20) NOT NULL,
  `access_id` int(11) NOT NULL,
  `time_created` int(11) NOT NULL,
  `time_updated` int(11) NOT NULL,
  `last_action` int(11) NOT NULL DEFAULT '0',
  `enabled` text NOT NULL DEFAULT 'yes'
);

-- relationships between entities
CREATE TABLE `prefix_entity_relationships` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `guid_one` int(20) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `guid_two` int(20) NOT NULL,
  `time_created` int(11) NOT NULL
);

-- entity type/subtype pairs
CREATE TABLE `prefix_entity_subtypes` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `type` text NOT NULL,
  `subtype` varchar(50) NOT NULL,
  `class` varchar(50) NOT NULL DEFAULT ''
);

-- cache lookups of latitude and longitude for place names
CREATE TABLE `prefix_geocode_cache` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `location` varchar(128) DEFAULT NULL,
  `lat` varchar(20) DEFAULT NULL,
  `long` varchar(20) DEFAULT NULL
);

-- secondary table for group entities
CREATE TABLE `prefix_groups_entity` (
  `guid` int(20) NOT NULL PRIMARY KEY,
  `name` text NOT NULL,
  `description` text NOT NULL
);

-- cache for hmac signatures for old web services
CREATE TABLE `prefix_hmac_cache` (
  `hmac` varchar(255) NOT NULL PRIMARY KEY,
  `ts` int(11) NOT NULL
);

-- metadata that describes an entity
CREATE TABLE `prefix_metadata` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `entity_guid` int(20) NOT NULL,
  `name_id` int(11) NOT NULL,
  `value_id` int(11) NOT NULL,
  `value_type` text NOT NULL,
  `owner_guid` int(20) NOT NULL,
  `access_id` int(11) NOT NULL,
  `time_created` int(11) NOT NULL,
  `enabled` text NOT NULL DEFAULT 'yes'
);

-- string normalization table for metadata and annotations
CREATE TABLE `prefix_metastrings` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `string` text NOT NULL
);

-- secondary table for object entities
CREATE TABLE `prefix_objects_entity` (
  `guid` int(20) NOT NULL PRIMARY KEY,
  `title` text NOT NULL,
  `description` text NOT NULL
);

-- settings for an entity
CREATE TABLE `prefix_private_settings` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `entity_guid` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `value` text NOT NULL
);

-- queue for asynchronous operations
CREATE TABLE `prefix_queue` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `name` varchar(255) NOT NULL,
  `data` mediumblob NOT NULL,
  `timestamp` int(11) NOT NULL,
  `worker` varchar(32) NULL
);

-- activity stream
CREATE TABLE `prefix_river` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `type` varchar(8) NOT NULL,
  `subtype` varchar(32) NOT NULL,
  `action_type` varchar(32) NOT NULL,
  `access_id` int(11) NOT NULL,
  `view` text NOT NULL,
  `subject_guid` int(11) NOT NULL,
  `object_guid` int(11) NOT NULL,
  `target_guid` int(11) NOT NULL,
  `annotation_id` int(11) NOT NULL,
  `posted` int(11) NOT NULL,
  `enabled` text NOT NULL DEFAULT 'yes'
);

-- secondary table for site entities
CREATE TABLE `prefix_sites_entity` (
  `guid` int(20) NOT NULL PRIMARY KEY,
  `name` text NOT NULL,
  `description` text NOT NULL,
  `url` varchar(255) NOT NULL
);

-- log activity for the admin
CREATE TABLE `prefix_system_log` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `object_id` int(11) NOT NULL,
  `object_class` varchar(50) NOT NULL,
  `object_type` varchar(50) NOT NULL,
  `object_subtype` varchar(50) NOT NULL,
  `event` varchar(50) NOT NULL,
  `performed_by_guid` int(11) NOT NULL,
  `owner_guid` int(11) NOT NULL,
  `access_id` int(11) NOT NULL,
  `enabled` text NOT NULL DEFAULT 'yes',
  `time_created` int(11) NOT NULL,
  `ip_address` varchar(46) NOT NULL
);

-- session table for old web services
CREATE TABLE `prefix_users_apisessions` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_guid` int(20) NOT NULL,
  `site_guid` int(20) NOT NULL,
  `token` varchar(40) DEFAULT NULL,
  `expires` int(11) NOT NULL
);

-- secondary table for user entities
CREATE TABLE `prefix_users_entity` (
  `guid` int(20) NOT NULL PRIMARY KEY,
  `name` text NOT NULL,
  `username` varchar(128) NOT NULL UNIQUE DEFAULT '',
  `password` varchar(32) NOT NULL DEFAULT '',
  `salt` varchar(8) NOT NULL DEFAULT '',
  -- 255 chars is recommended by PHP.net to hold future hash formats
  `password_hash` varchar(255) NOT NULL DEFAULT '',
  `email` text NOT NULL,
  `language` varchar(6) NOT NULL DEFAULT '',
  `banned` text NOT NULL DEFAULT 'no',
  `admin` text NOT NULL DEFAULT 'no',
  `last_action` int(11) NOT NULL DEFAULT '0',
  `prev_last_action` int(11) NOT NULL DEFAULT '0',
  `last_login` int(11) NOT NULL DEFAULT '0',
  `prev_last_login` int(11) NOT NULL DEFAULT '0'
);

-- user remember me cookies
CREATE TABLE `prefix_users_remember_me_cookies` (
  `code` varchar(32) NOT NULL PRIMARY KEY,
  `guid` int(20) NOT NULL,
  `timestamp` int(11) NOT NULL
);

-- user sessions
CREATE TABLE `prefix_users_sessions` (
  `session` varchar(255) NOT NULL PRIMARY KEY,
  `ts` int(11) NOT NULL DEFAULT '0',
  `data` mediumblob
);
