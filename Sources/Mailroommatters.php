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

		case 'search':
			MailroommattersSearch();
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
	$profileFields = null;

	if (isset($_REQUEST['save'])) {
		$context['error_message'] = '';

		// If no errors found, attempt the save
		if (empty($context['error_message'])) {
			$fieldList = array('last_modified = {int:last_modified}');
			$replacements = array('id_member' => $memberID, 'last_modified' => time());

			// Required before calling save; sets up values to save, checks validations
			$profileFields = _Mailroommatters_prepareFieldsForSave(_Mailroommatters_profileFields());

			if (!$profileFields['errors']) {
				$inserting = (empty($profile));

				if ($inserting) {
					$fieldList[] = 'id_member = {int:id_member}';
				}

				_Mailroommatters_saveQuerySection($profileFields['fields'], $fieldList, $replacements);

				$updateQuery = sprintf(
					'%s {db_prefix}mm_profiles SET %s%s',
					($inserting ? 'INSERT INTO' : 'UPDATE'),
					implode(', ', $fieldList),
					($inserting ? '' : ' WHERE id_member = {int:id_member}')
					);

				if ($smcFunc['db_query']('', $updateQuery, $replacements)) {
					redirectexit('action=mailroom_matters;area=me');
				}
			} else {
				$context['error_message'] = 'Errors were found in the information you provided. Please update your responses and try again';
			}
		}

		// Save failed, or field errors found?
		$profile = array_merge($profile, $_POST);
		if (empty($context['error_message'])) {
			$context['error_message'] = 'Save failed, please try again or contact the administrator';
		}
	}

	if (empty($profileFields['fields'])) {
		$profileFields['fields'] = _Mailroommatters_profileFields();
	}
	$context['mailroommatters']['fields'] = $profileFields['fields'];

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
 * Execute a basic search query, display a list of results
 */
