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
	isAllowedTo('profile_extra_own');
	
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
	die('todo');
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
