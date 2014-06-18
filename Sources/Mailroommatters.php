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

	if (isset($_REQUEST['save'])) {
		$context['error_message'] = '';

		// Check for various known error states
		if (empty($_POST['newspaper_name'])) {
			$context['error_message'] .= 'You must provide a Newspaper Name<br />';
		}

		// If no errors found, attempt the save
		if (empty($context['error_message'])) {
			$fieldList = array('last_modified = {int:last_modified}');
			$replacements = array('id_member' => $memberID, 'last_modified' => time());
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
			'label' => 'Address Line 2',
			'required' => true
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
				'--' => '- Select Province/State -',
				'' => '',
				'AL' => 'Alabama',
				'AK' => 'Alaska',
				'AZ' => 'Arizona',
				'AR' => 'Arkansas',
				'CA' => 'California',
				'CO' => 'Colorado',
				'CT' => 'Connecticut',
				'DE' => 'Delaware',
				'DC' => 'District Of Columbia',
				'FL' => 'Florida',
				'GA' => 'Georgia',
				'HI' => 'Hawaii',
				'ID' => 'Idaho',
				'IL' => 'Illinois',
				'IN' => 'Indiana',
				'IA' => 'Iowa',
				'KS' => 'Kansas',
				'KY' => 'Kentucky',
				'LA' => 'Louisiana',
				'ME' => 'Maine',
				'MD' => 'Maryland',
				'MA' => 'Massachusetts',
				'MI' => 'Michigan',
				'MN' => 'Minnesota',
				'MS' => 'Mississippi',
				'MO' => 'Missouri',
				'MT' => 'Montana',
				'NE' => 'Nebraska',
				'NV' => 'Nevada',
				'NH' => 'New Hampshire',
				'NJ' => 'New Jersey',
				'NM' => 'New Mexico',
				'NY' => 'New York',
				'NC' => 'North Carolina',
				'ND' => 'North Dakota',
				'OH' => 'Ohio',
				'OK' => 'Oklahoma',
				'OR' => 'Oregon',
				'PA' => 'Pennsylvania',
				'RI' => 'Rhode Island',
				'SC' => 'South Carolina',
				'SD' => 'South Dakota',
				'TN' => 'Tennessee',
				'TX' => 'Texas',
				'UT' => 'Utah',
				'VT' => 'Vermont',
				'VA' => 'Virginia',
				'WA' => 'Washington',
				'WV' => 'West Virginia',
				'WI' => 'Wisconsin',
				'WY' => 'Wyoming',
				'=' => '====================',
				'AB' => 'Alberta',
				'BC' => 'British Columbia',
				'MB' => 'Manitoba',
				'NB' => 'New Brunswick',
				'NL' => 'Newfoundland and Labrador',
				'NS' => 'Nova Scotia',
				'NT' => 'Northwest Territories',
				'NU' => 'Nunavut',
				'ON' => 'Ontario',
				'PE' => 'Prince Edward Island',
				'QC' => 'Quebec',
				'SK' => 'Saskatchewan',
				'YT' => 'Yukon'
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
		'circulation_day' => array(
			'database_field' => 'circulation_day',
			'type' => 'text',
			'label' => 'Circulation Day'
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
			'label' => 'Inserting equipment, stackers, etc. (please provide as much detail as possible)'
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
			'label' => 'Other:',
			'label_subtle' => true,
			'no_dt' => true
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
			'label' => 'Other:',
			'label_subtle' => true,
			'no_dt' => true
		),
		'special_requirements' => array(
			'database_field' => 'special_requirements',
			'type' => 'textarea',
			'label' => 'Special Requirements'
		),
		'hours_monday' => array(
			'database_field' => 'hours_monday',
			'type' => 'text',
			'label' => 'Monday *'
		),
		'hours_tuesday' => array(
			'database_field' => 'hours_tuesday',
			'type' => 'text',
			'label' => 'Tuesday *'
		),
		'hours_wednesday' => array(
			'database_field' => 'hours_wednesday',
			'type' => 'text',
			'label' => 'Wednesday *'
		),
		'hours_thursday' => array(
			'database_field' => 'hours_thursday',
			'type' => 'text',
			'label' => 'Thursday *'
		),
		'hours_friday' => array(
			'database_field' => 'hours_friday',
			'type' => 'text',
			'label' => 'Friday *'
		),
		'hours_saturday' => array(
			'database_field' => 'hours_saturday',
			'type' => 'text',
			'label' => 'Saturday *'
		),
		'hours_sunday' => array(
			'database_field' => 'hours_sunday',
			'type' => 'text',
			'label' => 'Sunday *'
		)
	);
}
