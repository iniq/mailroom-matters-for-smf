<?php
/**
 * Mailroommatters.template.php
 * Mod: Mailroom Matters for SMF
 *
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
					<td class="windowbg"><a class="subject" href="'. $scripturl .'?action=mailroom_matters;area=profile;mailroom='. $currentProfile['id_mmprofile'] .';">'. htmlspecialchars($currentProfile['newspaper_name']) .'</a></td>
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
