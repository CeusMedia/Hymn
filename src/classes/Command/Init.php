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

	protected $answers	= array();
	protected $pathPhar	= "phar://hymn.phar/";

	protected function answer( $key, $message, $type = 'string', $default = NULL, $options = array(), $break = TRUE ){
		$question	= new Hymn_Tool_Question( $message, $type, $default, $options, $break );
		$this->answers[$key]	= $question->ask();
	}

	protected function configureDatabase(){
		Hymn_Client::out( "Please enter database information:" );
		$this->answer( 'database.driver', "- PDO Driver", 'string', "mysql", PDO::getAvailableDrivers(), FALSE );
		$this->answer( 'database.host', "- Host", 'string', "localhost", NULL, FALSE );
		$this->answer( 'database.port', "- Port", 'string', "3306", NULL, FALSE );
		$this->answer( 'database.username', "- Username", 'string', NULL, NULL, FALSE );
		$this->answer( 'database.password', "- Password", 'string', NULL, NULL, FALSE );
		$this->answer( 'database.name', "- Database Name", 'string', NULL, NULL, FALSE );
		$this->answer( 'database.prefix', "- Table Prefix", 'string', "", NULL, FALSE );
	}

	protected function createComposerFile(){
		Hymn_Client::out( "Please enter more Application information:" );
		$this->answer( 'app.author.name', "- Author Name", 'string', "John Doe", NULL, FALSE );
		$this->answer( 'app.author.email', "- Author Email", 'string', "<john.doe@example.com>", NULL, FALSE );
		$command	= 'composer init --name %s --author "%s %s"';
		$command	= sprintf( $command,
			escapeshellarg( $this->answers['app.package'] ),
			$this->answers['app.author.name'],
			$this->answers['app.author.email']
		);
		exec( $command );
		Hymn_Client::out( "Composer file has been created." );
	}

	protected function createConfigFile(){
		Hymn_Client::out( "Please enter more Application information:" );
		$this->answer( 'app.version', "- Version", 'string', "0.1", NULL, FALSE );
		$this->answer( 'app.language', "- Language", 'string', "de", array( 'en', 'de' ), FALSE );
		$useModuleCache	= in_array( $this->answers['app.install.mode'], array( 'live' ) );
		mkdir( 'config' );
		copy( $this->pathPhar."templates/config/.htaccess", 'config/.htaccess' );
		copy( $this->pathPhar."templates/config/config.ini", 'config/config.ini' );
		$content	= file_get_contents( 'config/config.ini' );
		$content	= str_replace( "%appTitle%", $this->answers['app.title'], $content );
		$content	= str_replace( "%appVersion%", $this->answers['app.version'], $content );
		$content	= str_replace( "%appUrl%", $this->answers['app.url'], $content );
		$content	= str_replace( "%localeAllowed%", $this->answers['app.language'], $content );
		$content	= str_replace( "%localeDefault%", $this->answers['app.language'], $content );
		$content	= str_replace( "%systemModuleCache%", $useModuleCache ? 'yes' : 'no', $content );
		file_put_contents( 'config/config.ini', $content );
		Hymn_Client::out( "Config file has been created." );
	}

	protected function createMakeFile(){
		copy( $this->pathPhar."templates/Makefile", 'Makefile' );
		$content	= file_get_contents( 'Makefile' );
		$content	= str_replace( "%appName%", $this->answers['app.name'], $content );
		$content	= str_replace( "%appVersion%", $this->answers['app.version'], $content );
		file_put_contents( 'Makefile', $content );
		Hymn_Client::out( "Make file has been created." );
	}

	protected function createUnitFile(){
		$pathSource	= $this->ask( "- Folder with test classes", 'string', "test", NULL, FALSE );
		$pathTarget	= $this->ask( "- Path for test results", 'string', "doc/Test", NULL, FALSE );
		$pathSource	= rtrim( trim( $pathSource ), '/' );
		$bootstrap	= $this->ask( "- Bootstrap file", 'string', "bootstrap.php", NULL, FALSE );
		Hymn_Module_Files::createPath( $pathSource );
		copy( $this->pathPhar."templates/test/bootstrap.php", $pathSource.'/bootstrap.php' );
		copy( $this->pathPhar."templates/phpunit.xml", 'phpunit.xml' );
		$content	= file_get_contents( 'phpunit.xml' );
		$content	= str_replace( "%appKey%", $this->answers['app.key'], $content );
		$content	= str_replace( "%pathSource%", $pathSource, $content );
		$content	= str_replace( "%pathTarget%", $pathTarget, $content );
		file_put_contents( 'phpunit.xml', $content );
		Hymn_Client::out( "Configuration file for PHPUnit has been created." );
		Hymn_Client::out( "Empty bootstrap file for PHPUnit test classes has been created." );
	}

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){

		/*  --  CREATE HYMN FILE  --  */
		Hymn_Client::out( "Please enter application information:" );
		$this->answer( 'app.title', "- Application Title", 'string', "My Project", NULL, FALSE );
		$this->answer( 'app.key', "- Application Key Name", 'string', "MyCompany/MyProject", NULL, FALSE );
		$this->answer( 'app.uri', "- Installation Path", 'string', getcwd().'/', NULL, FALSE );
		$this->answer( 'app.url.protocol', "- HTTP Protocol", 'string', "http://", NULL, FALSE );
		$this->answer( 'app.url.host', "- HTTP Host", 'string', "example.com", NULL, FALSE );
		$this->answer( 'app.url.path', "- HTTP Path", 'string', "/", NULL, FALSE );
		$this->answer( 'app.install.type', "- Installation Type", 'string', "link", array( 'copy', 'link' ), FALSE );
		$this->answer( 'app.install.mode', "- Installation Mode", 'string', "dev", array( 'dev', 'live', 'test' ), FALSE );
		$this->answers['app.url']	= join( array(
			$this->answers['app.url.protocol'],
			$this->answers['app.url.host'],
			"/".ltrim( $this->answers['app.url.path'], "/" ),
		) );
		$this->answers['app.name']	= join( '', explode( ' ', $this->answers['app.title'] ) );
		$package	= strtolower( preg_replace( '/([A-Z])/', '-\\1', $this->answers['app.key'] ) );
		$this->answers['app.package']	= ltrim( str_replace( '/-', '/', $package ), '-' );

		Hymn_Client::out( "" );
		Hymn_Client::out( "Please enter Filesystem information:" );
		$this->answer( 'system.user', "- System User", 'string', get_current_user(), NULL, FALSE );
		$this->answer( 'system.group', "- System Group", 'string', "www-data", NULL, FALSE );
		Hymn_Client::out( "" );
		if( $this->ask( "Configure database?", 'boolean', "yes", NULL, FALSE ) )
			$this->configureDatabase();

		$data	= array(
			'application'	=> (object) array(
				'title'			=> $this->answers['app.title'],
				'url'			=> $this->answers['app.url'],
				'uri'			=> $this->answers['app.uri'],
				'installType'	=> $this->answers['app.install.type'],
				'installMode'	=> $this->answers['app.install.mode'],
			),
			'sources'		=> (object) array(),
			'modules'		=> (object) array(),
			'database'		=> (object) array(),
			'system'		=> (object) array(
				'user'			=> $this->answers['system.user'],
				'group'			=> $this->answers['system.group'],
			),
		);
		if( isset( $this->answers['database.driver'] ) ){
			$data['database']	= (object) array(
				'driver'	=> $this->answers['database.driver'],
				'host'		=> $this->answers['database.host'],
				'port'		=> $this->answers['database.port'],
				'username'	=> $this->answers['database.username'],
				'password'	=> $this->answers['database.password'],
				'name'		=> $this->answers['database.name'],
				'prefix'	=> $this->answers['database.prefix'],
			);

		}
		file_put_contents( Hymn_Client::$fileName, json_encode( $data, JSON_PRETTY_PRINT ) );
		Hymn_Client::out( "Hymn configuration file ".Hymn_Client::$fileName." has been created." );
		Hymn_Client::out( "" );

		/*  --  CREATE PHPUNIT FILE  --  */
		if( $this->ask( "Configure PHPUnit?", 'boolean', "yes", NULL, FALSE ) ){
			$this->createUnitFile();
			Hymn_Client::out( "" );
		}

		/*  --  CREATE COMPOSER FILE  --  */
		if( $this->ask( "Configure composer?", 'boolean', "yes", NULL, FALSE ) ){
			$this->createComposerFile();
			Hymn_Client::out( "" );																//  print empty line as optical separator
		}

		/*  --  CREATE APP BASE CONFIG FILE  --  */
		if( $this->ask( "Create base config file?", 'boolean', "yes", NULL, FALSE ) ){
			$this->createConfigFile();
			Hymn_Client::out( "" );																//  print empty line as optical separator
		}

		/*  --  CREATE MAKE FILE  --  */
		if( $this->ask( "Create make file?", 'boolean', "yes", NULL, FALSE ) ){
			$this->createMakeFile();
			Hymn_Client::out( "" );																//  print empty line as optical separator
		}

		Hymn_Client::out( "Done." );
		Hymn_Client::out( "Now you can execute commands like install module sources using 'hymn source-add'." );
		Hymn_Client::out( "" );																	//  print empty line as optical separator
	}
}
