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


// Plugin added one table; remove it
$smcFunc['db_drop_table']('{db_prefix}mm_profiles', array());
