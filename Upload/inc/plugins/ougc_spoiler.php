<?php

/***************************************************************************
 *
 *	OUGC Spoiler plugin (/inc/plugins/ougc_spoiler.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2019 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Hide content within a spoiler tag.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Run the ACP hooks.
if(!defined('IN_ADMINCP'))
{
	$plugins->add_hook('parse_message_end', 'ougc_spoiler');
	$plugins->add_hook('global_end', 'ougc_spoiler_js');

	global $templatelist;

	if(!isset($templatelist))
	{
		$templatelist = '';
	}
	else
	{
		$templatelist .= ',';
	}

	$templatelist .= 'ougcspoiler, ougcspoiler_js';
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Plugin API
function ougc_spoiler_info()
{
	global $lang;
	ougc_spoiler_lang_load();

	return array(
		'name'			=> 'OUGC Spoiler',
		'description'	=> $lang->ougc_spoiler_desc,
		'website'		=> 'https://omarg.me/thread?public/plugins/mybb-ougc-spoiler',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.8.19',
		'versioncode'	=> 1819,
		'compatibility'	=> '18*',
		'codename'		=> 'ougc_awards',
		'pl'			=> array(
			'version'	=> 13,
			'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
		)
	);
}

// _activate() routine
function ougc_spoiler_activate()
{
	global $PL, $cache;
	ougc_spoiler_pluginlibrary_helper();
	ougc_spoiler_deactivate();

	// Add template group
	$PL->templates('ougcspoiler', 'OUGC Spoiler', array(
		''	=> '<div class="spoiler tborder">
	<div class="tcat">
		<input type="button" value="{$lang->ougc_spoiler_show}" class="button float_right" onclick="return OUGC_Plugins.LoadSpoiler(event); return false;" />
		<strong>{$lang->ougc_spoiler_title}:</strong>
	</div>
	<div style="display: none;" class="spoiler_content">
		{$content}
	</div>
</div>',
		'js'	=> '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/ougc_spoiler.js"></script>
<script type="text/javascript">
<!--
	lang.ougc_spoiler_show = "{$lang->ougc_spoiler_show}";
	lang.ougc_spoiler_hide = "{$lang->ougc_spoiler_hide}";
	lang.ougc_spoiler_title = "{$lang->ougc_spoiler_title}";
// -->
</script>'
	));

	// Modify templates
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('footer', '#'.preg_quote('<debugstuff>').'#i', '<debugstuff><!--OUGC_SPOILER-->');

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_spoiler_info();

	if(!isset($plugins['spoiler']))
	{
		$plugins['spoiler'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['spoiler'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _deactivate() routine
function ougc_spoiler_deactivate()
{
	// Revert template edits
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('footer', '#'.preg_quote('<!--OUGC_SPOILER-->').'#i', '', 0);
}

// _is_installed() routine
function ougc_spoiler_is_installed()
{
	global $cache;

	$plugins = (array)$cache->read('ougc_plugins');

	return !empty($plugins['spoiler']);
}

// _uninstall() routine
function ougc_spoiler_uninstall()
{
	global $PL, $cache;
	ougc_spoiler_pluginlibrary_helper();

	$PL->templates_delete('ougcspoiler');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['spoiler']))
	{
		unset($plugins['spoiler']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$PL->cache_delete('ougc_plugins');
	}
}

// PluginLibrary dependency check & load
function ougc_spoiler_pluginlibrary_helper()
{
	global $lang, $awards;
	ougc_spoiler_lang_load();
	$info = ougc_spoiler_info();

	if(!file_exists(PLUGINLIBRARY))
	{
		flash_message($lang->sprintf($lang->ougc_spoiler_pluginlibrary_required, $info['pl']['url'], $info['pl']['version']), 'error');
		admin_redirect('index.php?module=config-plugins');
		exit;
	}

	global $PL;

	$PL or require_once PLUGINLIBRARY;

	if($PL->version < $info['pl']['version'])
	{
		flash_message($lang->sprintf($lang->ougc_spoiler_pluginlibrary_old, $info['pl']['url'], $info['pl']['version'], $PL->version), 'error');
		admin_redirect('index.php?module=config-plugins');
		exit;
	}
}

// Insert JavaScript code into footer
function ougc_spoiler_js()
{
	global $mybb, $templates, $lang, $footer;
	ougc_spoiler_lang_load();

	$js = eval($templates->render('ougcspoiler_js'));
	$footer = str_replace('<!--OUGC_SPOILER-->', $js, $footer);
}

// Format spoiler
function ougc_spoiler(&$message)
{
	global $parser;

	if(is_object($parser) && !empty($parser->options))
	{
		$format = (isset($parser->options['allow_mycode']) && !empty($parser->options['allow_mycode']));
	}

	if(!empty($format))
	{
		$spoiler = array(
			"#\[spoiler\](.+?)\[\/spoiler\](\r\n?|\n?)#si" => ougc_spoiler_format(),
			"#\[spoiler=(?:&quot;|\"|')?(.+?)[\"']?(?:&quot;|\"|')?\](.+?)\[\/spoiler\](\r\n?|\n?)#si" => ougc_spoiler_format(false)
		);

		do
		{
			$message = preg_replace(array_keys($spoiler), array_values($spoiler), $message, -1, $count);
		}
		while($count);
	}
}

// Helper
function ougc_spoiler_format($simod=true)
{
	static $spoiler_tmpls = array();

	if(!isset($spoiler_tmpls[$simod]))
	{
		global $templates, $lang;
		ougc_spoiler_lang_load();

		$content = '$1';
		if(!$simod)
		{
			$lang->ougc_spoiler_title = '$1';
			$content = '$2';
		}

		$spoiler_tmpls[$simod] = eval($templates->render('ougcspoiler'));
	}

	return $spoiler_tmpls[$simod];
}

// Load language file/variables
function ougc_spoiler_lang_load()
{
	global $lang;

	isset($lang->ougc_spoiler) or $lang->load('ougc_spoiler', true);
}