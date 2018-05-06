<?php
/**
 *
 * Display name. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2018, iRusel
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace irusel\newpost_filter\migrations;

class install_user_schema extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{		
		return $this->db_tools->sql_column_exists($this->table_prefix . 'users', 'user_newpost_filter');
	}

	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v31x\v314');
	}

	public function update_schema()
	{
		return array(
			'add_columns'	=> array(
				$this->table_prefix . 'users'			=> array(
					'user_newpost_filter'				=> array('VCHAR_UNI', 0),
				),
			),
		);
	}

	public function revert_schema()
	{
		return array(			
			'drop_columns'	=> array(
				$this->table_prefix . 'users'			=> array(
					'user_newpost_filter',
				),
			),			
		);
	}
}
