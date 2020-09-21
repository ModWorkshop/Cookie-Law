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
}

function cookielaw_deactivate(){
	global $mybb, $db;

	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	
	$db->delete_query("settinggroups", "name = 'cookielaw'");
	
	$settings = ["cookielaw_method"];
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");
	
	rebuild_settings();
}

function cookielaw_global_end(){
	cookielaw_clear_cookies();
}

function cookielaw_misc() {
	global $mybb, $lang, $templates, $theme, $cookielaw_info, $header, $headerinclude, $footer;
	
	if($mybb->input['action'] == 'cookielaw_change'){
		if((int)$mybb->input['disallow'] === 1){
			cookielaw_clear_cookies();
			my_setcookie('allow_cookies', '0', null, null, null, true);
		}
		else{
			my_setcookie('allow_cookies', '1', null, null, null, true);

			if($mybb->input['okay'])
				$lang->cookielaw_redirect = '';
		}

		if((int)$mybb->input['back_to_home'] === 1)
			header('LOCATION: /');
		else{
			Utils::api_init();
			Utils::json_echo(['success'=>1]);
		}
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
		'language' => [
			'member' => true,
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