<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2019 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Client{

	const FLAG_VERY_VERBOSE		= 1;
	const FLAG_VERBOSE			= 2;
	const FLAG_QUIET			= 4;
	const FLAG_DRY				= 8;
	const FLAG_FORCE			= 16;
	const FLAG_NO_DB			= 32;
	const FLAG_NO_FILES			= 64;
	const FLAG_NO_INTERACTION	= 128;

	const EXIT_ON_END			= 0;
	const EXIT_ON_LOAD			= 1;
	const EXIT_ON_SETUP			= 2;
	const EXIT_ON_RUN			= 4;
	const EXIT_ON_INPUT			= 8;
	const EXIT_ON_EXEC			= 16;
	const EXIT_ON_OUTPUT		= 32;

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
			'excludes'	=> 'verbose',
		),
		'verbose'	=> array(
			'pattern'	=> '/^-v|--verbose$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		),
		'very-verbose'	=> array(
			'pattern'	=> '/^-vv|--very-verbose$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
			'includes'	=> 'verbose',
		),
		'version'	=> array(
			'pattern'	=> '/^--version/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		),
		'interactive'	=> array(
			'pattern'	=> '/^--interactive=(\S+)$/',
			'resolve'	=> '\\1',
			'values'	=> array( 'yes', 'no' ),
			'default'	=> 'yes',
		),
		'comment'	=> array(
			'pattern'	=> '/^--comment=(\S+)$/',
			'resolve'	=> '\\1',
			'default'	=> NULL,
		)
	);

	static public $fileName			= '.hymn';

	static public $outputMethod		= 'print';

	static protected $commandWithoutConfig	= array(
		//  APP CREATION
		'init',
		//  SELF MANAGEMENT
		'help',
		'self-update',
		'test-syntax',
		'version',
	);

	static public $pathDefaults	= array(
		'config'		=> 'config/',
		'classes'		=> 'classes/',
		'images'		=> 'images/',
		'locales'		=> 'locales/',
		'scripts'		=> 'scripts/',
		'templates'		=> 'templates/',
		'themes'		=> 'themes/',
	);

	static public $language			= 'en';

	static public $version			= '0.9.9.2b';

	public $arguments;

	public $flags					= 0;

	public $locale;

	protected $config;

	protected $database;

	protected $framework;

	protected $words;

	protected $isLiveCopy			= FALSE;

	protected $originalArguments	= array();

	protected $output;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		array			$arguments		Map of CLI arguments
	 *	@param		boolean			$exit			Flag: exit execution afterwards (default: yes)
	 *	@return		void
	 *	@throws		RuntimeException				if trying to run via web server
	 */
	public function __construct( $arguments, $exit = TRUE ){
		$this->exit	= $exit;
		$this->originalArguments	= $arguments;
		ini_set( 'display_errors', TRUE );
		error_reporting( E_ALL );

		if( class_exists( 'Locale' ) ){
			$language	= Locale::getPrimaryLanguage( Locale::getDefault() );
			if( in_array( $language, array( 'en', 'de' ) ) )
				self::$language	= $language;
		}
		$this->database		= new Hymn_Tool_Database_PDO( $this );
		$this->locale		= new Hymn_Tool_Locale( Hymn_Client::$language );
		$this->words		= $this->locale->loadWords( 'client' );

		if( self::$outputMethod !== 'print' )
			ob_start();

		$this->arguments	= new Hymn_Tool_CLI_Arguments( $arguments, $this->baseArgumentOptions );
		if( $this->arguments->getOption( 'dry' ) )
			$this->flags	|= self::FLAG_DRY;
		if( $this->arguments->getOption( 'force' ) )
			$this->flags	|= self::FLAG_FORCE;
		if( $this->arguments->getOption( 'force' ) )
			$this->flags	|= self::FLAG_FORCE;
		if( $this->arguments->getOption( 'db' ) === 'no' )
			$this->flags	|= self::FLAG_NO_DB;
		if( $this->arguments->getOption( 'db' ) === 'only' )
			$this->flags	|= self::FLAG_NO_FILES;
		if( $this->arguments->getOption( 'quiet' ) )
			$this->flags	|= self::FLAG_QUIET;
		if( $this->arguments->getOption( 'verbose' ) ){
			$this->flags	|= self::FLAG_VERBOSE;
			$this->flags	|= self::FLAG_NO_INTERACTION;
		}
		if( $this->arguments->getOption( 'very-verbose' ) ){
			$this->flags	|= self::FLAG_VERBOSE;
			$this->flags	|= self::FLAG_VERY_VERBOSE;
		}
		if( $this->arguments->getOption( 'interactive' ) === 'no' )
			$this->flags	|= self::FLAG_NO_INTERACTION;
		self::$fileName		= $this->arguments->getOption( 'file' );
		$this->output		= new Hymn_Tool_CLI_Output( $this, $exit );

		try{
			if( getEnv( 'HTTP_HOST' ) )
				throw new RuntimeException( 'Access denied' );
			$action	= $this->arguments->getArgument();
			if( !$action && $this->arguments->getOption( 'help' ) ){
				array_unshift( $arguments, 'help' );
				$this->arguments	= new Hymn_Tool_CLI_Arguments( $arguments, $this->baseArgumentOptions );
			}
			else if( $this->arguments->getOption( 'version' ) ){
				array_unshift( $arguments, 'version' );
				$this->arguments	= new Hymn_Tool_CLI_Arguments( $arguments, $this->baseArgumentOptions );
			}
			$this->dispatch();
		}
		catch( Exception $e ){
			$this->outError( $e->getMessage().'.', Hymn_Client::EXIT_ON_SETUP );
		}
		if( $this->exit )
			exit( Hymn_Client::EXIT_ON_END );
	}

	public function getFramework(){
		if( !$this->framework )
			$this->framework	= new Hymn_Tool_Framework();
		return $this->framework;
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
		return $this->database;
	}

	public function getLocale(){
		return $this->locale;
	}

/*	public function getModuleInstallMode( $moduleId, $defaultInstallMode = 'dev' ){
		$mode	= $defaultInstallMode;
		if( isset( $this->config->application->{'installMode'} ) )
			$mode	= $this->config->application->{'installMode'};
		return $mode;
	}*/

	public function getModuleInstallType( $moduleId, $defaultInstallType = 'copy' ){
		$type	= $defaultInstallType;
		if( isset( $this->config->application->{'installType'} ) )
			$type	= $this->config->application->{'installType'};
		else if( isset( $this->config->modules->{'@installType'} ) )								//  @deprecated: use application->type instead
			$type	= $this->config->modules->{'@installType'};										//  @todo to be removed in 1.0
		else if( isset( $this->config->modules->$moduleId ) )
			if( isset( $this->config->modules->$moduleId->{'installType'} ) )
				$type	= $this->config->modules->$moduleId->{'installType'};
		return $type;
	}

	public function getModuleInstallShelf( $moduleId, $availableShelfIds, $defaultInstallShelfId ){
		if( !array( $availableShelfIds ) )
			throw new InvalidArgumentException( 'Available source IDs must be an array' );
		if( !count( $availableShelfIds ) )
			throw new InvalidArgumentException( 'No available source IDs given' );

		$modules	= $this->config->modules;														//  shortcut configured modules
		if( isset( $modules->$moduleId ) )															//  module is configured in hymn file
			if( isset( $modules->$moduleId->source ) )											//  module has configured source shelf
				if( in_array( $modules->$moduleId->source, $availableShelfIds ) )				//  configured shelf source has requested module
					return $modules->$moduleId->source;											//  return configured source shelf

		if( in_array( $defaultInstallShelfId, $availableShelfIds ) )								//  default shelf has requested module
			return $defaultInstallShelfId;															//  return default shelf

		return current( $availableShelfIds );														//  return first available shelf
	}

	/**
	 *	Prints out message of one ore more lines.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@throws		InvalidArgumentException		if neither array nor string nor NULL given
	 */
	public function out( $lines = NULL, $newLine = TRUE ){
		return $this->output->out( $lines, $newLine );
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
		return $this->output->outDeprecation( $lines );
	}

	/**
	 *	Prints out error message.
	 *	@access		public
	 *	@param		string			$message		Error message to print
	 *	@param		integer			$exitCode		Exit with error code, if given, otherwise do not exit (default)
	 *	@return		void
	 */
	public function outError( $message, $exitCode = NULL ){
		return $this->output->outError( $message, $exitCode );
	}

	/**
	 *	Prints out verbose message if verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@return		void
	 */
	public function outVerbose( $lines, $newLine = TRUE ){
		return $this->output->outVerbose( $lines, $newLine );
	}

	/**
	 *	Prints out verbose message if very verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@return		void
	 */
	public function outVeryVerbose( $lines, $newLine = TRUE ){
		return $this->output->outVeryVerbose( $lines, $newLine );
	}

	public function runCommand( $command, $arguments = array(), $addOptions = array(), $ignoreOptions = array() ){
		$args	= array( $command );
		foreach( $arguments as $argument )
			$args[]	= $argument;

		foreach( $this->arguments->getOptions() as $key => $value ){
			if( !strlen( $value ) || !array_key_exists( $key, $this->baseArgumentOptions ) )
				continue;
			if( in_array( $key, $ignoreOptions ) )
				continue;
			if( $this->baseArgumentOptions[$key]['resolve'] === TRUE )
				$args[]	= '--'.$key;
			else
				$args[]	= '--'.$key.'='.$value;
		}
		foreach( $addOptions as $key => $value ){
			if( array_key_exists( $key, $args ) )
				continue;
			if( !strlen( $value ) || !array_key_exists( $key, $this->baseArgumentOptions ) )
				continue;
			if( $this->baseArgumentOptions[$key]['resolve'] === TRUE )
				$args[]	= '--'.$key;
			else
				$args[]	= '--'.$key.'='.$value;
		}
		$this->outVeryVerbose( 'Running sub command: '.join( ' ', $args ) );
		$client = new Hymn_Client( $args, FALSE );
	}

	/*  --  PROTECTED  --  */

	/**
	 *	Copies database access information into database related resource modules.
	 *	Such modules can be registered in hymn file in 'database.modules' as string separated list of resource modules and option key prefixes (optional).
	 *	Example: Resource_Database:access.,MyDatabaseResourceModule:myOptionPrefix.
	 *	Hint: Hence the tailing dot.
	 *	Default module to use is Resource_Database, if no other definition has been found.
	 *
	 *	@access		protected
	 *	@param		array			$modules		Map of resource modules with config option key prefix (eG. Resource_Database:access.).
	 *	@todo		kriss: Question is: Why? On which purpose is this important, again?
	 */
	protected function applyAppConfiguredDatabaseConfigToModules( $modules = array() ){
		if( !isset( $this->config->database ) )
			return FALSE;

		if( !$modules ){																			//  no map of resource modules with config option key prefix given on function call
			$modules	= array();																	//  set empty map
			if( !isset( $this->config->database->modules ) )										//  no database resource modules defined in hymn file (default)
				$this->config->database->modules	= 'Resource_Database:access.';					//  set atleast pseudo-default resource module from CeusMedia:HydrogenModules
			$parts	= preg_split( '/\s*,\s*/', $this->config->database->modules );					//  split comma separated list if resource modules in registration format
			foreach( $parts as $moduleRegistration ){												//  iterate these module registrations
				$moduleId		= $moduleRegistration;												//  assume module ID to be the while module registration string ...
				$configPrefix	= '';																//  ... and no config prefix as fallback (simplest situation)
				if( preg_match( '/:/', $moduleRegistration ) )										//  a prefix defintion has been announced
					list( $moduleId, $configPrefix ) = preg_split( '/:/', $moduleRegistration, 2 );	//  split module ID and config prefix into variables
				$modules[$moduleId]	= $configPrefix;												//  enlist resource module registration in array form
			}
		}
		foreach( $modules as $moduleId => $configPrefix ){											//  iterate given or found resource module registrations
		//	$this->outVeryVerbose( 'Applying database config to module '.$moduleId.' ...' );		//  tell about this in very verbose mode
			if( !isset( $this->config->modules->{$moduleId} ) )										//  registered module is not installed
				$this->config->modules->{$moduleId}	= (object) array();								//  create an empty module definition in loaded module list
			$module	= $this->config->modules->{$moduleId};											//  shortcut module definition
			if( !isset( $module->config ) )															//  module definition has not configuration
				$module->config	= (object) array();													//  create an empty configuration in module definition
			foreach( $this->config->database as $key => $value )									//  iterate database access information from hymn file
				if( !in_array( $key, array( 'modules' ) ) )											//  skip the found list of resource modules to apply exactly this method to
					$module->config->{$configPrefix.$key}	= $value;								//  set database access information in resource module configuration
		}
		return TRUE;
	}

	protected function applyBaseConfiguredPathsToAppConfig(){
		$this->config->paths	= (object) array();
		foreach( self::$pathDefaults as $pathKey => $pathValue )
			if( !isset( $this->config->paths->{$pathKey} ) )
				$this->config->paths->{$pathKey}	= $pathValue;

		if( file_exists( $this->config->paths->config.'config.ini' ) ){
			$data	= parse_ini_file( $this->config->paths->config.'config.ini' );
			foreach( $data as $key => $value ){
				if( preg_match( '/^path\./', $key ) ){
					$key	= preg_replace( '/^path\./', '', $key );
					$key	= ucwords( str_replace( '.', ' ', $key ) );
					$key	= str_replace( ' ', '', lcfirst( $key ) );
					$this->config->paths->{$key}	= $value;
					continue;
				}
				$key	= ucwords( str_replace( '.', ' ', $key ) );
				$key	= str_replace( ' ', '', lcfirst( $key ) );
				$this->config->{$key}	= $value;
			}
		}
	}

	protected function applyCommandOptionsToArguments( Hymn_Command_Interface $commandObject ){
		$commandOptions		= call_user_func( array( $commandObject, 'getArgumentOptions' ) );		//  get command specific argument options
		$options			= array_merge( $this->baseArgumentOptions, $commandOptions );			//  merge with base argument options
		$this->arguments	= new Hymn_Tool_CLI_Arguments( $this->originalArguments, $options );	//  parse original arguments again with combined options
		$this->arguments->removeArgument( 0 );														//  remove the first argument which is the command itself
	}

	protected function dispatch(){
		$calledAction	= trim( $this->arguments->getArgument( 0 ) );								//  get called command
		if( strlen( $calledAction ) ){																//  command string given
			$this->arguments->removeArgument( 0 );													//  remove command from arguments list
			try{
				if( !in_array( $calledAction, self::$commandWithoutConfig ) ){						//  command needs hymn file
					$this->outVeryVerbose( 'Reading application configuration ...' );				//  note reading of application configuration
					$this->readConfig();															//  read application configuration from hymn file
				}
				$className			= $this->getCommandClassFromCommand( $calledAction );			//  get command class from called command
				$reflectedClass		= new ReflectionClass( $className );							//  reflect class
			//	$classInterfaces	= $reflectedClass->getInterfaceNames();							//  get interfaces implemented by class
				if( !$reflectedClass->implementsInterface( 'Hymn_Command_Interface' ) )
					throw new RuntimeException( sprintf(
						$this->words->errorCommandClassNotImplementingInterface,
						$className
					) );
				$commandObject		= $reflectedClass->newInstanceArgs( array( $this ) );			//  create object of reflected class
				$reflectedObject	= new ReflectionObject( $commandObject );						//  reflect object for method call
				$reflectedMethod    = $reflectedObject->getMethod( 'run' );							//  reflect object method "run"
				$this->applyCommandOptionsToArguments( $commandObject );							//  extend argument options by command specific options
				$reflectedMethod->invokeArgs( $commandObject, (array) $this->arguments );			//  call reflected object method
			}
			catch( Exception $e ){
				$this->outError( $e->getMessage().'.', Hymn_Client::EXIT_ON_RUN );
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
		$commandWords	= ucwords( preg_replace( '/-+/', ' ', $command ) );
		$className		= 'Hymn_Command_'.preg_replace( '/ +/', '_', $commandWords );
		if( !class_exists( $className ) )
			throw new RangeException( sprintf(
				$this->words->errorCommandUnknown,
				$command
			) );
		return $className;
	}

	protected function readConfig( $forceReload = FALSE ){
		if( $this->config && !$forceReload )
			return;
		if( !file_exists( self::$fileName ) )
			throw new RuntimeException( 'File "'.self::$fileName.'" is missing. Please use command "init"' );
		$this->config	= json_decode( file_get_contents( self::$fileName ) );
		if( is_null( $this->config ) )
			throw new RuntimeException( 'Configuration file "'.self::$fileName.'" is not valid JSON' );

/*		if( is_string( $this->config->sources ) ){
			if( !file_exists( $this->config->sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is missing' );
			$sources	= json_decode( file_get_contents( $this->config->sources ) );
			if( is_null( $sources ) )
				throw new RuntimeException( 'Sources file "'.$this->config->sources.'" is not valid JSON' );
			$this->config->sources = $sources;
		}*/

		$app	= $this->config->application;
		if( isset( $app->configPath ) )
			self::$pathDefaults['config'] = $app->configPath;

		$this->applyBaseConfiguredPathsToAppConfig();
		$this->applyAppConfiguredDatabaseConfigToModules();
		if( isset( $app->installMode ) && $app->installMode === 'live' )							//  is live installation
			if( isset( $app->installType ) && $app->installType === 'copy' )						//  is installation copy
				$this->isLiveCopy = TRUE;															//  this installation is a build for a live copy
	}
}
?>
