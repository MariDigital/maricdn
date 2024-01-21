<?php
class MariCDN 
{

	/**
	 * Returns the array of all the options with their default values in case they are not set
	 */
	public static function getOptions() {
		return wp_parse_args(
			get_option('maricdn'),
			array(
				"advanced_edit" => 1,
				"pull_zone" => "",
				"cdn_domain_name" => "",
				"excluded" => MARICDN_DEFAULT_EXCLUDED,
				"directories" => MARICDN_DEFAULT_DIRECTORIES,
				"site_url" => get_option('home'),
				"disable_admin" => 1,
				"api_key" => ""
			)
		);
	}

	/**
	 * Returns the option value for the given option name. If the value is not set, the default is returned.
	 */
	public static function getOption($option)
	{
		$options = MariCDN::getOptions();
		return $options[$option];
	}


	public static function validateSettings($data)
	{
		$cdn_domain_name = MariCDN::cleanHostname($data['cdn_domain_name']);
		$pull_zone = MariCDN::cleanHostname($data['pull_zone']);

		if (strlen($cdn_domain_name) > 0 && MariCDN::endsWith($cdn_domain_name, MARICDN_PULLZONEDOMAIN)) {
			$pull_zone = substr($cdn_domain_name, 0, strlen($cdn_domain_name) - strlen(MARICDN_PULLZONEDOMAIN) - 1);
		} else {
			$pull_zone = "";
		}

		$siteUrl = $data["site_url"];
		while (substr($siteUrl, -1) == '/') {
			$siteUrl = substr($siteUrl, 0, -1);
		}

		return array(
			"advanced_edit" => (int) ($data['advanced_edit']),
			"pull_zone" => $pull_zone,
			"cdn_domain_name" => $cdn_domain_name,
			"excluded" => esc_attr($data['excluded']),
			"directories" => esc_attr($data['directories']),
			"site_url" => $siteUrl,
			"disable_admin" => (int) ($data['disable_admin']),
			"api_key" => $data['api_key'],
		);
	}

	public static function cleanHostname($hostname)
	{
		$hostname = str_replace("http://", "", $hostname);
		$hostname = str_replace("https://", "", $hostname);

		return str_replace("/", "", $hostname);
	}

	public static function startsWith($haystack, $needle)
	{
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	public static function endsWith($haystack, $needle)
	{
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}
}


class MariCDNSettings
{
	// Initialize the settings page
	public static function initialize()
	{
		add_menu_page(
			"MariCDN", 					//$page_title
			"MariCDN", 					//$menu_title
			"manage_options", 			//$capability
			"maricdn", 					//$menu_slug
			array(						//$function 
				'MariCDNSettings',
				'outputSettingsPage'
			),
			"dashicons-performance"		//$icon_url
		);


		add_submenu_page(
			'maricdn',
			"MariCDN Purge Cache", 		//$page_title
			"Purge Cache", 				//$menu_title
			"manage_options", 			//$capability
			"maricdnpurgecache", 		//$menu_slug
			array(						//$function 
				'MariCDNSettings',
				'outputSettingsPage2'
			)
		);

		register_setting('maricdn', 'maricdn', array("MariCDN", "validateSettings"));
	}