function MailroommattersSearch() {
	global $smcFunc, $context;

	$rawSearch = trim(@$_GET['q']);
	$extraConditions = '';
	$replacements = array();
	$profiles = array();
	$boolKeyPattern = '~[\*\+\-\(\)\|\&]~';

	$searchableFields = array('newspaper_name', 'city', 'state', 'primary_name', 'primary_phone', 'primary_email', 'secondary_name', 'secondary_phone', 'secondary_email');
	$matchStatement = sprintf('MATCH (`%s`) AGAINST ({string:match_terms} IN BOOLEAN MODE)', implode('`, `', $searchableFields));

	// SMF does odd things with quotes, so anyone searching using them for an exact match (ie "Journal") will find nothing,
	// as the search includes the quotes as part of the term. Easiest for now is to ignore them, search works OK without.
	$cleanSearch = str_replace('"', '', htmlspecialchars_decode($rawSearch));

	// If they didn't provide their own boolean keys, then add a wildcard to the end of each word for them.
	// If they did, assume they know what they're doing and leave it alone.
	$matchTerms = $cleanSearch;
	if (!preg_match($boolKeyPattern, $matchTerms)) {
		$matchTerms = str_replace(' ', '* ', $matchTerms) .'*';
	}
	$replacements['match_terms'] = $matchTerms;

	// If a search term is below 4 characters long, it gets ignored.
	// Search for exact matches in that case.
	$simpleSearch = preg_replace($boolKeyPattern, '', $cleanSearch);
	if (!empty($simpleSearch) && strlen($simpleSearch) < 4) {
		$smallterms = explode(' ', $simpleSearch);
		foreach ($smallterms as $index => $term) {
			$termKey = 'smallterm_'. $index;
			$replacements[$termKey] = $term;
			foreach ($searchableFields as $field) {
				$extraConditions .= sprintf(' OR `%s` = {string:%s}', $field, $termKey);
			}
		}
	}

	$searchQuery = sprintf(
		'SELECT *, %s AS score FROM {db_prefix}mm_profiles WHERE %s%s',
		$matchStatement,
		$matchStatement,
		$extraConditions
		);
	$request = $smcFunc['db_query']('', $searchQuery, $replacements);
	while ($row = $smcFunc['db_fetch_assoc']($request)) {
		$profiles[] = $row;
	}
	$smcFunc['db_free_result']($request);

	$_GET['action'] = 'mailroom_matters';
	$context['page_title'] .= ' Profiles: Search Results';
	$context['mailroommatters']['profiles'] = $profiles;
	$context['mailroommatters']['top_header'] = $context['page_title'];
	$context['q'] = $rawSearch;
	$context['sub_template'] = 'mailroommatters_search';
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
 * Set values to be saved into the array, and check if any validation rules are defined against the field.
 * If so, validate and generate any applicable errors.
 * Assumes sections no longer exist in the field definition.
 *
 * @param array $profileFields
 * @return array Index 'errors' is bool, index 'fields' is the updated contents of $profileFields
 */
function _Mailroommatters_prepareFieldsForSave($profileFields) {
	$errors = false;

	foreach ($profileFields as $index => $currentField) {
		$errorMessage = null;
		$saveValue = null;

		if (array_key_exists($currentField['database_field'], $_POST)) {
			$saveValue = $_POST[$currentField['database_field']];
		}

		// Run any validations here
		if ($currentField['type'] == 'select') {
			// Translate placeholders to empty
			if (in_array($saveValue, array('', '--', '='))) {
				$saveValue = '';
			}
			if (!array_key_exists($saveValue, $currentField['options'])) {
				$saveValue = '';
			}
		}

		if ($currentField['required'] && empty($saveValue)) {
			$errors = true;
			$errorMessage = 'This is a required field';
		}

		// Update $profileFields at the end of it all
		$profileFields[$index]['save_value'] = $saveValue;
		$profileFields[$index]['error'] = $errorMessage;
	}

	return array('errors' => $errors, 'fields' => $profileFields);
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
 * Being done by reference and without injecting submitted fields list for memory concern reasons.
 * Expects that _Mailroommatters_prepareFieldsForSave() first prepared $fieldDefinition
 *
 * @param array $fieldDefinition  An entry in the $profileFields array
 * @param array $fieldList
 * @param array $replacements
 */
function _Mailroommatters_saveQueryField($fieldDefinition, &$fieldList, &$replacements) {
	if ($fieldDefinition['type'] == 'section') {
		_Mailroommatters_saveQuerySection($fieldDefinition['fields'], $fieldList, $replacements);
	}

	$saveValue = $fieldDefinition['save_value'];

	switch (strtolower($fieldDefinition['type'])) {
		case 'yesno':
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

/**
 * Get a consistent definition of the MM profile fields
 */
function _Mailroommatters_profileFields() {
	return array(
		'newspaper_name' => array(
			'database_field' => 'newspaper_name',
			'type' => 'text',
			'label' => 'Newspaper/Company Name',
			'required' => true
		),
		'address' => array(
			'database_field' => 'address',
			'type' => 'text',
			'label' => 'Address',
			'required' => true
		),
		'address2' => array(
			'database_field' => 'address2',
			'type' => 'text',
			'label' => 'Address Line 2'
		),
		'city' => array(
			'database_field' => 'city',
			'type' => 'text',
			'label' => 'City',
			'required' => true
		),
		'state' => array(
			'database_field' => 'state',
			'type' => 'select',
			'label' => 'State',
			'required' => true,
			'options' => array(
				'' => '- Select Province/State -',
				'--' => '',
				'Alabama' => 'Alabama',
				'Alaska' => 'Alaska',
				'Arizona' => 'Arizona',
				'Arkansas' => 'Arkansas',
				'California' => 'California',
				'Colorado' => 'Colorado',
				'Connecticut' => 'Connecticut',
				'Delaware' => 'Delaware',
				'District Of Columbia' => 'District Of Columbia',
				'Florida' => 'Florida',
				'Georgia' => 'Georgia',
				'Hawaii' => 'Hawaii',
				'Idaho' => 'Idaho',
				'Illinois' => 'Illinois',
				'Indiana' => 'Indiana',
				'Iowa' => 'Iowa',
				'Kansas' => 'Kansas',
				'Kentucky' => 'Kentucky',
				'Louisiana' => 'Louisiana',
				'Maine' => 'Maine',
				'Maryland' => 'Maryland',
				'Massachusetts' => 'Massachusetts',
				'Michigan' => 'Michigan',
				'Minnesota' => 'Minnesota',
				'Mississippi' => 'Mississippi',
				'Missouri' => 'Missouri',
				'Montana' => 'Montana',
				'Nebraska' => 'Nebraska',
				'Nevada' => 'Nevada',
				'New Hampshire' => 'New Hampshire',
				'New Jersey' => 'New Jersey',
				'New Mexico' => 'New Mexico',
				'New York' => 'New York',
				'North Carolina' => 'North Carolina',
				'North Dakota' => 'North Dakota',
				'Ohio' => 'Ohio',
				'Oklahoma' => 'Oklahoma',
				'Oregon' => 'Oregon',
				'Pennsylvania' => 'Pennsylvania',
				'Rhode Island' => 'Rhode Island',
				'South Carolina' => 'South Carolina',
				'South Dakota' => 'South Dakota',
				'Tennessee' => 'Tennessee',
				'Texas' => 'Texas',
				'Utah' => 'Utah',
				'Vermont' => 'Vermont',
				'Virginia' => 'Virginia',
				'Washington' => 'Washington',
				'West Virginia' => 'West Virginia',
				'Wisconsin' => 'Wisconsin',
				'Wyoming' => 'Wyoming',
				'=' => '====================',
				'Alberta' => 'Alberta',
				'British Columbia' => 'British Columbia',
				'Manitoba' => 'Manitoba',
				'New Brunswick' => 'New Brunswick',
				'Newfoundland and Labrador' => 'Newfoundland and Labrador',
				'Nova Scotia' => 'Nova Scotia',
				'Northwest Territories' => 'Northwest Territories',
				'Nunavut' => 'Nunavut',
				'Ontario' => 'Ontario',
				'Prince Edward Island' => 'Prince Edward Island',
				'Quebec' => 'Quebec',
				'Saskatchewan' => 'Saskatchewan',
				'Yukon' => 'Yukon'
			)
		),
		'country' => array(
			'database_field' => 'country',
			'type' => 'text',
			'label' => 'Country',
			'required' => true
		),
		'zip' => array(
			'database_field' => 'zip',
			'type' => 'text',
			'label' => 'ZIP/Postal Code',
			'required' => true
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
		'primary_name' => array(
			'database_field' => 'primary_name',
			'type' => 'text',
			'label' => 'Name',
			'required' => true
		),
		'primary_position' => array(
			'database_field' => 'primary_position',
			'type' => 'text',
			'label' => 'Position',
			'required' => true
		),
		'primary_phone' => array(
			'database_field' => 'primary_phone',
			'type' => 'text',
			'label' => 'Phone',
			'required' => true
		),
		'primary_fax' => array(
			'database_field' => 'primary_fax',
			'type' => 'text',
			'label' => 'Fax',
			'required' => true
		),
		'primary_email' => array(
			'database_field' => 'primary_email',
			'type' => 'text',
			'label' => 'Email',
			'required' => true
		),
		'secondary_name' => array(
			'database_field' => 'secondary_name',
			'type' => 'text',
			'label' => 'Name'
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
		'extension_requests_name' => array(
			'database_field' => 'extension_requests_name',
			'type' => 'text',
			'label' => 'Name',
			'required' => true
		),
		'extension_requests_position' => array(
			'database_field' => 'extension_requests_position',
			'type' => 'text',
			'label' => 'Position',
			'required' => true
		),
		'extension_requests_phone' => array(
			'database_field' => 'extension_requests_phone',
			'type' => 'text',
			'label' => 'Phone',
			'required' => true
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
			'label' => 'Extension Comments'
		),
		'circulation_volume' => array(
			'database_field' => 'circulation_volume',
			'type' => 'select',
			'label' => 'Approximate Sunday Circulation',
			'required' => true,
			'options' => array(
				'0-50000' => 'Below 50,000',
				'50000-80000' => '50,000 - 80,000',
				'80000-120000' => '80,000 - 120,000',
				'120000-200000' => '120,000 - 200,000',
				'200000+' => 'Above 200,000',
			)
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
			'type' => 'yesno',
			'label' => 'Do you do commercial printing?'
		),
		'digital_pictures' => array(
			'database_field' => 'digital_pictures',
			'type' => 'yesno',
			'label' => 'Digital picture capabilities for failed loads?'
		),
		'load_packaging' => array(
			'database_field' => 'load_packaging',
			'type' => 'textarea',
			'label' => 'How do you package your loads?',
		),
		'advanced_receiving_sundays' => array(
			'database_field' => 'advanced_receiving_sundays',
			'type' => 'number',
			'label' => 'Sunday',
			'after_input' => 'days',
			'required' => true
		),
		'advanced_receiving_daily' => array(
			'database_field' => 'advanced_receiving_daily',
			'type' => 'number',
			'label' => 'Daily',
			'after_input' => 'days',
			'required' => true
		),
		'inserting_equipment' => array(
			'database_field' => 'inserting_equipment',
			'type' => 'textarea',
			'edit_label' => 'Inserting equipment, stackers, etc. (please provide as much detail as possible)',
			'label' => 'Inserting equipment, stackers, etc.'
		),
		'unloading_equipment' => array(
			'database_field' => 'unloading_equipment',
			'type' => 'textarea',
			'label' => 'Special Unloading Equipment'
		),
		'receiving_challenges_difficult_access' => array(
			'database_field' => 'receiving_challenges_difficult_access',
			'type' => 'check',
			'label' => 'Difficult access for truck/trailer due to small laneway or road',
			'value' => '1'
		),
		'receiving_challenges_no_turnaround' => array(
			'database_field' => 'receiving_challenges_no_turnaround',
			'type' => 'check',
			'label' => 'No truck/trailer turn around area',
			'value' => '1'
		),
		'receiving_challenges_unpaved' => array(
			'database_field' => 'receiving_challenges_unpaved',
			'type' => 'check',
			'label' => 'Unpaved road or poor road repair',
			'value' => '1'
		),
		'receiving_challenges_comments' => array(
			'database_field' => 'receiving_challenges_comments',
			'type' => 'textarea',
			'label' => 'Other:'
		),
		'driver_privileges_office_only' => array(
			'database_field' => 'driver_privileges_office_only',
			'type' => 'check',
			'label' => 'Shipping office only',
			'value' => '1'
		),
		'driver_privileges_truck_only' => array(
			'database_field' => 'driver_privileges_truck_only',
			'type' => 'check',
			'label' => 'Stay in truck',
			'value' => '1'
		),
		'driver_privileges_unloading_participation' => array(
			'database_field' => 'driver_privileges_unloading_participation',
			'type' => 'check',
			'label' => 'Unloading participation supported',
			'value' => '1'
		),
		'driver_privileges_comments' => array(
			'database_field' => 'driver_privileges_comments',
			'type' => 'textarea',
			'label' => 'Other:'
		),
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
		'pallet_storage_inside_mailroom' => array(
			'database_field' => 'pallet_storage_inside_mailroom',
			'type' => 'check',
			'label' => 'Inside mailroom',
			'value' => '1'
		),
		'pallet_storage_inside_building' => array(
			'database_field' => 'pallet_storage_inside_building',
			'type' => 'check',
			'label' => 'Inside building near mailroom',
			'value' => '1'
		),
		'pallet_storage_inside_dock' => array(
			'database_field' => 'pallet_storage_inside_dock',
			'type' => 'check',
			'label' => 'Inside dock area',
			'value' => '1'
		),
		'pallet_storage_inside_trailer' => array(
			'database_field' => 'pallet_storage_inside_trailer',
			'type' => 'check',
			'label' => 'Inside a storage locker',
			'value' => '1'
		),
		'pallet_storage_inside_cage' => array(
			'database_field' => 'pallet_storage_inside_cage',
			'type' => 'check',
			'label' => 'Inside in a locked cage',
			'value' => '1'
		),
		'pallet_storage_outside_secured' => array(
			'database_field' => 'pallet_storage_outside_secured',
			'type' => 'check',
			'label' => 'Outside in a secured yard',
			'value' => '1'
		),
		'pallet_storage_outside_unsecured' => array(
			'database_field' => 'pallet_storage_outside_unsecured',
			'type' => 'check',
			'label' => 'Outside in a unsecured yard',
			'value' => '1'
		),
		'follow_recommended_stack' => array(
			'database_field' => 'follow_recommended_stack',
			'type' => 'yesno',
			'label' => 'Do you follow the recommended 30 pallets per stack?'
		),
		'pickup_notification' => array(
			'database_field' => 'pickup_notification',
			'type' => 'yesno',
			'label' => 'Perfect Pallet Pickup notification required?'
		),
		'pickup_notification_email' => array(
			'database_field' => 'pickup_notification_email',
			'type' => 'check',
			'label' => 'Email',
			'value' => '1'
		),
		'pickup_notification_phone' => array(
			'database_field' => 'pickup_notification_phone',
			'type' => 'check',
			'label' => 'Telephone',
			'value' => '1'
		),
		'pickup_notification_fax' => array(
			'database_field' => 'pickup_notification_fax',
			'type' => 'check',
			'label' => 'Fax',
			'value' => '1'
		),
		'recycling_bailers' => array(
			'database_field' => 'recycling_bailers',
			'type' => 'check',
			'label' => 'Paper bailers',
			'value' => '1'
		),
		'recycling_compactors' => array(
			'database_field' => 'recycling_compactors',
			'type' => 'check',
			'label' => 'Compactors',
			'value' => '1'
		),
		'recycling_dumpsters' => array(
			'database_field' => 'recycling_dumpsters',
			'type' => 'check',
			'label' => 'Dumpsters',
			'value' => '1'
		),
		'recycler' => array(
			'database_field' => 'recycler',
			'type' => 'yesno',
			'label' => 'Do you ship out to a local recycler(s)?'
		),
		'recycler_name' => array(
			'database_field' => 'recycler_name',
			'type' => 'text',
			'label' => "Recycler's Name",
		),
		'recycler_phone' => array(
			'database_field' => 'recycler_phone',
			'type' => 'text',
			'label' => "Recycler's Phone Number"
		),
		'inhouse_pallet' => array(
			'database_field' => 'inhouse_pallet',
			'type' => 'yesno',
			'label' => 'Do you own in house plastic pallets?'
		),
		'inhouse_pallet_type' => array(
			'database_field' => 'inhouse_pallet_type',
			'type' => 'text',
			'label' => 'What Kind?',
		),
		'inhouse_pallet_number' => array(
			'database_field' => 'inhouse_pallet_number',
			'type' => 'text',
			'label' => 'How Many?'
		),
		'pallet_return_details' => array(
			'database_field' => 'pallet_return_details',
			'type' => 'textarea',
			'label' => 'Additional comments regarding your purchased plastic pallets? (Please share)'
		)
	);
}
