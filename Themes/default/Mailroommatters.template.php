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
	global $context, $settings, $options, $scripturl, $modSettings, $txt;

	?>
	<div id="mailroommatters">
		<div class="cat_bar">
			<h3 class="catbg"><?php echo $context['mailroommatters']['top_header']; ?></h3>
		</div>
		<?php if (!empty($context['error_message'])): ?><p class="widowdb description error"><?php echo $context['error_message']; ?></p><?php endif; ?>
		<?php if (!empty($pageDescription)): ?><p class="windowdb description"><?php echo $pageDescription; ?></p><?php endif; ?>
	</div>
	<div class="widowbg2">
		<span class="topslice"></span>
		<div class="content"><?php echo $mainContent; ?></div>
		<span class="botslice"></span>
	</div>
	<?php
}

/**
 * Index action.
 * List brief summary of, and link to, existing profiles.
 */
function template_mailroommatters_index() {
	global $context, $settings, $options, $scripturl, $modSettings, $txt;

	$pageDescription = '
		See the resources and services available from each of the member mailrooms.<br />
		You can manage your own profile <a href="'. $scripturl .'?action=mailroom_matters;area=edit">here</a>.
		';

	$content = 'There are currently no profiles to view. Why not <a href="'. $scripturl .'?action=mailroom_matters;area=edit">add your own?</a>';
	if (!empty($context['mailroommatters']['profiles'])) {
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
		$linkLetters = array();
		foreach ($context['mailroommatters']['profiles'] as $currentProfile) {
			$firstLetter = strtoupper(substr($currentProfile['newspaper_name'], 0, 1));
			$letterHeader = '';
			if ($firstLetter != $sortLetter) {
				$letterHeader = ' id="letter'. $firstLetter .'"';
				$sortLetter = $firstLetter;
				$linkLetters[] = '<a href="'. $scripturl .'?action=mailroom_matters#letter'. $firstLetter .'">'. $firstLetter .'</a>';
			}

			$content .= '
				<tr'. $letterHeader .'>
					<td class="windowbg"><a class="subject" href="'. $scripturl .'?action=mailroom_matters;area=profile;mailroom='. $currentProfile['id_mmprofile'] .'">'. htmlspecialchars($currentProfile['newspaper_name']) .'</a></td>
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

	_mailroommatters_render($content, $pageDescription);
}

/**
 * Edit action.
 * Show the million and one fields they can edit.
 */
function template_mailroommatters_edit() {
	global $context, $settings, $options, $scripturl, $modSettings, $txt;

	$content = '';
	$pageDescription = (empty($context['mailroommatters']['profile']) ? 'Add a' : 'Update your') .' profile for your company. Include as much detail as you can to complete your profile.';

	foreach ($context['mailroommatters']['fields'] as $fieldKey => $currentField) {
		if ($currentField['type'] == 'section') {
			$content .= _mailroommatters_renderSection($currentField, '_mailroommatters_editField');
		} else {
			$content .= _mailroommatters_editField($currentField);
		}
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
 * Call to render a section of fields
 * @param array $section
 * @param callable $fieldRenderCallback
 * @return string
 */
function _mailroommatters_renderSection($section, $fieldRenderCallback = '_mailroommatters_renderField') {
	global $context;

	$content = '
		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">'. $section['label'] .'</span></h4>
		</div>
		<dl>
		';
	if (is_callable($fieldRenderCallback)) {
		foreach ($section['fields'] as $fieldKey => $currentField) {
			$content .= call_user_func($fieldRenderCallback, $currentField);
		}
	}
	$content .= '</dl>';

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
				$fieldInput .= $key .'="'. $value .'" ';
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
}
