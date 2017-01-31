<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Init extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$data	= array();

		/*  --  CREATE HYMN FILE  --  */
		Hymn_Client::out( "Please enter application information:" );
		$title		= $this->ask( "- Application title", 'string', "My Project", NULL, FALSE );
		$uri		= $this->ask( "- Installation Path", 'string', getcwd().'/', NULL, FALSE );
		$protocol	= $this->ask( "- HTTP Protocol", 'string', "http://", NULL, FALSE );
		$host		= $this->ask( "- HTTP Host", 'string', "example.com", NULL, FALSE );
		$path		= $this->ask( "- HTTP Path", 'string', "/", NULL, FALSE );
		$type		= $this->ask( "- Installation Type", 'string', "link", array( 'copy', 'link' ), FALSE );
		$mode		= $this->ask( "- Installation Mode", 'string', "dev", array( 'dev', 'live', 'test' ), FALSE );

		$data['application']	= (object) array(
			'title'		=> $title,
			'url'		=> $protocol.$host."/".ltrim( $path, "/"),
			'uri'		=> $uri,
			'installType'	=> $type,
			'installMode'	=> $mode,
		);

//		$data['library']	= (object) array();
		$data['sources']	= (object) array();
		$data['modules']	= (object) array();

		Hymn_Client::out( "" );
		Hymn_Client::out( "Please enter system information:" );
		$data['system']		= (object) array(
			'user'	=> $this->ask( "- System User", 'string', get_current_user(), NULL, FALSE ),
			'group'	=> $this->ask( "- System Group", 'string', "www-data", NULL, FALSE ),
		);
		$appKey	= $this->ask( "- Application Key Name", 'string', "MyCompany/MyApp", NULL, FALSE );
		Hymn_Client::out( "" );
		$initDatabase	= $this->ask( "Configure database?", 'boolean', "yes", NULL, FALSE );
		if( $initDatabase ){
			Hymn_Client::out( "Please enter database information:" );
			$data['database']	= (object) array(
				'driver'	=> $this->ask( "- PDO Driver", 'string', "mysql", PDO::getAvailableDrivers(), FALSE ),
				'host'		=> $this->ask( "- Host", 'string', "localhost", NULL, FALSE ),
				'port'		=> $this->ask( "- Port", 'string', "3306", NULL, FALSE ),
				'username'	=> $this->ask( "- Username", 'string', NULL, NULL, FALSE ),
				'password'	=> $this->ask( "- Password", 'string', NULL, NULL, FALSE ),
				'name'		=> $this->ask( "- Database Name", 'string', NULL, NULL, FALSE ),
				'prefix'	=> $this->ask( "- Table Prefix", 'string', "", NULL, FALSE ),
			);
		}
		file_put_contents( Hymn_Client::$fileName, json_encode( $data, JSON_PRETTY_PRINT ) );
		Hymn_Client::out( "Hymn configuration file ".Hymn_Client::$fileName." has been created." );
		Hymn_Client::out( "" );

		/*  --  CREATE PHPUNIT FILE  --  */
		$initPhpunit	= $this->ask( "Configure PHPUnit?", 'boolean', "yes", NULL, FALSE );
		if( $initPhpunit ){
			$pathSource	= $this->ask( "- Folder with test classes", 'string', "test", NULL, FALSE );
			$pathTarget	= $this->ask( "- Path for test results", 'string', "doc/Test", NULL, FALSE );
			$pathSource	= rtrim( trim( $pathSource ), '/' );
			$bootstrap	= $this->ask( "- Bootstrap file", 'string', "bootstrap.php", NULL, FALSE );
			$pathPhar	= "phar://hymn.phar/";
			Hymn_Module_Files::createPath( $pathSource );
			copy( $pathPhar."templates/test/bootstrap.php", $pathSource.'/bootstrap.php' );
			copy( $pathPhar."templates/phpunit.xml", 'phpunit.xml' );
			$content	= file_get_contents( 'phpunit.xml' );
			$content	= str_replace( "%appKey%", $appKey, $content );
			$content	= str_replace( "%pathSource%", $pathSource, $content );
			$content	= str_replace( "%pathTarget%", $pathTarget, $content );
			file_put_contents( 'phpunit.xml', $content );
			Hymn_Client::out( "Configuration file for PHPUnit has been created." );
			Hymn_Client::out( "Empty bootstrap file for PHPUnit test classes has been created." );
		}
		Hymn_Client::out( "" );

		/*  --  CREATE COMPOSER FILE  --  */
		if( $this->ask( "Configure composer?", 'boolean', "yes", NULL, FALSE ) ){
			$command	= "composer --name %s --author %";
			$command	= sprintf( $command, $appKey, '' );
			exec( $command );
			Hymn_Client::out( "Composer file has been created." );
		}
		Hymn_Client::out( "" );																		//  print empty line as optical separator

		/*  --  CREATE MAKE FILE  --  */
		if( $this->ask( "Create make file?", 'boolean', "yes", NULL, FALSE ) ){
			copy( $pathPhar."templates/Makefile", 'Makefile' );
			Hymn_Client::out( "Make file has been created." );
		}
		Hymn_Client::out( "" );																		//  print empty line as optical separator

		/*  --  CREATE APP BASE CONFIG FILE  --  */
		if( $this->ask( "Create base config file?", 'boolean', "yes", NULL, FALSE ) ){
			mkdir( 'config' );
			copy( $pathPhar."templates/config/config.ini", 'config/config.ini' );
			copy( $pathPhar."templates/config/.htaccess", 'config/.htaccess' );
			Hymn_Client::out( "Make file has been created." );
		}
		Hymn_Client::out( "" );																		//  print empty line as optical separator

		Hymn_Client::out( "Done." );
		Hymn_Client::out( "Now you can execute commands like install module sources using 'hymn source-add'." );
		Hymn_Client::out( "" );																		//  print empty line as optical separator
	}
}
