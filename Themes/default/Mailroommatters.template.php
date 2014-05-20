<?php
/**
 * Mailroommatters.template.php
 *
 * @package Mailroom Matters for SMF
 * @author iniq https://github.com/iniq
 * @version 0.1
 */

/**
 * General template shell to render (header, etc.) content for this module consistently.
 * Call with content from the individual methods.
 */
function _mailroommatters_render($mainContent, $pageDescription = '') {
	_mailroommatters_header($pageDescription);
	_mailroommatters_commonContentWrapper($mainContent);
}

/**
 * Generate the top-of-page header/title/error stuff
 */
function _mailroommatters_header($pageDescription = '', $includeSearch = true) {
	global $context;

	?>
	<div id="mailroommatters">
		<div class="cat_bar">
			<h3 class="catbg"><?php echo $context['mailroommatters']['top_header']; ?></h3>
		</div>
		<?php if (!empty($context['error_message'])): ?><p class="widowdb description error"><?php echo $context['error_message']; ?></p><?php endif; ?>
		<?php if (!empty($pageDescription)): ?><p class="windowdb description"><?php echo $pageDescription; ?></p><?php endif; ?>
		<?php if ($includeSearch) { _mailroommatters_searchbox(); }; ?>
	</div>
	<?php
}

/**
 * Wrap the main content in containers expected for most content in the default theme
 */
function _mailroommatters_commonContentWrapper($mainContent) {
	?>
	<div class="widowbg2">
		<span class="topslice"></span>
		<div class="content"><?php echo $mainContent; ?></div>
		<span class="botslice"></span>
	</div>
	<?php
}

/**
 * Function to include the MM search box wherever you'd like
 */
function _mailroommatters_searchbox() {
	global $context;

	$clearOnFocus = false;
	$searchTerm = $context['q'];
	$placeholderTerm = 'Search...';

	if (empty($searchTerm) || $searchTerm == $placeholderTerm) {
		$searchTerm = $placeholderTerm;
		$clearOnFocus = true;
	}
	?>
	<form class="mailroommatters_search" method="get" action="<?php echo $scripturl; ?>" accept-charset="UTF-8">
		<input type="hidden" name="action" value="mailroom_matters" />
		<input type="hidden" name="area" value="search" />
		<input class="inputbox" type="text" name="q" value="<?php echo $searchTerm; ?>" <?php if ($clearOnFocus) { echo 'onblur="if(this.value==\'\') this.value=\''. $searchTerm .'\';" onfocus="this.value = \'\';"'; } ?> />
		<input type="submit" value="Search Profiles" />
	</form>
	<?php
}

/**
 * Display a commonly styled and functional table for a list of Profiles
 * (index, search results, etc.)
 * Side effect: will modify page header to include link letters of its own accord (hacky, yes.)
 *
 * @param array $profiles
 * @return string
 */
function _mailroommatters_profileTable($profiles) {
	global $context, $scripturl;

	$content = '';
	$linkLetters = array();

	if (!empty($profiles)) {
		$letterLinkBase = $scripturl .'?action=mailroom_matters';
		if (!empty($context['q'])) {
			$letterLinkBase .= ';area=search;q='. urlencode($context['q']);
		}

		$content = '
		<div id="mailroommatters_list" class="tborder topic_table">
			<table class="table_grid" cellspacing="0" width="100%">
			<thead>
				<tr class="catbg">
					<th scope="col" class="first_th">Newspaper</th>
					<th scope="col">City</th>
					<th scope="col" class="last_th">State</th>
				</tr>
			</thead>
			<tbody>
		';

		$sortLetter = '';
		foreach ($profiles as $currentProfile) {
			$firstLetter = strtoupper(substr($currentProfile['newspaper_name'], 0, 1));
			$letterHeader = '';
			if ($firstLetter != $sortLetter) {
				$letterHeader = ' id="letter'. $firstLetter .'"';
				$sortLetter = $firstLetter;
				$linkLetters[] = '<a href="'. $letterLinkBase .'#letter'. $firstLetter .'">'. $firstLetter .'</a>';
			}

			$content .= '
				<tr'. $letterHeader .'>
					<td class="windowbg"><a class="subject" href="'. $scripturl .'?action=mailroom_matters;area=profile;mailroom='. $currentProfile['id_member'] .'">'. htmlspecialchars($currentProfile['newspaper_name']) .'</a></td>
					<td class="windowbg">'. htmlspecialchars($currentProfile['city']) .'</td>
					<td class="windowbg">'. htmlspecialchars($currentProfile['state']) .'</td>
				</tr>
				';
		}

		$content .= '
			</tbody>
			</table>
		</div>
		';

		$context['mailroommatters']['top_header'] .= '<span class="floatright">'. implode(' ', $linkLetters) .'</span>';
	}

	return $content;
}

