<?php
/**
 * Cookie Law 1.0.0

 * Copyright 2016 Matthew Rogowski

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
**/

if(!defined("IN_MYBB"))
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");

$plugins->add_hook('global_start', 'cookielaw_global_intermediate');
$plugins->add_hook('global_intermediate', 'cookielaw_global_intermediate');
$plugins->add_hook('global_end', 'cookielaw_global_end');
$plugins->add_hook('misc_start', 'cookielaw_misc');
$plugins->add_hook('admin_load', 'cookielaw_clear_cookies');

function cookielaw_info(){
	return [
		"name" => "Cookie Law",
		"description" => "Give information and gain consent for cookies to be set by the forum.",
		"website" => "https://github.com/MattRogowski/Cookie-Law",
		"author" => "Matt Rogowski",
		"authorsite" => "https://matt.rogow.ski",
		"version" => "1.0.0",
		"compatibility" => "16*,18*",
		"codename" => "cookielaw"
	];
}

function cookielaw_activate(){
	global $db;
	
	cookielaw_deactivate();
	
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	$settings_group = [
		"name" => "cookielaw",
		"title" => "Cookie Law Settings",
		"description" => "Settings for the cookie law plugin.",
		"disporder" => "28",
		"isdefault" => 0
	];
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$settings = [];
	$settings[] = [
		"name" => "cookielaw_method",
		"title" => "Display Method",
		"description" => "How do you want the message to function?<br /><strong>Notify:</strong> A message will be displayed notifying users that cookies are used, but no method of opting out.<br /><strong>Opt In/Out:</strong> Give people a choice on whether they want to accept the use of cookies or not.",
		"optionscode" => "radio
notify=Notify
opt=Opt In/Out",
		"value" => "notify"
	];
	$i = 1;
	foreach($settings as $setting){
		$insert = [
			"name" => $db->escape_string($setting['name']),
			"title" => $db->escape_string($setting['title']),
			"description" => $db->escape_string($setting['description']),
			"optionscode" => $db->escape_string($setting['optionscode']),
			"value" => $db->escape_string($setting['value']),
			"disporder" => intval($i),
			"gid" => intval($gid),
		];
		$db->insert_query("settings", $insert);
		$i++;
	}
	
	rebuild_settings();
	
	$templates = [];
	$templates[] = [
		"title" => "cookielaw_info",
		"template" => "<html>
<head>
<title>{\$lang->cookielaw_info_title}</title>
{\$headerinclude}
<script type=\"text/javascript\">
".$js_info."
</script>
</head>
<body>
{\$header}
<form action=\"{\$mybb->settings['bburl']}/misc.php?action=cookielaw_change\" method=\"post\">
	<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
		<tr>		
			<td class=\"thead\" colspan=\"4\"><strong>{\$lang->cookielaw_header}</strong></td>
		</tr>
		<tr>		
			<td class=\"trow1\" colspan=\"4\">{\$lang->cookielaw_description}</td>
		</tr>
		<tr>		
			<td class=\"tcat\"><strong>{\$lang->cookielaw_info_cookie_name}</strong></td>
			<td class=\"tcat\"><strong>{\$lang->cookielaw_info_cookie_description}</strong></td>
			<td class=\"tcat\" align=\"center\"><strong>{\$lang->cookielaw_info_cookies_set_logged_in}</strong></td>
			<td class=\"tcat\" align=\"center\"><strong>{\$lang->cookielaw_info_cookies_set_guest}</strong></td>
		</tr>
		{\$cookies_rows}
		<tr>		
			<td class=\"tfoot\" colspan=\"4\"><div style=\"text-align: center;\">{\$buttons}</div></td>
		</tr>
	</table>
</form>
{\$footer}
</body>
</html>"
	];
	$templates[] = [
		"title" => "cookielaw_buttons_notify",
		"template" => "<input type=\"submit\" name=\"okay\" value=\"{\$lang->cookielaw_ok}\" />{\$more_info}<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />"
	];
	$templates[] = [
		"title" => "cookielaw_buttons_opt",
		"template" => "<input type=\"submit\" name=\"allow\" value=\"{\$lang->cookielaw_allow}\" /> <input type=\"submit\" name=\"disallow\" class=\"cookielaw_disallow\" value=\"{\$lang->cookielaw_disallow}\" />{\$more_info}<input type=\"hidden\" name=\"my_post_key\" value=\"{\$mybb->post_code}\" />"
	];
	$templates[] = [
		"title" => "cookielaw_button_more_info",
		"template" => "<input type=\"submit\" name=\"more_info\" value=\"{\$lang->cookielaw_more_info}\" />"
	];

	foreach($templates as $template){
		$insert = [
			"title" => $db->escape_string($template['title']),
			"template" => $db->escape_string($template['template']),
			"sid" => "-1",
			"version" => "1800",
			"status" => "",
			"dateline" => TIME_NOW
		];

		$db->insert_query("templates", $insert);
	}
}

