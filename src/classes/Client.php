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
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Client{

	protected $application;

	public $arguments;

	protected $config;

	protected $dba;

	protected $dbc;

	protected $instance;

	static public $fileName	= ".hymn";

	static public $pathDefaults	= array(
		'images'		=> 'images/',
		'locales'		=> 'locales/',
		'scripts'		=> 'scripts/',
		'templates'		=> 'templates/',
		'themes'		=> 'themes/',
	);

	static public $version	= "0.8.8";


	protected $baseArgumentOptions	= array(
		'dry'		=> array(
			'pattern'	=> '/^-d|--dry/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		),
		'file'		=> array(
			'pattern'	=> '/^--file=(\S+)$/',
			'resolve'	=> '\\1',
			'default'	=> '.hymn',
		),
		'force'		=> array(
			'pattern'	=> '/^-f|--force$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		),
		'help'		=> array(
			'pattern'	=> '/^-h|--help/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		),
		'prefix'		=> array(
			'pattern'	=> '/^--prefix=(\S*)$/',
			'resolve'	=> '\\1',
			'default'	=> NULL,
		),
		'quiet'		=> array(
			'pattern'	=> '/^-q|--quiet$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		),
		'verbose'	=> array(
			'pattern'	=> '/^-v|--verbose$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		),
		'version'	=> array(
			'pattern'	=> '/^--version/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		)
	);

	public function __construct( $arguments ){
		ini_set( 'display_errors', TRUE );
		error_reporting( E_ALL );

		$this->arguments	= new Hymn_Arguments( $arguments, $this->baseArgumentOptions );
		self::$fileName		= $this->arguments->getOption( 'file' );

		try{
			if( getEnv( 'HTTP_HOST' ) )
				throw new RuntimeException( 'Access denied' );
			$action	= $this->arguments->getArgument();
			if( $this->arguments->getOption( 'help' ) ){
				array_unshift( $arguments, "help" );
				$this->arguments	= new Hymn_Arguments( $arguments, $this->baseArgumentOptions );
			}
			else if( $this->arguments->getOption( 'version' ) ){
				array_unshift( $arguments, "version" );
				$this->arguments	= new Hymn_Arguments( $arguments, $this->baseArgumentOptions );
			}
			if( !in_array( $action, array( 'help', 'create', 'version' ) ) ){
				$this->readConfig();
				$this->loadLibraries();
//				$this->setupDatabaseConnection();
			}
			$this->dispatch();
//			self::out();
		}
		catch( Exception $e ){
			self::out( "Error: ".$e->getMessage() );
		}
	}

	protected function dispatch(){
		$action		= $this->arguments->getArgument( 0 );
		$className	= "Hymn_Command_Default";
		if( strlen( $action ) ){
			$command		= ucwords( preg_replace( "/-+/", " ", $action ) );
			$className	= "Hymn_Command_".preg_replace( "/ +/", "_", $command );
			if( !class_exists( $className ) )
				throw new InvalidArgumentException( 'Invalid action: '.$action );
		}
//		self::out( "Command Class: ".$className );
		try{
			$this->arguments->removeArgument( 0 );
			$object		= new $className( $this );
			$object->run( $this->arguments );
		}
		catch( Exception $e ){
			Hymn_Client::out( $e->getMessage() );
			exit;
		}
	}

	public function getConfig(){
		return $this->config;
	}

	public function getDatabase(){
		return $this->dbc;
	}

	public function getDatabaseConfiguration( $key = NULL ){
		if( is_null( $key ) )
			return $this->dba;
		if( isset( $this->dba->$key ) )
			return $this->dba->$key;
		else
			throw new InvalidArgumentException( 'Invalid database access property key "'.$key.'"' );
	}

	static public function getInput( $message, $default = NULL, $options = array(), $break = TRUE ){
		if( strlen( trim( $default ) ) )
			$message	.= " [".$default."]";
		if( is_array( $options ) && count( $options ) )
			$message	.= " (".implode( "|", $options ).")";
		if( !$break )
			$message	.= ": ";
		do{
			Hymn_Client::out( $message, $break );
			$handle	= fopen( "php://stdin","r" );
			$line		= trim( fgets( $handle ) );
			if( !strlen( $line ) && $default )
				$line	= $default;
		}
		while( $options && !in_array( $line, $options ) );
		return $line;
	}

	public function getModuleConfiguration( $moduleId, $key = NULL, $force = FALSE ){
		if( !isset( $this->config->modules->$moduleId ) ){
			if( $force )
				throw new RuntimeException( 'Configuration of module "'.$moduleId.'" is needed but missing' );
			return (object) array();
		}
		$config	= $this->config->modules->{$moduleId}->config;
		if( !is_null( $key ) ){
			if( isset( $config->{$key} ) )
				return $config->{$key};
			if( $force )
				throw new RuntimeException( 'Configuration of module "'.$moduleId.'" has no property "'.$key.'"' );
			return NULL;
		}
		return $config;
	}

	public function getModuleInstallType( $moduleId, $defaultInstallType = "copy" ){
		$type	= $defaultInstallType;
		if( isset( $this->config->modules->{"@installType"} ) )
			$type	= $this->config->modules->{"@installType"};
		if( isset( $this->config->modules->$moduleId ) )
			if( isset( $this->config->modules->$moduleId->{"installType"} ) )
				$type	= $this->config->modules->$moduleId->{"installType"};
		return $type;
	}

	protected function loadLibraries(){
		foreach( $this->config->library as $library ){
			if( !@include_once $library.'autoload.php5' )
				throw new RuntimeException( 'Missing cmClasses in "'.$this->config->library->cmClasses.'"' );

		}
//		if( !@include_once $this->config->library->cmClasses.'autoload.php5' )
//			throw new RuntimeException( 'Missing cmClasses in "'.$this->config->library->cmClasses.'"' );
//		if( !@include_once $this->config->library->cmFrameworks.'autoload.php5' )
//			throw new RuntimeException( 'Missing cmFrameworks in "'.$this->config->library->cmFrameworks.'"' );
	}

	static public function out( $messages = NULL, $newLine = TRUE ){
		if( !is_array( $messages ) )
			$messages	= array( $messages );
		foreach( $messages as $message )
			print( $message );
		if( $newLine )
			print( PHP_EOL );
	}

	protected function readConfig( $filename = NULL ){
		$filename	= $filename ? $filename : self::$fileName;
		if( !file_exists( $filename ) )
			throw new RuntimeException( 'File "'.$filename.'" is missing' );
		$this->config	= json_decode( file_get_contents( $filename ) );
		if( is_null( $this->config ) )
			throw new RuntimeException( 'Configuration file "'.$filename.'" is not valid JSON' );
		if( is_string( $this->config->sources ) ){
			if( !file_exists( $this->config->sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is missing' );
			$sources	= json_decode( file_get_contents( $this->config->sources ) );
			if( is_null( $sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is not valid JSON' );
			$this->config->sources = $sources;
		}
		$this->config->paths	= (object) array();
		foreach( self::$pathDefaults as $pathKey => $pathValue )
			if( !isset( $this->config->paths->{$pathKey} ) )
				$this->config->paths->{$pathKey}	= $pathValue;

		if( file_exists( 'config/config.ini' ) ){
			$data	= parse_ini_file( 'config/config.ini' );
			foreach( $data as $key => $value ){
				if( preg_match( "/^path\./", $key ) ){
					$key	= preg_replace( "/^path\./", "", $key );
					$key	= ucwords( str_replace( ".", " ", $key ) );
					$key	= str_replace( " ", "", lcfirst( $key ) );
					$this->config->paths->{$key}	= $value;
				}
				else{
					$key	= ucwords( str_replace( ".", " ", $key ) );
					$key	= str_replace( " ", "", lcfirst( $key ) );
					$this->config->{$key}	= $value;
				}
			}
		}
	}

	public function setupDatabaseConnection( $force = FALSE ){
		if( $this->dbc )
			return;
//		$this->dbc			= NULL;
		$usesGlobalDbAccess	= isset( $this->config->database ) && $this->config->database;
		$usesDatabaseModule	= isset( $this->config->modules->Resource_Database->config );
		if( $usesGlobalDbAccess ){
			$this->dba		= $this->config->database;
		}
		else if( $usesDatabaseModule ){
			$config		= array();
			foreach( $this->config->modules->Resource_Database->config as $key => $value )
				$config[preg_replace("/^access\./", "", $key)]	= $value;
			$this->dba	= (object) $config;
		}

		if( empty( $this->dba ) ){
			if( $force )
				throw new RuntimeException( 'Database access needed but not configured' );
			return;
		}
		$this->dba->driver		= isset( $this->dba->driver ) ? $this->dba->driver : "mysql";
		$this->dba->host		= isset( $this->dba->host ) ? $this->dba->host : "localhost";
		$this->dba->port		= isset( $this->dba->port ) ? $this->dba->port : "3306";
		$this->dba->name		= isset( $this->dba->name ) ? $this->dba->name : NULL;
		$this->dba->prefix		= isset( $this->dba->prefix ) ? $this->dba->prefix : NULL;
		$this->dba->username	= isset( $this->dba->username ) ? $this->dba->username : NULL;
		$this->dba->password	= isset( $this->dba->password ) ? $this->dba->password : NULL;

		if( !in_array( $this->dba->driver, PDO::getAvailableDrivers() ) ){
			throw new RuntimeException( 'PDO driver "'.$this->dba->driver.'" is not available' );
		}
		while( empty( $this->dba->name ) ){
			$this->dba->name		= Hymn_Client::getInput( "Database Name:" );
		}
		while( empty( $this->dba->username ) ){
			$this->dba->username	= Hymn_Client::getInput( "Database Username:" );
		}
		while( empty( $this->dba->password ) ){
			$this->dba->password	= Hymn_Client::getInput( "Database Password:" );
		}
		while( is_null( $this->dba->prefix ) ){
			$this->dba->prefix		= Hymn_Client::getInput( "Table Prefix:" );
		}
		$dsn			= $this->dba->driver.":host=".$this->dba->host.";port=".$this->dba->port.";dbname=".$this->dba->name;

		$this->dbc		= new PDO( $dsn, $this->dba->username, $this->dba->password );
		$this->dbc->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		if( !isset( $this->config->modules->Resource_Database ) )
			$this->config->modules->Resource_Database	= (object) array();
		$this->config->modules->Resource_Database->config	= (object) array();
		foreach( $this->dba as $key => $value )
			$this->config->modules->Resource_Database->config->{"access.".$key}	= $value;
	}
}
?>
