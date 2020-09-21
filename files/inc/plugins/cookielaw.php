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

$plugins->add_hook('global_end', 'cookielaw_clear_cookies');
$plugins->add_hook('misc_start', 'cookielaw_clear_cookies');

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