function cookielaw_deactivate(){
	global $mybb, $db;

	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	$db->delete_query("settinggroups", "name = 'cookielaw'");
	
	$settings = ["cookielaw_method"];
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
	
	find_replace_templatesets("header", "#".preg_quote('{$cookielaw}')."#i", '', 0);
	if(substr($mybb->version, 0, 3) == '1.6')
		find_replace_templatesets("footer", "#".preg_quote(' | <a href="{$mybb->settings[\'bburl\']}/misc.php?action=cookielaw_info">{$lang->cookielaw_footer}</a>')."#i", '', 0);
	elseif(substr($mybb->version, 0, 3) == '1.8')
		find_replace_templatesets("footer", "#".preg_quote("\n\t\t\t\t".'<li><a href="{$mybb->settings[\'bburl\']}/misc.php?action=cookielaw_info">{$lang->cookielaw_footer}</a></li>')."#i", '', 0);
	
	$db->delete_query("templates", "title IN ('cookielaw_info','cookielaw_header','cookielaw_buttons_notify','cookielaw_buttons_opt','cookielaw_button_more_info','cookielaw_header_no_cookies')");
}

function cookielaw_global_end(){
	cookielaw_clear_cookies();
}

function cookielaw_misc() {
	global $mybb, $lang, $templates, $theme, $cookielaw_info, $header, $headerinclude, $footer;
	
	$lang->load('cookielaw');
	
	if($mybb->input['action'] == 'cookielaw_change'){
		if(isset($mybb->input['more_info']))
			header('LOCATION: /misc.php?action=cookielaw_info');
		else{
			if(isset($mybb->input['disallow'])){
				cookielaw_clear_cookies();
				my_setcookie('allow_cookies', '0', null, null, null, true);
			}
			else{
				my_setcookie('allow_cookies', '1', null, null, null, true);

				if($mybb->input['okay'])
					$lang->cookielaw_redirect = '';
			}
			header('LOCATION: /');
		}
	}
	elseif($mybb->input['action'] == 'cookielaw_info'){
		$cookies_rows = '';
		$cookies = cookielaw_get_cookies();
		foreach($cookies as $cookie_name => $info){
			if(isset($info['mod']) || isset($info['admin'])){
				$cookie_user_type = '';
				if($info['mod'])
					$cookie_user_type = $lang->cookielaw_info_cookies_set_mod;
				elseif($info['admin'])
					$cookie_user_type = $lang->cookielaw_info_cookies_set_admin;
				
				$trow = alt_trow();
				$cookie_description = 'cookielaw_cookie_'.$cookie_name.'_desc';
				$cookies_rows .= '<tr>
					<td class="'.$trow.'">'.$cookie_name.'</td>
					<td class="'.$trow.'">'.$lang->$cookie_description.'</td>
					<td class="'.$trow.'" align="center">'.$cookie_user_type.'</td>
					<td class="'.$trow.'" align="center">-</td>
				</tr>';
			}
			else{
				if(substr($mybb->version, 0, 3) == '1.6')
					$ext = 'gif';
				elseif(substr($mybb->version, 0, 3) == '1.8')
					$ext = 'png';
				
				$cookie_member = $cookie_guest = '';
				if($info['member'])
					$cookie_member = '<img src="'.$mybb->settings['bburl'].'/images/valid.'.$ext.'" alt="" title="" />';
				else
					$cookie_member = '<img src="'.$mybb->settings['bburl'].'/images/invalid.'.$ext.'" alt="" title="" />';
				if($info['guest'])
					$cookie_guest = '<img src="'.$mybb->settings['bburl'].'/images/valid.'.$ext.'" alt="" title="" />';
				else
					$cookie_guest = '<img src="'.$mybb->settings['bburl'].'/images/invalid.'.$ext.'" alt="" title="" />';

				$trow = alt_trow();
				$cookie_description = 'cookielaw_cookie_'.$cookie_name.'_desc';
				$cookies_rows .= '<tr>
					<td class="'.$trow.'">'.$cookie_name.'</td>
					<td class="'.$trow.'">'.$lang->$cookie_description.'</td>
					<td class="'.$trow.'" align="center">'.$cookie_member.'</td>
					<td class="'.$trow.'" align="center">'.$cookie_guest.'</td>
				</tr>';
			}
		}
		
		if($mybb->settings['cookielaw_method'] == 'opt')
			eval("\$buttons = \"".$templates->get("cookielaw_buttons_".$mybb->settings['cookielaw_method'])."\";");

		eval("\$cookielaw_info = \"".$templates->get("cookielaw_info")."\";");
		output_page($cookielaw_info);
	}
}