	public static function outputSettingsPage()
	{
		$options = MariCDN::getOptions();

		?>
		<div class="tead" style="width: 550px; padding-top: 20px; margin-left: auto; margin-right: auto; position: relative;">
			<a href="https://maricdn.com" target="_blank"><img width="250" src="<?php echo esc_url(plugins_url('maricdn-logo.svg', __FILE__)); ?>?v=2" alt="MariCDN"></a>
			<?php
			if (strlen(trim($options["cdn_domain_name"])) == 0) {
				echo '<h2>' . esc_html__('MariCDN by MariHost. Enable MariCDN Content Delivery Network', 'your-text-domain') . '</h2>';
			} else {
				echo '<h2>' . esc_html__('MariCDN by MariHost. Configure MariCDN Content Delivery Network', 'your-text-domain') . '</h2>';
			}
			?>

			<form id="maricdn_options_form" method="post" action="options.php">
				<?php settings_fields('maricdn') ?>

				<input type="hidden" name="maricdn[advanced_edit]" id="maricdn_advanced_edit" value="<?php echo esc_html($options['advanced_edit']); ?>" />
				<input type="hidden" name="maricdn[disable_admin]" id="maricdn_disable_admin" value="<?php echo esc_html($options['disable_admin']); ?>" />

				<!-- Simple settings. To remove -->
				<div id="maricdn-simple-settings" <?php if ($options["advanced_edit"]) { echo 'style="display: none;"'; } ?>>
					<p>To set up, enter the name of your Pull Zone that was given to you. If you haven't done that, you can <a href="<?php echo esc_url('https://maricdn.com/?originUrl=' . urlencode(get_option('home'))); ?>" target="_blank">create a new account now</a>. It should only take a minute. After that, just click on the Enable MariCDN button and enjoy a faster website.</p>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								Pull Zone Name:
							</th>
							<td>
								<input type="text" maxlength="40" placeholder="mypullzone" name="maricdn[pull_zone]" id="maricdn_pull_zone" value="<?php echo esc_attr($options['pull_zone']); ?>" size="64" class="regular-text code" />
								<p class="description">The name of the pull zone that you have created for this site. <strong>Do not include the .<?php echo esc_html(MARICDN_PULLZONEDOMAIN); ?></strong>. Leave empty to disable CDN integration.</p>
							</td>
						</tr>
					</table>
				</div>


				<!-- Advanced settings -->
				<br><p>If you need to signup, please <a href="https://maricdn.com/" target="_blank">visit us here</a></p>

				<table id="maricdn-advanced-settings" class="form-table" <?php if (!$options["advanced_edit"]) { echo 'style="display: none;"'; } ?>>
					<tr valign="top">
						<th scope="row">
							CDN Domain Name:
						</th>
						<td>
							<input type="text" name="maricdn[cdn_domain_name]" placeholder="mysite.maricdn.com" id="maricdn_cdn_domain_name" value="<?php echo esc_html($options['cdn_domain_name']); ?>" size="64" class="regular-text code" />
							<p class="description">The CDN domain that you you wish to use to rewrite your links to. This must be a fully qualified domain name and not the name of your pull zone. Leave empty to disable CDN integration.</p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							Site URL:
						</th>
						<td>
							<input type="text" name="maricdn[site_url]" id="maricdn_site_url" value="<?php echo esc_html($options['site_url']); ?>" size="64" class="regular-text code" />
							<p class="description">The public URL where your website is accessible. Default for your configuration <code><?php echo esc_url(get_option('home')); ?></code>.
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							Excluded:
						</th>
						<td>
							<input type="text" name="maricdn[excluded]" id="maricdn_excluded" value="<?php echo esc_html($options['excluded']); ?>" size="64" class="regular-text code" />
							<p class="description">The links containing the listed phrases will be excluded from the CDN. Enter a <code>,</code> separated list without spaces.<br/><br/>Default value: <code><?php echo esc_html(MARICDN_DEFAULT_EXCLUDED); ?></code></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							Included Directories:
						</th>
						<td>
							<input type="text" name="maricdn[directories]" id="maricdn_directories" value="<?php echo esc_html($options['directories']); ?>" size="64" class="regular-text code" />
							<p class="description">Only the files linking inside of this directory will be pointed to their CDN url. Enter a <code>,</code> separated list without spaces.<br/><br/>Default value: <code><?php echo esc_html(MARICDN_DEFAULT_DIRECTORIES); ?></code></p>
						</td>
					</tr>

					<tr valign="top">
						<th scope="row">
							Disable CDN for admin user:
						</th>
						<td>
							<p class="description"><input type="checkbox" id="maricdn_disable_admin_checkbox" class="regular-text code" <?php echo esc_attr($options['disable_admin'] == true ? "checked" : ""); ?> /> If checked, MariCDN.com will be disabled while signed in as an admin user.</p>
						</td>
					</tr>
				</table>

				<div>
					<p class="submit">
						<input type="submit" name="maricdn-save-button" id="maricdn-save-button" class="button submit" value="<?php echo esc_html(strlen(trim($options['cdn_domain_name'])) == 0 ? 'Enable MariCDN' : 'Update CDN Settings'); ?>">
						&nbsp;
						<!--
						<input type="button" id="maricdn-clear-cache-button" class="button submit" value="Clear Cache">
						-->
					</p>
				</div>

				<!--
				<a id="advanced-switch-url" href="#"><?php echo esc_html($options["advanced_edit"] ? "Switch To Simple View" : "Switch To Advanced View"); ?></a>
				-->
				<script>
					jQuery("#maricdn-clear-cache-button").click(function (e) {
						var apiKey = maricdn_getApiKey();
						if (apiKey.length == 0) {
							if (!maricdn_isAdvancedSettingsVisible()) {
								maricdn_showAdvancedSettings();
							}

							jQuery("#maricdn_api_key_notice").show();

							// Scroll to the warning
							jQuery([document.documentElement, document.body]).animate({
								scrollTop: jQuery("#maricdn_api_key_notice").offset().top
							}, 1000);
							jQuery("#maricdn_api_key").focus();
						} else {
							maricdn_showPopupMessage("Clearing Cache ...");
							jQuery.ajax({
								type: "POST",
								url: "https://maricdn.com/api/pullzone/purgeCacheByHostname?hostname=" + "<?php echo urlencode($options['cdn_domain_name']); ?>",
								beforeSend: function (xhr) {
									xhr.setRequestHeader('AccessKey', apiKey);
								},
							}).done(function () {
								setTimeout(function () {
									maricdn_hidePopupMessage();
								}, 300);

							}).fail(function () {
								maricdn_hidePopupMessage();
								alert("Clearing cache failed. Please check your API key.");
							});
						}
					});
					jQuery("#maricdn_pull_zone").keydown(function (e) {
						// Allow: backspace, delete, tab, escape, enter
						if (jQuery.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
							// Allow: Ctrl+A, Command+A
							(e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
							// Allow: home, end, left, right, down, up
							(e.keyCode >= 35 && e.keyCode <= 40) ||
							(e.keyCode >= 65 && e.keyCode <= 90)) {
							// let it happen, don't do anything
							return;
						}
						// Ensure that it is a number and stop the keypress
						if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
							e.preventDefault();
						}
					});
					jQuery("#maricdn_disable_admin_checkbox").click(function (event) {
						var disableAdminChecked = jQuery("#maricdn_disable_admin_checkbox").is(":checked");
						jQuery("#maricdn_disable_admin").val(disableAdminChecked ? "1" : "0");
					});
					jQuery("#maricdn_cdn_domain_name").keydown(function (e) {
						// Allow: backspace, delete, tab, escape, enter
						if (jQuery.inArray(e.keyCode, [109, 189, 46, 8, 9, 27, 13, 110, 190]) !== -1 ||
							// Allow: Ctrl+A, Command+A
							(e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
							// Allow: home, end, left, right, down, up
							(e.keyCode >= 35 && e.keyCode <= 40) ||
							(e.keyCode >= 65 && e.keyCode <= 90)) {
							// let it happen, don't do anything
							return;
						}
						// Ensure that it is a number and stop the keypress
						if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
							e.preventDefault();
						}
					});

					function maricdn_getApiKey() {
						return '<?php echo esc_attr($options["api_key"]); ?>'.trim();
					}

					function maricdn_isAdvancedSettingsVisible() {
						return jQuery('#maricdn-advanced-settings').css("display") != "none";
					}

					function maricdn_showAdvancedSettings() {
						jQuery('#maricdn-advanced-settings').fadeIn("fast");
						jQuery('#maricdn-simple-settings').fadeOut("fast");
						jQuery("#advanced-switch-url").text("Switch To Simple View");
						jQuery("#maricdn_cdn_domain_name").focus();
						jQuery("#maricdn_advanced_edit").val("1");
					}

					function maricdn_showSimpleSettings() {
						jQuery('#maricdn-advanced-settings').fadeOut("fast");
						jQuery('#maricdn-simple-settings').fadeIn("fast");
						jQuery("#advanced-switch-url").text("Switch To Advanced View");
						jQuery("#maricdn_cdn_domain_name").focus();
						jQuery("#maricdn_advanced_edit").val("0");
					}

					function maricdn_showPopupMessage(message) {
						jQuery("#maricdn_popupMessage").text(message);
						jQuery("#maricdn_popupBackground").show("fast");
						jQuery("#maricdn_popupBox").show("fast");

						jQuery([document.documentElement, document.body]).animate({
							scrollTop: 0
						}, 500);
					}

					function maricdn_hidePopupMessage() {
						jQuery("#maricdn_popupBackground").hide("fast");
						jQuery("#maricdn_popupBox").hide("fast");
					}

					jQuery("#advanced-switch-url").click(function (event) {
						if (!maricdn_isAdvancedSettingsVisible()) {
							maricdn_showAdvancedSettings();
						} else {
							maricdn_showSimpleSettings();
						}
					});

					jQuery("#maricdn_pull_zone").change(function (event) {
						var name = jQuery("#maricdn_pull_zone").val();
						if (name.length > 0) {
							var hostname = name + ".<?php echo esc_html(MARICDN_PULLZONEDOMAIN); ?>";
							jQuery("#maricdn_cdn_domain_name").val(hostname);
						} else {
							jQuery("#maricdn_cdn_domain_name").val("");
						}
					});
				</script>
			</form>

			<div id="maricdn_popupBackground" style="display: none; position: absolute; top: 0px; left: 0px; height: 100%; width: 100%; background-color: #f1f1f1; opacity: 0.93;"></div>
			<div id="maricdn_popupBox" style="display: none; position: absolute; top: 0px; left: 0px; height: 100%; width: 100%;">
				<img style="margin-left: auto; margin-right: auto; display: block; margin-top: 110px;" src="<?php echo esc_url(plugins_url('loading-maricdn.png', __FILE__)); ?>"></img>
				<h3 id="maricdn_popupMessage" style="text-align: center;"></h4>
			</div>
		</div><?php
	}

