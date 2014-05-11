<?php

/***************************************************************************
 *
 *	OUGC Spoiler plugin (/inc/plugins/ougc_spoiler.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2014 Omar Gonzalez
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

// Plugin API
function ougc_spoiler_info()
{
	global $lang;
	ougc_spoiler_lang_load();

	return array(
		'name'			=> 'OUGC Spoiler',
		'description'	=> $lang->ougc_spoiler_desc,
		'website'		=> 'http://mods.mybb.com/view/ougc-spoiler',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.0',
		'versioncode'	=> 1000,
		'compatibility'	=> '16*',
		'guid'			=> '',
		'pl'			=> array(
			'version'	=> 12,
			'url'		=> 'http://mods.mybb.com/view/pluginlibrary'
		)
	);
}

// _activate() routine
function ougc_spoiler_activate()
{
	global $PL, $cache;
	ougc_spoiler_deactivate();

	// Add template group
	$PL->templates('ougcspoiler', '<lang:ougc_spoiler>', array(
		''	=> '<div class="spoiler tborder"><div class="tfoot"><input type="button" value="{$lang->show}" onclick="showSpoiler(this);" /><strong>{$lang->title}:</strong></div><div style="display: none;" class="spoiler_content">{$content}</div></div>',
		'js'	=> '<script type="text/javascript">
<!--
function showSpoiler(e)
{
	var el = $(e).up().next();

	el.visible() ? e.value = \'{$lang->show}\' : e.value = \'{$lang->hide}\';
	el.toggle();
}
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
	ougc_spoiler_pl_check();

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
	ougc_spoiler_pl_check();

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

// Insert JavaScript code into footer
function ougc_spoiler_js()
{
	global $templates, $lang, $footer;
	ougc_spoiler_lang_load();

	eval('$js = "'.$templates->get('ougcspoiler_js').'";');
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
			"#\[spoiler=(?:&quot;|\"|')?(.+?)[\"']?(?:&quot;|\"|')?\](.+?)\[\/spoiler\](\r\n?|\n?)#si" => ougc_spoiler_format('custom')
		);

		do
		{
			$message = preg_replace(array_keys($spoiler), array_values($spoiler), $message, -1, $count);
		} while($count);
	}
}

// Helper
function ougc_spoiler_format($type='default')
{
	static $template = array();

	if(!isset($spoiler[$type]))
	{
		global $templates, $lang;
		ougc_spoiler_lang_load();

		$lang->title = $type ? '$1' : $lang->title;
		$content = $type ? '$2' : '$1';

		eval('$spoiler[$type] = "'.$templates->get('ougc_spoiler').'";');
	}

	return $spoiler[$type];
}

// Load language file/variables
function ougc_spoiler_lang_load()
{
	global $lang;

	isset($lang->ougc_spoiler) or $lang->load('ougc_spoiler', false, true);

	if(!isset($lang->ougc_spoiler))
	{
		// Plugin API
		$lang->ougc_spoiler = 'OUGC Spoiler';
		$lang->ougc_spoiler_desc = 'Hide content within a spoiler tag.';

		// Spoiler MyCode
		$lang->show = 'Show';
		$lang->hide = 'Hide';
		$lang->title = 'Spoiler';
	}
}