function cookielaw_clear_cookies(){
	global $mybb, $session;
	
	if(isset($mybb->cookies['allow_cookies']) && (int)$mybb->cookies['allow_cookies'] === 0 && !defined('IN_ADMINCP')){
		unset($mybb->user);
		unset($mybb->session);
		$session->load_guest();

		$cookies = cookielaw_get_cookies(true);
		foreach($cookies as $cookie_name => $info){
			if($cookie_name == 'allow_cookies')
				continue;

			my_unsetcookie($cookie_name);
		}
		foreach($mybb->cookies as $key => $val){
			if(strpos($key, 'inlinemod_') !== false)
				my_unsetcookie($key);
		}
	}
}

function cookielaw_get_cookies($all = false){
	global $mybb;

	$cookies = [
		'sid' => [
			'member' => true,
			'guest' => true
		],
		'mybbuser' => [
			'member' => true,
			'guest' => false
		],
		'mybb[lastvisit]' => [
			'member' => false,
			'guest' => true
		],
		'mybb[lastactive]' => [
			'member' => false,
			'guest' => true
		],
		'mybb[threadread]' => [
			'member' => false,
			'guest' => true
		],
		'mybb[forumread]' => [
			'member' => false,
			'guest' => true
		],
		'mybb[readallforums]' => [
			'member' => false,
			'guest' => true
		],
		'mybb[announcements]' => [
			'member' => true,
			'guest' => true
		],
		'mybb[referrer]' => [
			'member' => false,
			'guest' => true
		],
		'forumpass' => [
			'member' => true,
			'guest' => true
		],
		'mybblang' => [
			'member' => false,
			'guest' => true
		],
		'pollvotes' => [
			'member' => true,
			'guest' => true
		],
		'mybbratethread' => [
			'member' => false,
			'guest' => true
		],
		'multiquote' => [
			'member' => true,
			'guest' => true
		],
		
		'loginattempts' => [
			'member' => false,
			'guest' => true
		],
		'failedlogin' => [
			'member' => false,
			'guest' => true
		],
		'fcollapse' => [
			'member' => false,
			'guest' => true
		],
		'mybbtheme' => [
			'member' => false,
			'guest' => true
		],
		'collapsed' => [
			'member' => true,
			'guest' => true
		],
		'coppauser' => [
			'member' => false,
			'guest' => true
		],
		'coppadob' => [
			'member' => false,
			'guest' => true
		],
		'allow_cookies' => [
			'member' => true,
			'guest' => true
		],
		'theme_color' => [
			'member' => true,
			'guest' => true
		],
		'mods_displaymode' => [
			'member' => true,
			'guest' => true
		]
	];
	
	if($all || is_moderator())
		$cookies['inlinemod_*'] = ['mod' => true];
	
	if($all || (int)$mybb->usergroup['cancp'] === 1){
		$cookies['adminsid'] = ['admin' => true];
		$cookies['acploginattempts'] = ['admin' => true];
		$cookies['acpview'] = ['admin' => true];
	}
	
	return $cookies;
}
?>