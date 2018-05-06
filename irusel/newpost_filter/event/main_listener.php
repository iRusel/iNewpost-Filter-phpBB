<?php
/**
 *
 * Display name. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, iRusel
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace irusel\newpost_filter\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Display name Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	static public function getSubscribedEvents()
	{
		return array(
			'core.user_setup'							=> 'load_language_on_setup',
			'core.ucp_prefs_view_data'					=> 'ucp_prefs_view_newpost_filter',			
			'core.ucp_prefs_view_update_data'			=> 'ucp_prefs_update_newpost_filter',	
			'core.search_get_topic_data'				=> 'search_newpost_filter',
		);
	}	

	/* @var \phpbb\db\driver\driver_interface */
	protected $db;

	/* @var \phpbb\auth\auth */
	protected $auth;

	/* @var \phpbb\request\request */
	protected $request;

	/* @var \phpbb\template\template */
	protected $template;

	/* @var \phpbb\user */
	protected $user;

	/* @var string phpEx */
	protected $php_ext;

	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\auth\auth $auth, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $php_ext)
	{
		$this->db 			= $db;
		$this->auth 		= $auth;
		$this->request 		= $request;
		$this->template 	= $template;
		$this->user 		= $user;
		$this->php_ext 		= $php_ext;
	}

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'irusel/newpost_filter',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function ucp_prefs_view_newpost_filter($event)
	{
		$forums_id = $this->user->data['user_newpost_filter'];
		$forums_id_user = explode(" ", $forums_id);		

		$rowset = array();
		$s_forums = '';
		$sql = 'SELECT f.forum_id, f.forum_name, f.parent_id, f.forum_type, f.left_id, f.right_id, f.forum_password, f.enable_indexing, fa.user_id
			FROM ' . FORUMS_TABLE . ' f
			LEFT JOIN ' . FORUMS_ACCESS_TABLE . " fa ON (fa.forum_id = f.forum_id
				AND fa.session_id = '" . $this->db->sql_escape($this->user->session_id) . "')
			ORDER BY f.left_id ASC";
		$result = $this->db->sql_query($sql);

		while ($row = $this->db->sql_fetchrow($result))
		{
			$rowset[(int) $row['forum_id']] = $row;
		}
		$this->db->sql_freeresult($result);

		$right = $cat_right = $padding_inc = 0;
		$padding = $forum_list = $holding = '';
		$pad_store = array('0' => '');

		$vars = array('rowset');
		$holding = '<option value="0">' . $this->user->lang['NEWPOST_SETTING_SHOW_ALL'] . '</option>';

		foreach ($rowset as $row)
		{
			if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
			{
				// Non-postable forum with no subforums, don't display
				continue;
			}

			if ($row['forum_type'] == FORUM_POST && ($row['left_id'] + 1 == $row['right_id']) && !$row['enable_indexing'])
			{
				// Postable forum with no subforums and indexing disabled, don't display
				continue;
			}

			if ($row['forum_type'] == FORUM_LINK || ($row['forum_password'] && !$row['user_id']))
			{
				// if this forum is a link or password protected (user has not entered the password yet) then skip to the next branch
				continue;
			}

			if ($row['left_id'] < $right)
			{
				$padding .= '&nbsp; &nbsp;';
				$pad_store[$row['parent_id']] = $padding;
			}
			else if ($row['left_id'] > $right + 1)
			{
				if (isset($pad_store[$row['parent_id']]))
				{
					$padding = $pad_store[$row['parent_id']];
				}
				else
				{
					continue;
				}
			}

			$right = $row['right_id'];

			if ($this->auth->acl_gets('!f_search', '!f_list', $row['forum_id']))
			{
				// if the user does not have permissions to search or see this forum skip only this forum/category
				continue;
			}

			$selected = (in_array($row['forum_id'], $forums_id_user)) ? ' selected="selected"' : '';
			
			if ($row['left_id'] > $cat_right)
			{
				// make sure we don't forget anything
				$s_forums .= $holding;
				$holding = '';
			}

			
			if ($row['right_id'] - $row['left_id'] > 1)
			{
				$cat_right = max($cat_right, $row['right_id']);
				$holding .= '<option value="' . $row['forum_id'] . '"' . $selected . '>' . $padding . $row['forum_name'] . '</option>';
			}
			else
			{
				$s_forums .= $holding . '<option value="' . $row['forum_id'] . '"' . $selected . '>' . $padding . $row['forum_name'] . '</option>';
				$holding = '';
			}
		}

		if ($holding)
		{
			$s_forums .= $holding;
		}


		$this->template->assign_vars(array(	
			'S_FORUM_OPTIONS'		=> $s_forums,
		));			
	}

	public function ucp_prefs_update_newpost_filter($event)
	{				
		$forums_id = $this->request->variable('forum_id', array(0));

		$data_sql = $event['sql_ary'];
		$data_sql = array_merge($data_sql, array(
			'user_newpost_filter'		=> $this->request->variable('forum_id', ($forums_id) ? implode(" ", $forums_id) : 0)
		));
		$event['sql_ary'] = $data_sql;
	}

	public function search_newpost_filter($event)
	{
		$forums_id = $this->user->data['user_newpost_filter'];
		if($forums_id)
		{
			$forums_id_ary = str_replace(" ", ",", $forums_id);
			$forums_id_user = explode(",", $forums_id);
			$event['sql_where'] = " t.forum_id IN (" . $forums_id_ary . ") ";
		}
	}
}