/**
 * Index action.
 * List brief summary of, and link to, existing profiles.
 */
function template_mailroommatters_index() {
	global $context, $scripturl;

	$pageDescription = '
		See the resources and services available from each of the member mailrooms.<br />
		You can manage your own profile <a href="'. $scripturl .'?action=mailroom_matters;area=edit">here</a>.
		';

	$content = _mailroommatters_profileTable($context['mailroommatters']['profiles']);
	if (empty($content)) {
		$content = 'There are currently no profiles to view. Why not <a href="'. $scripturl .'?action=mailroom_matters;area=edit">add your own?</a>';
	}

	_mailroommatters_render($content, $pageDescription);
}

/**
 * Edit action.
 * Show the million and one fields they can edit.
 */
function template_mailroommatters_edit() {
	global $context, $scripturl;

	$content = '';
	$pageDescription = (empty($context['mailroommatters']['profile']) ? 'Add a' : 'Update your') .' profile for your company. Include as much detail as you can to complete your profile.';

	foreach ($context['mailroommatters']['fields'] as $fieldKey => $currentField) {
		$content .= _mailroommatters_editField($currentField);
	}

	$content = '
		<form id="creator" method="post" action="'. $scripturl .'?action=mailroom_matters;area=edit;save">
			'. $content .'
			<hr class="hrcolor clear" width="100%" size="1" />
			<div class="righttext"><input class="button_submit" type="submit" value="Save Profile" /></div>
		</form>
		';

	_mailroommatters_render($content, $pageDescription);
}

/**
 * View action.
 * Show the million and one field values, with links to edit/delete if appropriate.
 */
function template_mailroommatters_view() {
	global $context, $scripturl;

	$content = '';
	$pageDescription = '';

	foreach ($context['mailroommatters']['fields'] as $fieldKey => $currentField) {
		$content .= _mailroommatters_renderField($currentField);
	}

	_mailroommatters_header();

	$actionButtons = array();
	if ($context['mailroommatters']['self']) {
		$actionButtons[] = array('label' => 'Edit Profile', 'linkSuffix' => ';area=edit');
	}
	if ($context['mailroommatters']['self'] || $context['user']['is_admin']) {
		$actionButtons[] = array('label' => 'Delete Profile', 'linkSuffix' => ';area=delete;mailroom='. $context['mailroommatters']['profile']['id_member']);
	}

	if (!empty($actionButtons)) {
		?>
		<div class="pagesection">
			<div class="buttonlist floatright">
				<ul>
					<?php
		foreach ($actionButtons as $actionButton) {
			echo '<li><a href="'. $scripturl .'?action=mailroom_matters'. $actionButton['linkSuffix'] .'"><span>'. $actionButton['label'] .'</span></a></li>';
		}
					?>
				</ul>
			</div>
		</div>
		<?php
	}

	?>
	<div id="main_admsection" class="flow_auto">
		<div id="detailedinfo">
			<?php _mailroommatters_commonContentWrapper($content); ?>
		</div>
	</div>
	<?php
}

/**
 * Delete action.
 * Show a confirmation - do they really want to delete everything in this profile?
 */
function template_mailroommatters_delete() {
	global $context, $scripturl;

	ob_start();
	?>
	<h2>Delete Profile for &quot;<?php echo htmlspecialchars($context['mailroommatters']['profile']['newspaper_name']); ?>&quot;?</h2>
	<p>This cannot be undone, and the entire profile will be deleted. Only proceed if you are certain that you want to do this.</p>
	<p><a href="<?php echo $scripturl; ?>?action=mailroom_matters;area=delete;mailroom=<?php echo $context['mailroommatters']['profile']['id_member']; ?>;confirm">I understand this is permanent. Delete this profile.</a></p>
	<?php
	$content = ob_get_contents();
	ob_end_clean();

	_mailroommatters_render($content);
}


