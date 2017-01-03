<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 *	@deprecated		use command 'init' instead
 */
class Hymn_Command_Create extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		Hymn_Client::out( "" );
		Hymn_Client::out( "WARNING: Command 'create' is deprecated. Please use command 'init' instead!" );
		Hymn_Client::out( "" );
		$data	= array();
		Hymn_Client::out( "Please enter application information:" );

		$title		= Hymn_Client::getInput( "- Application title", "My Project", 'string', NULL, FALSE );
		$uri		= Hymn_Client::getInput( "- Folder Path", getcwd().'/', 'string', NULL, FALSE );
		$protocol	= Hymn_Client::getInput( "- HTTP Protocol", "http://", 'string', NULL, FALSE );
		$host		= Hymn_Client::getInput( "- HTTP Host", "example.com", 'string', NULL, FALSE );
		$path		= Hymn_Client::getInput( "- HTTP Path", "/", 'string', NULL, FALSE );

		$data['application']	= (object) array(
			'title'		=> $title,
			'url'		=> $protocol.$host."/".ltrim( $path, "/"),
			'uri'		=> $uri,
		);

//		$data['library']	= (object) array();														//  disabled in v0.9 @deprecated in v1.0
		$data['sources']	= (object) array();
		$data['modules']	= (object) array();

		Hymn_Client::out( "" );
		Hymn_Client::out( "Please enter database information:" );
		$data['database']	= (object) array(
			'driver'	=> Hymn_Client::getInput( "- PDO Driver", 'string', "mysql", NULL, FALSE ),
			'host'		=> Hymn_Client::getInput( "- Host", 'string', "localhost", NULL, FALSE ),
			'port'		=> Hymn_Client::getInput( "- Port", 'integer', "3306", NULL, FALSE ),
			'username'	=> Hymn_Client::getInput( "- Username", 'string', "my_db_user", NULL, FALSE ),
			'password'	=> Hymn_Client::getInput( "- Password", "my_db_password", 'string', NULL, FALSE ),
			'name'		=> Hymn_Client::getInput( "- Name", 'string', "my_db_name", NULL, FALSE ),
			'prefix'	=> Hymn_Client::getInput( "- Table Prefix", 'string', "", NULL, FALSE ),
		);
		Hymn_Client::out( "" );
		Hymn_Client::out( "Please enter system information:" );
		$data['system']		= (object) array(
			'user'	=> Hymn_Client::getInput( "- System User", 'string', get_current_user(), NULL, FALSE ),
			'group'	=> Hymn_Client::getInput( "- System Group", 'string', "www-data", NULL, FALSE ),
		);
		file_put_contents( Hymn_Client::$fileName, json_encode( $data, JSON_PRETTY_PRINT ) );
		Hymn_Client::out( "Configuration file ".Hymn_Client::$fileName." has been created." );
	}
}
