<?php
/**
 * Mailroommatters.php
 *
 * @package Mailroom Matters for SMF
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

	$context['linktree'][] = array(
		'url' => $scripturl . '?action=mailroom_matters;area=edit',
		'name' => 'Edit Profile',
	);

	// Find their profile, if they have made one already
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}mm_profiles
		WHERE id_member = {int:id_member}',
		array(
			'id_member' => _Mailroommatters_actorID(),
		)
	);

	$profile = $smcFunc['db_fetch_assoc']($request);

	if (isset($_REQUEST['save'])) {
		// @todo Actual save stuff
		$saveSuccess = true;

		if ($saveSuccess) {
			redirectexit('action=mailroom_matters;area=profile;mailroom=' . $profile['id_mmprofile']);
		}
	}

	// Didn't save
	$context['mailroommatters']['fields'] = _Mailroommatters_profileFields();

	$_GET['action'] = 'mailroom_matters';
	$context['page_title'] .= ' - Edit Your Profile';
	$context['mailroommatters']['profile'] = $profile;
	$context['mailroommatters']['top_header'] = $context['page_title'];
	$context['sub_template'] = 'mailroommatters_edit';
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

/**
 * Get a consistent definition of the MM profile fields
 */
function _Mailroommatters_profileFields() {
	return array(
		'company_details' => array(
			'type' => 'section',
			'label' => 'Newspaper/Company Contact Information',
			'fields' => array(
				'newspaper_name' => array(
					'database_field' => 'newspaper_name',
					'type' => 'text',
					'label' => 'Name'
				),
				'address' => array(
					'database_field' => 'address',
					'type' => 'text',
					'label' => 'Address'
				),
				'address2' => array(
					'database_field' => 'address2',
					'type' => 'text',
					'label' => 'Address Line 2'
				),
				'city' => array(
					'database_field' => 'city',
					'type' => 'text',
					'label' => 'City'
				),
				'state' => array(
					'database_field' => 'state',
					'type' => 'text',
					'label' => 'State'
				),
				'country' => array(
					'database_field' => 'country',
					'type' => 'text',
					'label' => 'Country'
				),
				'zip' => array(
					'database_field' => 'zip',
					'type' => 'text',
					'label' => 'Zip Code'
				),
				'phone_emergency' => array(
					'database_field' => 'phone_emergency',
					'type' => 'text',
					'label' => 'Emergency Phone Number'
				),
				'phone_security' => array(
					'database_field' => 'phone_security',
					'type' => 'text',
					'label' => 'Security Guard Phone Number'
				),
			)
		),
		'primary_contact' => array(
			'type' => 'section',
			'label' => 'Primary Point of Contact',
			'fields' => array(
				'primary_name' => array(
					'database_field' => 'primary_name',
					'type' => 'text',
					'label' => 'Primary Point of Contact'
				),
				'primary_position' => array(
					'database_field' => 'primary_position',
					'type' => 'text',
					'label' => 'Position'
				),
				'primary_phone' => array(
					'database_field' => 'primary_phone',
					'type' => 'text',
					'label' => 'Phone'
				),
				'primary_fax' => array(
					'database_field' => 'primary_fax',
					'type' => 'text',
					'label' => 'Fax'
				),
				'primary_email' => array(
					'database_field' => 'primary_email',
					'type' => 'text',
					'label' => 'Email'
				),
			)
		),
		'secondary_contact' => array(
			'type' => 'section',
			'label' => 'Secondary Point of Contact',
			'fields' => array(
				'secondary_name' => array(
					'database_field' => 'secondary_name',
					'type' => 'text',
					'label' => 'Primary Point of Contact'
				),
				'secondary_position' => array(
					'database_field' => 'secondary_position',
					'type' => 'text',
					'label' => 'Position'
				),
				'secondary_phone' => array(
					'database_field' => 'secondary_phone',
					'type' => 'text',
					'label' => 'Phone'
				),
				'secondary_fax' => array(
					'database_field' => 'secondary_fax',
					'type' => 'text',
					'label' => 'Fax'
				),
				'secondary_email' => array(
					'database_field' => 'secondary_email',
					'type' => 'text',
					'label' => 'Email'
				),
			)
		),
		'extension_contact' => array(
			'type' => 'section',
			'label' => 'Extension Requests Contact',
			'fields' => array(
				'extension_requests_name' => array(
					'database_field' => 'extension_requests_name',
					'type' => 'text',
					'label' => 'Name'
				),
				'extension_requests_position' => array(
					'database_field' => 'extension_requests_position',
					'type' => 'text',
					'label' => 'Position'
				),
				'extension_requests_phone' => array(
					'database_field' => 'extension_requests_phone',
					'type' => 'text',
					'label' => 'Phone'
				),
				'extension_requests_fax' => array(
					'database_field' => 'extension_requests_fax',
					'type' => 'text',
					'label' => 'Fax'
				),
				'extension_requests_email' => array(
					'database_field' => 'extension_requests_email',
					'type' => 'text',
					'label' => 'Email'
				),
				'extension_requests_comments' => array(
					'database_field' => 'extension_requests_comments',
					'type' => 'textarea',
					'label' => 'Comments'
				),
			)
		),
		'equipment_capabilities' => array(
			'type' => 'section',
			'label' => 'Company Details and Equipment',
			'fields' => array(
				'circulation_day' => array(
					'database_field' => 'circulation_day',
					'type' => 'text',
					'label' => 'Circulation Day'
				),
				'circulation_volume' => array(
					'database_field' => 'circulation_volume',
					'type' => 'text',
					'label' => 'Circulation Volume'
				),
				'forklifts' => array(
					'database_field' => 'forklifts',
					'type' => 'number',
					'label' => 'Number of Forklifts'
				),
				'pallet_jacks' => array(
					'database_field' => 'pallet_jacks',
					'type' => 'number',
					'label' => 'Number of Pallet Jacks'
				),
				'staff_receiving' => array(
					'database_field' => 'staff_receiving',
					'type' => 'number',
					'label' => 'Number of Receiving Staff'
				),
				'staff_inserting' => array(
					'database_field' => 'staff_inserting',
					'type' => 'number',
					'label' => 'Number of Inserting Staff'
				),
				'commercial_printing' => array(
					'database_field' => 'commercial_printing',
					'type' => 'check',
					'label' => 'Commercial Printing',
					'value' => '1'
				),
				'digital_pictures' => array(
					'database_field' => 'digital_pictures',
					'type' => 'check',
					'label' => 'Provide Digital Pictures for Failed Loads',
					'value' => '1'
				),
				'load_packaging' => array(
					'database_field' => 'load_packaging',
					'type' => 'select',
					'label' => 'Load Packaging',
					'options' => array('', 'Palletize', '?')
				),
				'insert_advance_days' => array(
					'database_field' => 'insert_advance_days',
					'type' => 'number',
					'label' => 'Advanced Insert Receiving Times',
					'subtext' => 'Days in advance that inserts must be received'
				),
				'inserting_equipment' => array(
					'database_field' => 'inserting_equipment',
					'type' => 'textarea',
					'label' => 'Inserting Equipment'
				),
				'unloading_equipment' => array(
					'database_field' => 'unloading_equipment',
					'type' => 'textarea',
					'label' => 'Special Unloading Equipment'
				),
				'receiving_challenges_comments' => array(
					'database_field' => 'receiving_challenges_comments',
					'type' => 'textarea',
					'label' => 'Receiving Challenges or Comments'
				),
				'driver_privileges_access' => array(
					'database_field' => 'driver_privileges_access',
					'type' => 'textarea',
					'label' => 'Driver Privileges and Access'
				),
				'special_requirements' => array(
					'database_field' => 'special_requirements',
					'type' => 'textarea',
					'label' => 'Special Requirements'
				),
			)
		),
		'hours' => array(
			'type' => 'section',
			'label' => 'Receiving Days and Hours for Insert Delivery',
			'fields' => array(
				'hours_monday' => array(
					'database_field' => 'hours_monday',
					'type' => 'text',
					'label' => 'Monday'
				),
				'hours_tuesday' => array(
					'database_field' => 'hours_tuesday',
					'type' => 'text',
					'label' => 'Tuesday'
				),
				'hours_wednesday' => array(
					'database_field' => 'hours_wednesday',
					'type' => 'text',
					'label' => 'Wednesday'
				),
				'hours_thursday' => array(
					'database_field' => 'hours_thursday',
					'type' => 'text',
					'label' => 'Thursday'
				),
				'hours_friday' => array(
					'database_field' => 'hours_friday',
					'type' => 'text',
					'label' => 'Friday'
				),
				'hours_saturday' => array(
					'database_field' => 'hours_saturday',
					'type' => 'text',
					'label' => 'Saturday'
				),
				'hours_sunday' => array(
					'database_field' => 'hours_sunday',
					'type' => 'text',
					'label' => 'Sunday'
				),
			)
		)
	);
}
