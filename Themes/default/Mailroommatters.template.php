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
 * Search results action.
 * List brief summary of, and link to, matching profiles (if any).
 */
function template_mailroommatters_search() {
	global $context, $scripturl;

	$pageDescription = 'Search for a Mailroom Matters profile by: City, State, Newspaper Name or Contact.';

	$content = _mailroommatters_profileTable($context['mailroommatters']['profiles']);
	if (empty($content)) {
		$content = 'No profiles matched your search. You can refine your terms and try again, or <a href="'. $scripturl .'?action=mailroom_matters">view the full listing</a>.';
	} else {
		$pageDescription .= '<br />The following profiles matched your search:';
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

	ob_start();
	?>
	<form id="creator" method="post" action="<?php echo $scripturl; ?>?action=mailroom_matters;area=edit;save">

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Newspaper/Company Contact Information</span></h4>
		</div>
		<strong><small>* fields are required</small></strong>
		<dl>
			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['newspaper_name']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['address']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['address2']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['city']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['state']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['country']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['zip']);
			?>
		</dl>

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Primary Contact</span></h4>
		</div>
		<dl>
			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['primary_name']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['primary_position']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['primary_phone']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['primary_fax']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['primary_email']);
			?>
		</dl>

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Secondary Contact</span></h4>
		</div>
		<dl>
			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['secondary_name']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['secondary_position']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['secondary_phone']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['secondary_fax']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['secondary_email']);
			?>
		</dl>

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Other Contact Information</span></h4>
		</div>
		<dl>
			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['phone_emergency']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['phone_security']);
			?>
		</dl>

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Company Details and Equipment</span></h4>
		</div>
		<dl>
			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['circulation_volume']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['inserting_equipment']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['forklifts']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_jacks']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['staff_receiving']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['staff_inserting']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['commercial_printing']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['load_packaging']);
			?>
		</dl>

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Extension Requests Contact</span></h4>
		</div>
		<dl>
			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['extension_requests_name']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['extension_requests_position']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['extension_requests_phone']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['extension_requests_fax']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['extension_requests_email']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['extension_requests_comments']);
			?>
		</dl>

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Receiving Days and Hours for Insert Delivery (If you are closed on the specified day, then leave the field blank)</span></h4>
		</div>
		<dl>
			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['hours_monday']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['hours_tuesday']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['hours_wednesday']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['hours_thursday']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['hours_friday']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['hours_saturday']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['hours_sunday']);
			?>
		</dl>

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Receiving Details</span></h4>
		</div>
		<dl>
			<dt><strong>Advanced insert receiving times: (How many days in advance must inserts be recieved?)</strong></dt>
			<dd></dd>

			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['advanced_receiving_sundays']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['advanced_receiving_daily']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['unloading_equipment']);
			?>

			<dt><strong>Receiving Challenges or Comments</strong></dt>
			<dd>
				<?php
				echo _mailroommatters_editField($context['mailroommatters']['fields']['receiving_challenges_difficult_access']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['receiving_challenges_no_turnaround']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['receiving_challenges_unpaved']);
				?>
				<br />
				<?php echo _mailroommatters_editField($context['mailroommatters']['fields']['receiving_challenges_comments'], $wrap = false, $strong = false); ?>
			</dd>

			<dt><strong>Driver Privileges and Access</strong></dt>
			<dd>
				<?php
				echo _mailroommatters_editField($context['mailroommatters']['fields']['driver_privileges_office_only']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['driver_privileges_truck_only']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['driver_privileges_unloading_participation']);
				?>
				<br />
				<?php echo _mailroommatters_editField($context['mailroommatters']['fields']['driver_privileges_comments'], $wrap = false, $strong = false); ?>
			</dd>

			<?php echo _mailroommatters_editField($context['mailroommatters']['fields']['digital_pictures']); ?>
		</dl>

		<div class="title_barIC">
			<h4 class="titlebg"><span class="ie6_header floatleft">Returnable Printer Pallets/Recycling (Confidential)</span></h4>
		</div>
		<dl>
			<dt><strong>Perfect Pallet storage area</strong></dt>
			<dd>
				<?php
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_storage_inside_mailroom']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_storage_inside_building']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_storage_inside_dock']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_storage_inside_trailer']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_storage_inside_cage']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_storage_outside_secured']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_storage_outside_unsecured']);
				?>
			</dd>

			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['follow_recommended_stack']);
			echo _mailroommatters_editField($context['mailroommatters']['fields']['pickup_notification']);
			?>

			<dt><strong>If yes, what type of notification do you require?</strong></dt>
			<dd>
				<?php
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pickup_notification_email']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pickup_notification_phone']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['pickup_notification_fax']);
				?>
			</dd>

			<dt><strong>Recycling Equipment</strong></dt>
			<dd>
				<?php
				echo _mailroommatters_editField($context['mailroommatters']['fields']['recycling_bailers']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['recycling_compactors']);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['recycling_dumpsters']);
				?>
			</dd>

			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['recycler']);
			?>

			<dt><strong>If yes:</strong></dt>
			<dd>
				<?php
				echo _mailroommatters_editField($context['mailroommatters']['fields']['recycler_name'], $wrap = false, $strong = false, $inline = true);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['recycler_phone'], $wrap = false, $strong = false, $inline = true);
				?>
			</dd>

			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['inhouse_pallet']);
			?>

			<dt><strong>If yes:</strong></dt>
			<dd>
				<?php
				echo _mailroommatters_editField($context['mailroommatters']['fields']['inhouse_pallet_type'], $wrap = false, $strong = false, $inline = true);
				echo _mailroommatters_editField($context['mailroommatters']['fields']['inhouse_pallet_number'], $wrap = false, $strong = false, $inline = true);
				?>
			</dd>

			<?php
			echo _mailroommatters_editField($context['mailroommatters']['fields']['pallet_return_details']);
			?>

			<hr class="hrcolor clear" width="100%" size="1" />
			<div class="righttext"><input class="button_submit" type="submit" value="Save Profile" /></div>
		</dl>
	</form>
	<?php
	$content = ob_get_contents();
	ob_end_clean();


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

	// Build the main page content
	ob_start();
	?>

	<small><strong>Quick Links</strong></small>
	<ul id="mmQuickLinks" style="margin: 0px; padding: 0px; height: 40px; list-style: none; font-size: 11px; font-weight: bold;">
		<li style="float: left; margin: 5px 5px 0px 0px; border-right: 1px solid #cccccc; padding: 0px 5px 0px 0px;"><a href="#newspaperCompanyContactInformation">Newspaper/Company Contact Information</a></li>
		<li style="float: left; margin: 5px 5px 0px 0px; border-right: 1px solid #cccccc; padding: 0px 5px 0px 0px;"><a href="#primaryContact">Primary Contact</a></li>
		<li style="float: left; margin: 5px 5px 0px 0px; border-right: 1px solid #cccccc; padding: 0px 5px 0px 0px;"><a href="#secondaryContact">Secondary Contact</a></li>
		<li style="float: left; margin: 5px 5px 0px 0px; border-right: 1px solid #cccccc; padding: 0px 5px 0px 0px;"><a href="#otherContent">Other Contact</a></li>
		<li style="float: left; margin: 5px 5px 0px 0px; border-right: 1px solid #cccccc; padding: 0px 5px 0px 0px;"><a href="#companyDetailsandEquipment">Company Details and Equipment</a></li>
		<li style="float: left; margin: 5px 5px 0px 0px; border-right: 1px solid #cccccc; padding: 0px 5px 0px 0px;"><a href="#extensionRequestsContact">Extension Requests Contact</a></li>
		<li style="float: left; margin: 5px 5px 0px 0px; border-right: 1px solid #cccccc; padding: 0px 5px 0px 0px;"><a href="#receivingDays">Receiving Days</a></li>
		<li style="float: left; margin: 5px 5px 0px 0px; padding: 0px 5px 0px 0px;"><a href="#receivingDetails">Receiving Details</a></li>
	</ul>

	<hr style="background: #cccccc; margin: 10px 0px; border: 0px; height: 1px; clear: both;" />

	<div class="title_barIC">
		<a name="newspaperCompanyContactInformation"></a>
		<h4 class="titlebg"><span class="ie6_header floatleft">Newspaper/Company Contact Information</span></h4>
	</div>
	<dl>
		<?php
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['newspaper_name']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['address']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['address2']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['city']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['state']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['country']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['zip']);
		?>
	</dl>

	<div class="title_barIC">
		<a name="primaryContact"></a>
		<h4 class="titlebg"><span class="ie6_header floatleft">Primary Contact</span></h4>
	</div>
	<dl>
		<?php
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['primary_name']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['primary_position']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['primary_phone']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['primary_fax']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['primary_email']);
		?>
	</dl>

	<div class="title_barIC">
		<a name="secondaryContact"></a>
		<h4 class="titlebg"><span class="ie6_header floatleft">Secondary Contact</span></h4>
	</div>
	<dl>
		<?php
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['secondary_name']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['secondary_position']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['secondary_phone']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['secondary_fax']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['secondary_email']);
		?>
	</dl>

	<div class="title_barIC">
		<a name="otherContact"></a>
		<h4 class="titlebg"><span class="ie6_header floatleft">Other Contact Information</span></h4>
	</div>
	<dl>
		<?php
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['phone_emergency']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['phone_security']);
		?>
	</dl>

	<div class="title_barIC">
		<a name="companyDetailsandEquipment"></a>
		<h4 class="titlebg"><span class="ie6_header floatleft">Company Details and Equipment</span></h4>
	</div>
	<dl>
		<?php
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['circulation_volume']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['inserting_equipment']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['forklifts']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['pallet_jacks']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['staff_receiving']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['staff_inserting']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['commercial_printing']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['load_packaging']);
		?>
	</dl>

	<div class="title_barIC">
		<a name="extensionRequestsContact"></a>
		<h4 class="titlebg"><span class="ie6_header floatleft">Extension Requests Contact</span></h4>
	</div>
	<dl>
		<?php
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['extension_requests_name']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['extension_requests_position']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['extension_requests_phone']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['extension_requests_fax']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['extension_requests_email']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['extension_requests_comments']);
		?>
	</dl>

	<div class="title_barIC">
		<a name="receivingDays"></a>
		<h4 class="titlebg"><span class="ie6_header floatleft">Receiving Days and Hours for Insert Delivery</span></h4>
	</div>
	<dl>
		<?php
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['hours_monday'], $emptyValue = 'Closed');
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['hours_tuesday'], $emptyValue = 'Closed');
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['hours_wednesday'], $emptyValue = 'Closed');
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['hours_thursday'], $emptyValue = 'Closed');
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['hours_friday'], $emptyValue = 'Closed');
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['hours_saturday'], $emptyValue = 'Closed');
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['hours_sunday'], $emptyValue = 'Closed');
		?>
	</dl>

	<div class="title_barIC">
		<a name="receivingDetails"></a>
		<h4 class="titlebg"><span class="ie6_header floatleft">Receiving Details</span></h4>
	</div>
	<dl>
		<dt><strong>Advanced insert receiving times: </strong></dt>
		<dd></dd>
		<?php
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['advanced_receiving_sundays']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['advanced_receiving_daily']);
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['unloading_equipment']);
		echo _mailroommatters_renderGroupWithOptions('Receiving Challenges or Comments', array(
			$context['mailroommatters']['fields']['receiving_challenges_difficult_access'],
			$context['mailroommatters']['fields']['receiving_challenges_no_turnaround'],
			$context['mailroommatters']['fields']['receiving_challenges_unpaved'],
			$context['mailroommatters']['fields']['receiving_challenges_comments']
			));
		echo _mailroommatters_renderGroupWithOptions('Driver Privileges and Access', array(
			$context['mailroommatters']['fields']['driver_privileges_office_only'],
			$context['mailroommatters']['fields']['driver_privileges_truck_only'],
			$context['mailroommatters']['fields']['driver_privileges_unloading_participation'],
			$context['mailroommatters']['fields']['driver_privileges_comments']
			));
		echo _mailroommatters_renderField($context['mailroommatters']['fields']['digital_pictures']);
		?>
	</dl>

	<?php
	$content = ob_get_contents();
	ob_end_clean();

	// Build the header / wrap for the main content described above
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
function _mailroommatters_editField($field, $wrap = true, $strongTitle = true, $inline = false) {
	global $context;

	$fieldInput = '';
	$properties = array();
	$currentValue = (array_key_exists('save_value', $field) ? $field['save_value'] : $context['mailroommatters']['profile'][$field['database_field']]);

	if (!empty($field['edit_label'])) {
		$field['label'] = $field['edit_label'];
	}

	if ($field['required']) {
		$field['label'] .= ' *';
	}

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

			$fieldInput = sprintf(
				'<input type="hidden" name="%s" value="0" /><input type="checkbox" id="%s", name="%s", value="%s" %s/> <label for="%s">%s</label><br />',
				$field['database_field'],
				$field['database_field'],
				$field['database_field'],
				$field['value'],
				($currentValue == $field['value'] ? 'checked ' : ''),
				$field['database_field'],
				$field['label']
				);

			// Do not wrap
			return $fieldInput;
			break;

		case 'yesno':
			$field['options'] = array(0 => 'No', 1 => 'Yes');

		case 'select':
			foreach ($field['options'] as $value => $label) {
				$fieldInput .= '<option value="'. $value .'"'. ($value == $currentValue ? ' selected' : '') .'>'. $label .'</option>';
			}
			$fieldInput = '<select name="'. $field['database_field'] .'">'. $fieldInput .'</select>';
			break;

		case 'text':
			$properties['size'] = '50';

		// case 'number':
		// 	$properties['size'] = '20';
		// 	$field['type'] = 'text';

		default:
			$properties['type'] = $field['type'];
			$properties['name'] = $field['database_field'];
			$properties['value'] = $currentValue;
			foreach ($properties as $key => $value) {
				$fieldInput .= $key .'="'. htmlspecialchars($value, ENT_QUOTES) .'" ';
			}
			$fieldInput = '<input '. $fieldInput .'/>';
	}

	if ($wrap) {
		$content = sprintf(
			'<dt>%s%s%s</dt><dd>%s%s</dd>',
			$field['label'],
			(empty($field['error']) ? '' : ' <span class="smalltext error">'. $field['error'] .'</span>'),
			(empty($field['subtext']) ? '' : '<br /><span class="smalltext">'. $field['subtext'] .'</span>'),
			$fieldInput,
			(empty($field['after_input']) ? '' : ' '. $field['after_input'])
			);
	} elseif ($inline) {
		$content = sprintf(
			'%s: %s %s%s %s<br />',
			($strongTitle ? '<strong>'. $field['label'] .'</strong>' : $field['label']),
			(empty($field['subtext']) ? '' : '<span class="smalltext">'. $field['subtext'] .'</span>'),
			$fieldInput,
			(empty($field['after_input']) ? '' : ' '. $field['after_input']),
			(empty($field['error']) ? '' : '<span class="smalltext error">'. $field['error'] .'</span>')
			);
	} else {
		$content = sprintf(
			'%s%s%s<br />%s%s',
			($strongTitle ? '<strong>'. $field['label'] .'</strong>' : $field['label']),
			(empty($field['error']) ? '' : '<span class="smalltext error">'. $field['error'] .'</span>'),
			(empty($field['subtext']) ? '' : '<br /><span class="smalltext">'. $field['subtext'] .'</span>'),
			$fieldInput,
			(empty($field['after_input']) ? '' : ' '. $field['after_input'])
			);
	}


	return $content;
}

