<?php
/**
 * Plugin database schema
 * WARNING: 
 * 	dbDelta() doesn't like empty lines in schema string, so don't put them there;
 *  WPDB doesn't like NULL values so better not to have them in the tables;
 */

/**
 * The database character collate.
 * @var string
 * @global string
 * @name $charset_collate
 */
$charset_collate = '';

// Declare these as global in case schema.php is included from a function.
global $wpdb, $plugin_queries;

if ( ! empty($wpdb->charset))
	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
if ( ! empty($wpdb->collate))
	$charset_collate .= " COLLATE $wpdb->collate";
	
$table_prefix = PMXE_Plugin::getInstance()->getTablePrefix();

$plugin_queries = <<<SCHEMA
CREATE TABLE {$table_prefix}exports (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	attch_id BIGINT(20) UNSIGNED NOT NULL,
	options LONGTEXT,
	scheduled VARCHAR(64) NOT NULL DEFAULT '',
	registered_on DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',	
	friendly_name VARCHAR(64) NOT NULL DEFAULT '',	
	exported BIGINT(20) NOT NULL DEFAULT 0,
	canceled BOOL NOT NULL DEFAULT 0,  	
  	canceled_on DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  	settings_update_on DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  	last_activity DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
  	processing BOOL NOT NULL DEFAULT 0,
  	executing BOOL NOT NULL DEFAULT 0,
  	triggered BOOL NOT NULL DEFAULT 0,
	PRIMARY KEY  (id)
) $charset_collate;
SCHEMA;
