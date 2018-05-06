<?php
/**
 *
 * Display name. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, iRusel
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'NEWPOST_SETTING'				=>	'Отображать новые сообщения',	
	'NEWPOST_SETTING_EXPLAIN'		=>	'Новые сообщения будут отображаться из выбранных форумов.',
	'NEWPOST_SETTING_SHOW_ALL'		=>	'Отображать все разделы',
));
