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

		case 'delete':
			MailroommattersDelete();
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

	$actorID = _Mailroommatters_actorID();
	$memberID = @$_GET['mailroom'];
	if ($me || empty($memberID)) {
		$memberID = $actorID;
	}
	$self = ($memberID == $actorID);

	$profile = _Mailroommatters_profile($memberID);

	if ($self && empty($profile)) {
		redirectexit('action=mailroom_matters;area=edit');
	}

	if (empty($profile)) {
		redirectexit('action=mailroom_matters');
	}

	$context['linktree'][] = array(
		'url' => $scripturl . '?action=mailroom_matters;area=profile;mailroom='. $memberID,
		'name' => ($self ? 'My' : 'View') .' Profile'
	);

	$context['mailroommatters']['fields'] = _Mailroommatters_profileFields();

	$_GET['action'] = 'mailroom_matters';
	$context['page_title'] .= ' - '. htmlspecialchars($profile['newspaper_name']);
	$context['mailroommatters']['profile'] = $profile;
	$context['mailroommatters']['top_header'] = $context['page_title'];
	$context['mailroommatters']['self'] = $self;
	$context['sub_template'] = 'mailroommatters_view';
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

	$memberID = intval(_Mailroommatters_actorID());
	$profile = _Mailroommatters_profile($memberID);

	if (isset($_REQUEST['save'])) {
		$context['error_message'] = '';

		// Check for various known error states
		if (empty($_POST['newspaper_name'])) {
			$context['error_message'] .= 'You must provide a Newspaper Name<br />';
		}

		// If no errors found, attempt the save
		if (empty($context['error_message'])) {
			$fieldList = array();
			$replacements = array('id_member' => $memberID);
			$profileFields = _Mailroommatters_profileFields();

			$inserting = (empty($profile));

			if ($inserting) {
				$fieldList[] = 'id_member = {int:id_member}';
			}

			_Mailroommatters_saveQuerySection($profileFields, $fieldList, $replacements);

			$updateQuery = sprintf(
				'%s {db_prefix}mm_profiles SET %s%s',
				($inserting ? 'INSERT INTO' : 'UPDATE'),
				implode(', ', $fieldList),
				($inserting ? '' : ' WHERE id_member = {int:id_member}')
				);

			if ($smcFunc['db_query']('', $updateQuery, $replacements)) {
				redirectexit('action=mailroom_matters;area=me');
			}
		}

		// Save failed, or field errors found?
		$profile = array_merge($profile, $_POST);
		if (empty($context['error_message'])) {
			$context['error_message'] = 'Save failed, please try again or contact the administrator';
		}
	}

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
 * Workflow for deleting a profile
 */
function MailroomMattersDelete() {
	global $smcFunc, $context;

	$memberID = @$_GET['mailroom'];
	$actorID = _Mailroommatters_actorID();
	$profile = _Mailroommatters_profile($memberID);

	if (empty($profile) || ($memberID != $actorID && !$context['user']['is_admin'])) {
		redirectexit('action=mailroom_matters');
	}

	$context['linktree'][] = array(
		'url' => $scripturl . '?action=mailroom_matters;area=delete;mailroom='. $memberID,
		'name' => 'Delete Profile',
	);

	if (isset($_REQUEST['confirm'])) {
		// Find the profile, if one exists for this member
		$request = $smcFunc['db_query']('', '
			DELETE
			FROM {db_prefix}mm_profiles
			WHERE id_member = {int:id_member}',
			array(
				'id_member' => $memberID,
			)
		);
		redirectexit('action=mailroom_matters');
	}

	$_GET['action'] = 'mailroom_matters';
	$context['page_title'] .= ' - Delete Profile';
	$context['mailroommatters']['profile'] = $profile;
	$context['mailroommatters']['top_header'] = $context['page_title'];
	$context['sub_template'] = 'mailroommatters_delete';
}

/**
 * Helper to read actor's ID
 */
function _Mailroommatters_actorID() {
	global $context;
	if (empty($context['user']['id'])) {
		return false;
	}

	return $context['user']['id'];
}

/**
 * Helper to read a profile from the database
 */
function _Mailroommatters_profile($memberID = null) {
	global $smcFunc;

	if (empty($memberID)) {
		$memberID = _Mailroommatters_actorID();
	}

	// Find the profile, if one exists for this member
	$request = $smcFunc['db_query']('', '
		SELECT *
		FROM {db_prefix}mm_profiles
		WHERE id_member = {int:id_member}',
		array(
			'id_member' => $memberID,
		)
	);

	return $smcFunc['db_fetch_assoc']($request);
}


/**
 * Generate field clause and insertion array value for a section of defined fields.
 * Being done by reference and without injecting submitted fields list for memory concern reasons
 *
 * @param array $profileFields
 * @param array $fieldList
 * @param array $replacements
 */
function _Mailroommatters_saveQuerySection($profileFields, &$fieldList, &$replacements) {
	foreach ($profileFields as $currentField) {
		_Mailroommatters_saveQueryField($currentField, $fieldList, $replacements);
	}
}

/**
 * Generate field clause and insertion array value for a defined field
 * Being done by reference and without injecting submitted fields list for memory concern reasons
 *
 * @param array $profileFields
 * @param array $fieldList
 * @param array $replacements
 */
function _Mailroommatters_saveQueryField($fieldDefinition, &$fieldList, &$replacements) {
	if ($fieldDefinition['type'] == 'section') {
		_Mailroommatters_saveQuerySection($fieldDefinition['fields'], $fieldList, $replacements);
	}

	if (array_key_exists($fieldDefinition['database_field'], $_POST)) {
		$saveValue = $_POST[$fieldDefinition['database_field']];

		switch (strtolower($fieldDefinition['type'])) {
			case 'check':
			case 'number':
				if ($saveValue === '' || !is_numeric($saveValue)) {
					$queryType = 'raw';
					$saveValue = 'NULL';
				} else {
					$queryType = 'int';
				}
				break;

			case 'select':
			case 'text':
			case 'textarea':
				$queryType = 'text';
				break;

			default:
				$queryType = $fieldDefinition['type'];
		}

		$fieldList[] = sprintf('%s = {%s:%s}', $fieldDefinition['database_field'], $queryType, $fieldDefinition['database_field']);
		$replacements[$fieldDefinition['database_field']] = $saveValue;
	}
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
