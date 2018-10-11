<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2018 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Client{

	const FLAG_VERBOSE			= 1;
	const FLAG_QUIET			= 2;
	const FLAG_DRY				= 4;
	const FLAG_FORCE			= 8;
	const FLAG_NO_DB			= 16;
	const FLAG_NO_FILES			= 32;

	const EXIT_ON_END			= 0;
	const EXIT_ON_LOAD			= 1;
	const EXIT_ON_SETUP			= 2;
	const EXIT_ON_RUN			= 4;
	const EXIT_ON_INPUT			= 8;
	const EXIT_ON_EXEC			= 16;
	const EXIT_ON_OUTPUT		= 32;

	protected $application;

	protected $baseArgumentOptions	= array(
		'db'		=> array(
			'pattern'	=> '/^--db=(\S+)$/',
			'resolve'	=> '\\1',
			'values'	=> array( 'yes', 'no', 'only' ),
			'default'	=> 'yes',
		),
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

	static public $fileName	= ".hymn";

	static public $outputMethod	= "print";

	static protected $commandWithoutConfig	= array(
		'default',
		'help',
		'create',																					//  @deprecated
		'init',
		'version',
		'test-syntax',
	);

	static public $pathDefaults	= array(
		'config'		=> 'config/',
		'images'		=> 'images/',
		'locales'		=> 'locales/',
		'scripts'		=> 'scripts/',
		'templates'		=> 'templates/',
		'themes'		=> 'themes/',
	);

	static public $version	= '0.9.7.7b';

	static public $language	= 'en';

	public $arguments;

	protected $config;

	protected $dba;

	protected $dbc;

	protected $instance;

	protected $isLiveCopy	= FALSE;

	public $flags			= 0;

	public $locale;

	public function __construct( $arguments ){
		ini_set( 'display_errors', TRUE );
		error_reporting( E_ALL );

		if( class_exists( 'Locale' ) ){
			$language	= Locale::getPrimaryLanguage( Locale::getDefault() );
			if( in_array( $language, array( 'en', 'de' ) ) )
				self::$language	= $language;
		}
		$this->locale	= new Hymn_Tool_Locale( Hymn_Client::$language );
		$this->words	= $this->locale->loadWords( 'client' );

		if( self::$outputMethod !== "print" )
			ob_start();

		$this->arguments	= new Hymn_Arguments( $arguments, $this->baseArgumentOptions );
		if( $this->arguments->getOption( 'dry' ) )
			$this->flags	|= self::FLAG_DRY;
		if( $this->arguments->getOption( 'force' ) )
			$this->flags	|= self::FLAG_FORCE;
		if( $this->arguments->getOption( 'quiet' ) )
			$this->flags	|= self::FLAG_QUIET;
		if( $this->arguments->getOption( 'verbose' ) )
			$this->flags	|= self::FLAG_VERBOSE;
		if( $this->arguments->getOption( 'force' ) )
			$this->flags	|= self::FLAG_FORCE;
		if( $this->arguments->getOption( 'db' ) === 'no' )
			$this->flags	|= self::FLAG_NO_DB;
		if( $this->arguments->getOption( 'db' ) === 'only' )
			$this->flags	|= self::FLAG_NO_FILES;
		self::$fileName		= $this->arguments->getOption( 'file' );

		try{
			if( getEnv( 'HTTP_HOST' ) )
				throw new RuntimeException( 'Access denied' );
			$action	= $this->arguments->getArgument();
			if( !$action && $this->arguments->getOption( 'help' ) ){
				array_unshift( $arguments, "help" );
				$this->arguments	= new Hymn_Arguments( $arguments, $this->baseArgumentOptions );
			}
			else if( $this->arguments->getOption( 'version' ) ){
				array_unshift( $arguments, "version" );
				$this->arguments	= new Hymn_Arguments( $arguments, $this->baseArgumentOptions );
			}
			$this->dispatch();
		}
		catch( Exception $e ){
			$this->outError( $e->getMessage().".", Hymn_Client::EXIT_ON_SETUP );
		}
		exit( Hymn_Client::EXIT_ON_END );
	}

	protected function dispatch(){
		$calledAction	= trim( $this->arguments->getArgument( 0 ) );								//  get called command
		if( strlen( $calledAction ) ){																//  command string given
			$this->arguments->removeArgument( 0 );													//  remove command from arguments list
			try{
				if( !in_array( $calledAction, self::$commandWithoutConfig ) ){						//  command needs hymn file
					if( $this->flags & self::FLAG_VERBOSE && !( $this->flags & self::FLAG_QUIET ) )	//  verbose mode
						$this->out( 'Reading application configuration ...' );						//  note reading of application configuration
					$this->readConfig();															//  read application configuration from hymn file
				}
				$className			= $this->getCommandClassFromCommand( $calledAction );			//  get command class from called command
				$reflectedClass		= new ReflectionClass( $className );							//  reflect class
				$classInterfaces	= $reflectedClass->getInterfaceNames();							//  get interfaces implemented by class
				if( !$reflectedClass->implementsInterface( 'Hymn_Command_Interface' ) )
					throw new RuntimeException( sprintf(
						$this->words->errorCommandClassNotImplementingInterface,
						$className
					) );
				$commandObject		= $reflectedClass->newInstanceArgs( array( $this ) );			//  create object of reflected class
				$reflectedObject	= new ReflectionObject( $commandObject );						//  reflect object for method call
				$reflectedMethod    = $reflectedObject->getMethod( 'run' );							//  reflect object method "run"
				$reflectedMethod->invokeArgs( $commandObject, (array) $this->arguments );			//  call reflected object method
			}
			catch( Exception $e ){
				$this->outError( $e->getMessage().".", Hymn_Client::EXIT_ON_RUN );
			}
		}
		else																						//  no command string given
			$this->out( $this->locale->loadText( 'command/index' ) );								//  print index text
	}

	/**
	 *	Tries to find and return command class name for a called command.
	 *	@access		protected
	 *	@param		string			$command			Called command
	 *	@return		string								Command class name
	 *	@throws		InvalidArgumentException			if no command class is available for called command
	 */
	protected function getCommandClassFromCommand( $command ){
		if( !strlen( trim( $command ) ) )
			throw new InvalidArgumentException( 'No command given' );
		$commandWords	= ucwords( preg_replace( "/-+/", " ", $command ) );
		$className		= "Hymn_Command_".preg_replace( "/ +/", "_", $commandWords );
		if( !class_exists( $className ) )
			throw new RangeException( sprintf(
				$this->words->errorCommandUnknown,
				$command
			) );
		return $className;
	}

	public function getConfig(){
		if( !$this->config )
			$this->readConfig();
		return $this->config;
	}

	public function getConfigPath(){
		$config	= $this->getConfig();
		if( substr( $config->paths->config, 0, 1 ) === '/' )
			return $config->paths->config;
		return $config->application->uri.$config->paths->config;
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

	public function getInput( $message, $type = 'string', $default = NULL, $options = array(), $break = TRUE ){
		$typeIsBoolean	= in_array( $type, array( 'bool', 'boolean' ) );
		$typeIsInteger	= in_array( $type, array( 'int', 'integer' ) );
		$typeIsNumber	= in_array( $type, array( 'float', 'double', 'decimal' ) );
		if( $typeIsBoolean ){
			$options		= array( 'y', 'n' );
			$defaultIsYes	= in_array( strtolower( $default ), array( 'y', 'yes', '1' ) );
			$default		= $defaultIsYes ? 'yes' : 'no';
		}
		if( strlen( trim( $default ) ) )
			$message	.= " [".$default."]";
		if( is_array( $options ) && count( $options ) )
			$message	.= " (".implode( "|", $options ).")";
		if( !$break )
			$message	.= ": ";
		do{
			$this->out( $message, $break );
			$handle	= fopen( "php://stdin","r" );
			$input	= trim( fgets( $handle ) );
			if( !strlen( $input ) && $default )
				$input	= $default;
		}
		while( $options && is_null( $default ) && !in_array( $input, $options ) );
		if( $typeIsBoolean )
			$input	= in_array( strtolower( $input ), array( 'y', 'yes' ) );
		if( $typeIsInteger )
			$input	= (int) $input;
		if( $typeIsNumber )
			$input	= (float) $input;
		return $input;
	}

	public function getLocale(){
		return $this->locale;
	}

/*	public function getModuleInstallMode( $moduleId, $defaultInstallMode = "dev" ){
		$mode	= $defaultInstallMode;
		if( isset( $this->config->application->{"installMode"} ) )
			$mode	= $this->config->application->{"installMode"};
		return $mode;
	}*/

	public function getModuleInstallType( $moduleId, $defaultInstallType = "copy" ){
		$type	= $defaultInstallType;
		if( isset( $this->config->application->{"installType"} ) )
			$type	= $this->config->application->{"installType"};
		else if( isset( $this->config->modules->{"@installType"} ) )							//  @deprecated: use application->type instead
			$type	= $this->config->modules->{"@installType"};									//  @todo to be removed in 1.0
		else if( isset( $this->config->modules->$moduleId ) )
			if( isset( $this->config->modules->$moduleId->{"installType"} ) )
				$type	= $this->config->modules->$moduleId->{"installType"};
		return $type;
	}

	public function getModuleInstallShelf( $moduleId, $availableShelfIds, $defaultInstallShelfId ){
		if( !array( $availableShelfIds ) )
			throw new InvalidArgumentException( 'Available shelf IDs must be an array' );
		if( !count( $availableShelfIds ) )
			throw new InvalidArgumentException( 'No available shelf IDs given' );

		$modules	= $this->config->modules;													//  shortcut configured modules
		if( isset( $modules->$moduleId ) )														//  module is configured in hymn file
			if( isset( $modules->$moduleId->{"source"} ) )										//  module has configured source shelf
				if( in_array( $modules->$moduleId->{"source"}, $availableShelfIds ) )			//  configured shelf source has requested module
					return $modules->$moduleId->{"source"};										//  return configured source shelf

		if( in_array( $defaultInstallShelfId, $availableShelfIds ) )							//  default shelf has requested module
			return $defaultInstallShelfId;														//  return default shelf

		return current( $availableShelfIds );													//  return first available shelf
	}

	/**
	 *	Prints out message of one ore more lines.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@throws		InvalidArgumentException		if neither array nor string nor NULL given
	 */
	public function out( $lines = NULL, $newLine = TRUE ){
		if( is_null( $lines ) )
			$lines	= array();
		if( !is_array( $lines ) ){
			if( !is_string( $lines ) )
				throw new InvalidArgumentException( 'Argument must be array or string.' );		//  ...
			$lines	= array( $lines );
		}
		foreach( $lines as $line )
			print( $line );
		if( $newLine )
			print( PHP_EOL );
	}

	/**
	 *	Prints out deprecation message of one ore more lines.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@throws		InvalidArgumentException		if neither array nor string given
	 *	@throws		InvalidArgumentException		if given string is empty
	 *	@return		void
	 */
	public function outDeprecation( $lines = array() ){
		if( !is_array( $lines ) ){
			if( !is_string( $lines ) )
				throw new InvalidArgumentException( 'Argument must be array or string.' );		//  ...
			if( !strlen( trim( $lines ) ) )
				throw new InvalidArgumentException( 'Argument must not be empty.' );			//  ...
			$lines	= array( $lines );
		}
		$lines[0]	= $this->words->outPrefixDeprecation.$prefix.$lines[0];
		array_unshift( $lines, '' );
		array_push( $lines, '' );
		$this->out( $lines );
	}

	/**
	 *	Prints out error message.
	 *	@access		public
	 *	@param		string			$message		Error message to print
	 *	@param		integer			$exitCode		Exit with error code, if given, otherwise do not exit (default)
	 *	@return		void
	 */
	public function outError( $message, $exitCode = NULL ){
		$this->out( $this->words->outPrefixError.$message );
		if( is_int( $exitCode ) && $exitCode > Hymn_Client::EXIT_ON_END ){
			if( self::$outputMethod !== "print" && ob_get_level() )
				print( ob_get_clean() );
			exit( $exitCode );
		}
	}

	/**
	 *	Prints out verbose message if verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@return		void
	 */
	public function outVerbose( $lines, $newLine = TRUE ){
		if( $this->flags & self::FLAG_VERBOSE )														//  verbose mode is on
			if( !( $this->flags & self::FLAG_QUIET ) )												//  quiet mode is off
				$this->out( $lines, $newLine );
	}

	protected function readConfig( $forceReload = FALSE ){
		if( $this->config && !$forceReload )
			return;
		if( !file_exists( self::$fileName ) )
			throw new RuntimeException( "File '".self::$fileName."' is missing. Please use command 'init'" );
		$this->config	= json_decode( file_get_contents( self::$fileName ) );
		if( is_null( $this->config ) )
			throw new RuntimeException( 'Configuration file "'.self::$fileName.'" is not valid JSON' );
		if( is_string( $this->config->sources ) ){
			if( !file_exists( $this->config->sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is missing' );
			$sources	= json_decode( file_get_contents( $this->config->sources ) );
			if( is_null( $sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is not valid JSON' );
			$this->config->sources = $sources;
		}
		$app	= $this->config->application;
		if( isset( $app->configPath ) )
			self::$pathDefaults['config'] = $app->configPath;

		$this->config->paths	= (object) array();
		foreach( self::$pathDefaults as $pathKey => $pathValue )
			if( !isset( $this->config->paths->{$pathKey} ) )
				$this->config->paths->{$pathKey}	= $pathValue;

		if( file_exists( $this->config->paths->config.'config.ini' ) ){
			$data	= parse_ini_file( $this->config->paths->config.'config.ini' );
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

		if( isset( $app->installMode ) && isset( $app->installType ) )							//  installation type and mode are set
			$this->isLiveCopy = $app->installMode === "live" && $app->installType === "copy";	//  this installation is a build for a live copy
#		if( $this->isLiveCopy )
#			self::out( "This is a live copy build. Most hymn functions are not available." );
		if( isset( $app->installMode ) && isset( $app->installType ) )							//  installation type and mode are set
			$this->isLiveCopy = $app->installMode === "live" && $app->installType === "copy";	//  this installation is a build for a live copy
	}

	public function setupDatabaseConnection( $force = FALSE, $forceReset = FALSE ){
		if( $this->dbc && !$forceReset ){
			if( $this->flags & self::FLAG_VERBOSE )
				$this->out( "Database already set up." );
			return;
		}
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
			$this->dba->name		= $this->client->getInput( "Database Name:" );
		}
		while( empty( $this->dba->username ) ){
			$this->dba->username	= $this->client->getInput( "Database Username:" );
		}
		while( empty( $this->dba->password ) ){
			$this->dba->password	= $this->client->getInput( "Database Password:" );
		}
		while( is_null( $this->dba->prefix ) ){
			$this->dba->prefix		= $this->client->getInput( "Table Prefix:" );
		}

		if( $this->dba->name && !$usesDatabaseModule ){
			$this->config->modules->Resource_Database	= (object) array();
			$this->config->modules->Resource_Database->config	= (object) array();
			foreach( $this->dba as $key => $value )
				$this->config->modules->Resource_Database->config->{"access.".$key}	= $value;
		}
		if( $this->flags & self::FLAG_NO_DB )
			return;

		if( strtolower( $this->dba->driver ) !== "mysql" )										//  exclude other PDO drivers than 'mysql' @todo improve this until v1.0!
			throw new OutOfRangeException( sprintf(
				'PDO driver "%s" is not supported at the moment',
				$this->dba->driver
			) );
		$dsn			= $this->dba->driver.':'.implode( ";", array(
			"host=".$this->dba->host,
			"port=".$this->dba->port,
//			"dbname=".$this->dba->name,
		) );
		if( $this->flags & self::FLAG_VERBOSE )
			$this->out( "Connecting database ...", FALSE );
		$this->dbc		= new PDO( $dsn, $this->dba->username, $this->dba->password );
		if( $this->flags & self::FLAG_VERBOSE )
			$this->out( "OK" );
		$this->dbc->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		if( !$this->dbc->query( "SHOW DATABASES LIKE '".$this->dba->name."'" )->fetch() ){
			if( $this->flags & self::FLAG_VERBOSE )
				$this->out( 'Creating database "'.$this->dba->name.'" ...', FALSE );
			$this->dbc->query( "CREATE DATABASE `".$this->dba->name."`" );
			if( $this->flags & self::FLAG_VERBOSE )
				$this->out( "OK" );
		}
		if( $this->dbc->query( "SHOW DATABASES LIKE '".$this->dba->name."'" )->fetch() ){
			if( $this->flags & self::FLAG_VERBOSE )
				$this->out( 'Switching into database "'.$this->dba->name.'" ...', FALSE );
			$this->dbc->query( "USE `".$this->dba->name."`" );
			if( $this->flags & self::FLAG_VERBOSE )
				$this->out( "OK" );
		}
	}
}
?>