/**
 * Show a built answer for a single question comprised of many different option fields, plus potentially a
 * field for 'other/comments' at the end.
 * @param string $label
 * @param array $fieldList  Array of $field arrays
 * @return string
 */
function _mailroommatters_renderGroupWithOptions($label, $fieldList) {
	global $context;
	$contentStatements = array();

	foreach ($fieldList as $field) {
		$currentValue = $context['mailroommatters']['profile'][$field['database_field']];
		switch ($field['type']) {
			case 'check':
				if ($currentValue) {
					$currentValue = $field['label'];
				}
				break;

			case 'textarea':
				$currentValue = nl2br(htmlspecialchars($currentValue));
				break;

			default:
				$currentValue = htmlspecialchars($fieldValue);
		}
		if (!empty($currentValue)) {
			$contentStatements[] = $currentValue;
		}
	}

	$content = '
		<dt>'. $label .'</dt>
		<dd>'. implode('<br />', $contentStatements) .'</dd>
		';

	return $content;
}

/**
 * Call to show a value for a profile field, if it isn't empty
 * @param array $field
 * @return string
 */
function _mailroommatters_renderField($field, $emptyValue = '') {
	global $context;

	if ($field['type'] == 'section') {
		return _mailroommatters_renderSection($field, '_mailroommatters_renderField');
	}

	$currentValue = $context['mailroommatters']['profile'][$field['database_field']];

	switch ($field['type']) {
		case 'number':
			$currentValue = floatval($currentValue);
			break;

		case 'select':
			if (array_key_exists($currentValue, $field['options'])) {
				$currentValue = $field['options'][$currentValue];
			} else {
				$currentValue = $emptyValue;
			}
			break;

		case 'yesno':
		case 'check':
			$currentValue = ($currentValue ? 'Yes' : 'No');
			break;

		case 'textarea':
			$currentValue = nl2br(htmlspecialchars($currentValue));
			break;

		default:
			$currentValue = htmlspecialchars($currentValue);
			if (strpos($field['database_field'], '_email') !== false && !empty($currentValue)) {
				$currentValue = '<a href="mailto:'. $currentValue .'">'. $currentValue .'</a>';
			}
	}

	if (empty($currentValue)) {
		$currentValue = $emptyValue;
	}

	$content = '
		<dt>'. $field['label'] .'</dt>
		<dd>'. $currentValue . (!empty($field['after_input']) ? ' '. $field['after_input'] : '') .'</dd>
		';
	return $content;
}
