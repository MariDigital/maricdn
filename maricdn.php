<?php
/**
 * Plugin Name: MariCDN by MariHost
 * Plugin URI: https://maricdn.com/
 * Description: Boost your website speed with MariCDN Content Delivery Network (CDN). You can easily enable MariCDN with a click of a button.
 * Version: 0.7.2
 * Author: MariHost
 * Author URI: https://marihost.com/
 * License: GPLv2 or later
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

defined('ABSPATH') OR die();

// Load the paths
define('MARICDN_PLUGIN_FILE', __FILE__);
define('MARICDN_PLUGIN_DIR', dirname(__FILE__));
define('MARICDN_PLUGIN_BASE', plugin_basename(__FILE__));
define('MARICDN_PULLZONEDOMAIN', "b-cdn.net");
define('MARICDN_DEFAULT_DIRECTORIES', "wp-content,wp-includes");
define('MARICDN_DEFAULT_EXCLUDED', ".php");


// Make sure jQuery is included
function theme_scripts() {
  wp_enqueue_script('jquery');
}
add_action('admin_enqueue_scripts', 'theme_scripts');

// Load everything
spl_autoload_register('MariCDNLoad');
function MariCDNLoad($class) 
{
	require_once(MARICDN_PLUGIN_DIR.'/inc/maricdnSettings.php');
	require_once(MARICDN_PLUGIN_DIR.'/inc/maricdnFilter.php');
}

// Register the settings page and menu
add_action("admin_menu", array("MariCDNSettings", "initialize"));


add_action("template_redirect", "doRewrite");
add_action("wp_head", "maricdn_dnsPrefetch", 0);

function doRewrite() 
{
	$options = MariCDN::getOptions();
	if(strlen(trim($options["cdn_domain_name"])) > 0)
	{
		$rewriter = new MariCDNFilter($options["site_url"], (is_ssl() ? 'https://' : 'http://') . $options["cdn_domain_name"], $options["directories"], $options["excluded"], $options["disable_admin"]);
		$rewriter->startRewrite();
	}
}

function maricdn_dnsPrefetch() 
{
	$options = MariCDN::getOptions();
	if(strlen(trim($options["cdn_domain_name"])) > 0)
	{
		echo "<link rel='dns-prefetch' href='//{$options["cdn_domain_name"]}' />";
	}
}

?>