/**
 * Call to render a section of fields
 * @param array $section
 * @param callable $fieldRenderCallback
 * @return string
 */
function _mailroommatters_renderSection($section, $fieldRenderCallback = '_mailroommatters_renderField') {
	global $context;

	$fieldContent = '';
	if (is_callable($fieldRenderCallback)) {
		foreach ($section['fields'] as $fieldKey => $currentField) {
			$fieldContent .= call_user_func($fieldRenderCallback, $currentField);
		}
	}

	if (empty($fieldContent)) {
		return '';
	}

	$content = '
		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">'. $section['label'] .'</span></h4>
		</div>
		<dl>
		'. $fieldContent .'
		</dl>
		';

	return $content;
}

/**
 * Call to render an input to edit a profile field
 * @param array $field
 * @return string
 */
function _mailroommatters_editField($field) {
	global $context;

	$fieldInput = '';
	$properties = array();
	$currentValue = $context['mailroommatters']['profile'][$field['database_field']];

	switch ($field['type']) {
		case 'section':
			return _mailroommatters_renderSection($field, '_mailroommatters_editField');

		case 'textarea':
			$fieldInput = '<textarea class="editor" cols="50" rows="5" name="'. $field['database_field'] .'">'. $currentValue .'</textarea>';
			break;

		case 'check':
			if (!array_key_exists('value', $field)) {
				$field['value'] = '1';
			}
			$fieldInput = '<input type="checkbox" name="'. $field['database_field'] .'" value="'. $field['value'] .'" '. ($currentValue == $field['value'] ? ' checked' : '') .'/>';
			break;

		case 'select':
			if (!in_array($currentValue, $field['options'])) {
				$field['options'][] = $currentValue;
			}
			foreach ($field['options'] as $option) {
				$fieldInput .= '<option value="'. $option .'"'. ($option == $currentValue ? ' selected' : '') .'>'. $option .'</option>';
			}
			$fieldInput = '<select name="'. $field['database_field'] .'">'. $fieldInput .'</select>';
			break;

		case 'text':
			$properties['size'] = '50';

		default:
			$properties['type'] = $field['type'];
			$properties['name'] = $field['database_field'];
			$properties['value'] = $currentValue;
			foreach ($properties as $key => $value) {
				$fieldInput .= $key .'="'. htmlspecialchars($value, ENT_QUOTES) .'" ';
			}
			$fieldInput = '<input '. $fieldInput .'/>';
	}

	$content = '
		<dt><strong>'. $field['label'] .'</strong>'. (empty($field['subtext']) ? '' : '<br /><span class="smalltext">'. $field['subtext'] .'</span>') .'</dt>
		<dd>'. $fieldInput .'</dd>
		';
	return $content;
}

/**
 * Call to show a value for a profile field, if it isn't empty
 * @param array $field
 * @return string
 */
function _mailroommatters_renderField($field) {
	global $context;

	if ($field['type'] == 'section') {
		return _mailroommatters_renderSection($field, '_mailroommatters_renderField');
	}

	$currentValue = $context['mailroommatters']['profile'][$field['database_field']];

	if ($currentValue === '' || is_null($currentValue)) {
		return '';
	}

	switch ($field['type']) {
		case 'number':
			$currentValue = floatval($currentValue);
			break;

		case 'check':
			$currentValue = ($currentValue ? 'Yes' : 'No');
			break;

		case 'textarea':
			$currentValue = nl2br(htmlspecialchars($currentValue));
			break;

		default:
			$currentValue = htmlspecialchars($currentValue);
			if (strpos($field['database_field'], '_email') !== false) {
				$currentValue = '<a href="mailto:'. $currentValue .'">'. $currentValue .'</a>';
			}
	}

	$content = '
		<dt>'. $field['label'] .'</dt>
		<dd>'. $currentValue .'</dd>
		';
	return $content;
}
