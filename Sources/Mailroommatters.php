<?php
/**
 * Mailroommatters.php
 * Mod: Mailroom Matters for SMF
 *
 * @author iniq https://github.com/iniq
 * @version 0.1
 */

if (!defined('SMF')) {
	die('Hacking attempt...');
}
	
/**
 * Dispatcher method for the module
 */
function MailroommattersMain() {
	global $context;

	isAllowedTo('profile_extra_own');
	loadTemplate('Mailroommatters');

	$context['linktree'][] = array(
		'url' => $scripturl . '?action=mailroom_matters',
		'name' => 'Mailroom Matters'
	);
	$context['page_title'] = 'Mailroom Matters';

	switch (strtolower(@$_GET['area'])) {
		case 'me':
			MailroomMattersView($me = true);
			break;

		case 'profile':
			MailroomMattersView();
			break;

		case 'edit':
			MailroomMattersEdit();
			break;

		default:
			MailroomMattersIndex();
	}
}

/**
 * Show a Mailroom Matters profile
 */
function MailroomMattersView($me = false) {
	global $smcFunc, $context;
	die('todo');
}

/**
 * Edit/save a Mailroom Matters profile belonging to the current User
 */
function MailroomMattersEdit() {
	global $smcFunc, $context;
	die('todo');
}

/**
 * Show an index/listing of all current Mailroom Matters profiles
 */
function MailroomMattersIndex() {
	global $smcFunc, $context;

	// Get the list of all existing profiles
	$profiles = array();
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}mm_profiles
		ORDER BY newspaper_name ASC'
	);

	while ($row = $smcFunc['db_fetch_assoc']($request)) {
		$profiles[] = $row;
	}
	$smcFunc['db_free_result']($request);

	$_GET['action'] = 'mailroom_matters';
	$context['page_title'] .= ' Profiles';
	$context['mailroommatters']['profiles'] = $profiles;
	$context['mailroommatters']['top_header'] = $context['page_title'];
	$context['sub_template'] = 'mailroommatters_index';
}

/**
 * Helper to read actor's ID
 */
function _Mailroommatters_actorID() {
	global $smcFunc, $context;
	if (empty($context['user']['id'])) {
		return false;
	}

	return $context['user']['id'];
}