	public static function outputSettingsPage2()
	{
		$options = MariCdn::getOptions();

		?>
		<div class="tead" style="width: 550px; padding-top: 20px; margin-left: auto; margin-right: auto; position: relative;">
			<a href="https://maricdn.com" target="_blank"><img width="250" src="<?php echo esc_url(plugins_url('maricdn-logo.svg', __FILE__)); ?>?v=2"></img></a>
			<?php
				echo '<h2>' . esc_html('MariCDN Cache Control') . '</h2>';
			?>

			<form id="maricdn_purge_form" method="post" action="https://automate.mariwp.com/webhook/cfd93e3d-b1b5-4fb9-b122-f34434000328" target="framesamepage">
				<?php settings_fields('maricdn') ?>

				<div id="maricdn-simple-settings" <?php if ($options["advanced_edit"]) {
														echo 'style="display: none;"';
													} ?>>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								Zone ID:
							</th>
							<td>
								<input type="text" maxlength="40" placeholder="mypullzone" name="zoneid" id="zoneid" value="<?php echo esc_html($options['cdn_domain_name']); ?>" size="64" class="regular-text code" />
							</td>
						</tr>
					</table>
				</div>
				<input type="hidden" id="webid" name="webid" value="dE8TPFJbvzvUyo4wY8RWXAJgywaK">

				<div>

					<p>MariCDN does not monitor the files on your origin server for changes.<br>This means that if a file is already cached on our servers, it will remain cached until the Cache-Control expires or it gets deleted to
						make space for more popular content.<br><br>If you make a change to a file and need to see it reflected immediately,<br>please make sure to purge the cache using the clear cache button.
					</p>

					<p class="submit">
						<input type="submit" name="maricdn-purge" id="maricdn-purge" class="button submit" value="Clear Cache">
					</p>
				</div>
			</form>

			<iframe name="framesamepage" id="framesamepage">Thank you</iframe>

		</div><?php
	}


}

?>
