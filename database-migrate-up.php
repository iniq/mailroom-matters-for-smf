<?php
// Handle running this file by using SSI.php
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF')) {
	require_once(dirname(__FILE__) . '/SSI.php');
} elseif (!defined('SMF')) {
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
}


global $db_prefix, $smcFunc;
	
// Make sure that we have the package database functions.
if (!array_key_exists('db_create_table', $smcFunc)) {
	db_extend('Packages');
}


// Create table to store Mailroom Matters profile fields
$columns = array(
	array(
		'name' => 'id_mmprofile',
		'type' => 'int',
		'size' => '11',
		'auto' => true,
		),
	array(
		'name' => 'id_member',
		'type' => 'mediumint',
		'size' => '8',
		'default' => 0,
		),
	array(
		'name' => 'newspaper_name',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'address',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'address2',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'city',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'state',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'country',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'zip',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'phone_emergency',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'phone_security',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'primary_name',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'primary_position',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'primary_phone',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'primary_fax',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'primary_email',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'secondary_name',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'secondary_position',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'secondary_phone',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'secondary_fax',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'secondary_email',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'extension_requests_name',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'extension_requests_position',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'extension_requests_phone',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'extension_requests_fax',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'extension_requests_email',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'extension_requests_comments',
		'type' => 'text',
		),
	array(
		'name' => 'circulation_day',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'circulation_volume',
		'type' => 'varchar',
		'size' => 128,
		),
	array(
		'name' => 'inserting_equipment',
		'type' => 'text',
		),
	array(
		'name' => 'forklifts',
		'type' => 'int',
		'size' => '5',
		),
	array(
		'name' => 'pallet_jacks',
		'type' => 'int',
		'size' => '5',
		),
	array(
		'name' => 'staff_receiving',
		'type' => 'int',
		'size' => '5',
		),
	array(
		'name' => 'staff_inserting',
		'type' => 'int',
		'size' => '5',
		),
	array(
		'name' => 'commercial_printing',
		'type' => 'tinyint',
		'size' => 1,
		'default' => 0
		),
	array(
		'name' => 'load_packaging',
		'type' => 'text',
		),
	array(
		'name' => 'hours_monday',
		'type' => 'varchar',
		'size' => 30,
		'null' => true,
		'default' => null,
		),
	array(
		'name' => 'hours_tuesday',
		'type' => 'varchar',
		'size' => 30,
		'null' => true,
		'default' => null,
		),
	array(
		'name' => 'hours_wednesday',
		'type' => 'varchar',
		'size' => 30,
		'null' => true,
		'default' => null,
		),
	array(
		'name' => 'hours_thursday',
		'type' => 'varchar',
		'size' => 30,
		'null' => true,
		'default' => null,
		),
	array(
		'name' => 'hours_friday',
		'type' => 'varchar',
		'size' => 30,
		'null' => true,
		'default' => null,
		),
	array(
		'name' => 'hours_saturday',
		'type' => 'varchar',
		'size' => 30,
		'null' => true,
		'default' => null,
		),
	array(
		'name' => 'hours_sunday',
		'type' => 'varchar',
		'size' => 30,
		'null' => true,
		'default' => null,
		),
	array(
		'name' => 'insert_advance_days',
		'type' => 'int',
		'size' => '5',
		'default' => 0
		),
	array(
		'name' => 'unloading_equipment',
		'type' => 'text',
		),
	array(
		'name' => 'receiving_challenges_comments',
		'type' => 'text',
		),
	array(
		'name' => 'driver_privileges_access',
		'type' => 'text',
		),
	array(
		'name' => 'special_requirements',
		'type' => 'text',
		'null' => true,
		'default' => null
		),
	array(
		'name' => 'digital_pictures',
		'type' => 'tinyint',
		'size' => 1,
		'default' => 0
		)
	);
$indicies = array(
	array(
		'type' => 'primary',
		'columns' => array('id_mmprofile')
		),
	array(
		'type' => 'unique',
		'columns' => array('id_member')
		)
	);
$smcFunc['db_create_table']('{db_prefix}mm_profiles', $columns, $indicies, array(), 'ignore');
