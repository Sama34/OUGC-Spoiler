<?php

/***************************************************************************
 *
 *   OUGC Spoiler plugin (/inc/plugins/ougc_spoiler.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012-20013 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Hide content within a spoiler tag.
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
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

if(!defined('IN_ADMINCP'))
{
	$plugins->add_hook('parse_message_end', 'ougc_spoiler');
	$plugins->add_hook('global_end', 'ougc_spoiler_js');

	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	$templatelist .= 'ougc_spoiler, ougc_spoiler_js';
}

// Plugin API
function ougc_spoiler_info()
{
	return array(
		'name'			=> 'OUGC Spoiler',
		'description'	=> 'Hide content within posts.',
		'website'		=> 'http://community.mybb.com/user-25096.html',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.0',
		'guid'			=> '',
		'compatibility' => '16*'
	);
}

// _activate
function ougc_spoiler_activate()
{
	ougc_spoiler_deactivate();
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('footer', '#'.preg_quote('<debugstuff>').'#i', '<debugstuff><!--OUGC_SPOILER-->');
}

// _deactivate
function ougc_spoiler_deactivate()
{
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	find_replace_templatesets('footer', '#'.preg_quote('<!--OUGC_SPOILER-->').'#i', '', 0);
}

// Insert JavaScript code into footer
function ougc_spoiler_js()
{
	global $footer, $lang, $templates;
	ougc_spoiler_lang();

	if(!isset($templates->cache['ougc_spoiler_js']))
	{
		$templates->cache['ougc_spoiler_js'] = '<script type="text/javascript">
<!--
function showSpoiler(e)
{
	var el = $(e).up().next();

	el.visible() ? e.value = \'{$lang->show}\' : e.value = \'{$lang->hide}\';
	el.toggle();
}
// -->
</script>';
	}

	eval('$js = "'.$templates->get('ougc_spoiler_js').'";');
	$footer = str_replace('<!--OUGC_SPOILER-->', $js, $footer);
}

// Format spoiler
function ougc_spoiler(&$message)
{
	global $parser;

	$format = true;
	if(is_object($parser))
	{
		$format = (isset($parser->options['allow_mycode']) && !empty($parser->options['allow_mycode']));
	}

	if($format)
	{
		$spoiler = array(
			"#\[spoiler\](.+?)\[\/spoiler\](\r\n?|\n?)#si" => ougc_spoiler_format(0),
			"#\[spoiler=(?:&quot;|\"|')?(.+?)[\"']?(?:&quot;|\"|')?\](.+?)\[\/spoiler\](\r\n?|\n?)#si" => ougc_spoiler_format(1)
		);

		do
		{
			$message = preg_replace(array_keys($spoiler), array_values($spoiler), $message, -1, $count);
		} while($count);
	}
}

// Helper
function ougc_spoiler_format($case)
{
	static $template = array();
	#_dump($template[$case]);

	if(!isset($template[$case]))
	{
		global $templates, $lang;
		ougc_spoiler_lang();

		if(!isset($templates->cache['ougc_spoiler']))
		{
			$templates->cache['ougc_spoiler'] = '<div class="spoiler tborder"><div class="tfoot"><input type="button" value="{$lang->show}" onclick="showSpoiler(this);" /><strong>{$lang->title}:</strong></div><div style="display: none;" class="spoiler_content">{$content}</div></div>';
		}

		$content = '$1';
		if($case)
		{
			$lang->title = '$1';
			$content = '$2';
		}

		eval('$template[$case] = "'.$templates->get('ougc_spoiler').'";');
	}

	return $template[$case];
}

// Load language file/variables
function ougc_spoiler_lang()
{
	global $lang;

	isset($lang->show) or $lang->load('ougc_spoiler', false, true);

	isset($lang->show) or $lang->show = 'Show';
	isset($lang->hide) or $lang->hide = 'Hide';
	isset($lang->title) or $lang->title = 'Spoiler';
}