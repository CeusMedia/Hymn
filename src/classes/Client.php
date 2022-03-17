<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Client
{
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

	public static $fileName			= '.hymn';

	public static $outputMethod		= 'print';

	public static $language			= 'en';

	public static $version			= '0.9.9.5a';

	/** @var	Hymn_Tool_CLI_Arguments 	$arguments		Parsed CLI arguments and options */
	public $arguments;

	public $flags					= 0;

	public $locale;

	public $memoryUsageAtStart;

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

	protected static $commandWithoutConfig	= array(
		//  APP CREATION
		'init',
		//  SELF MANAGEMENT
		'help',
		'self-update',
		'test-syntax',
		'version',
	);

	protected $config;

	protected $database;

	protected $framework;

	protected $words;

	protected $isLiveCopy			= FALSE;

	protected $originalArguments	= array();

	/** @var	Hymn_Tool_CLI_Output	$output */
	protected $output;

	protected $exit;

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		array			$arguments		Map of CLI arguments
	 *	@param		boolean			$exit			Flag: exit execution afterwards (default: yes)
	 *	@return		void
	 *	@throws		RuntimeException				if trying to run via web server
	 */
	public function __construct( array $arguments, bool $exit = TRUE )
	{
		$this->exit					= $exit;
		$this->originalArguments	= $arguments;

		ini_set( 'display_errors', TRUE );
		error_reporting( E_ALL );

		try{
			$this->parseArguments( $arguments );
			$this->realizeLanguage();
			$this->output		= new Hymn_Tool_CLI_Output( $this, $exit );
			$this->outVeryVerbose( 'hymn v'.self::$version );
			$this->outVeryVerbose( $this->getMemoryUsage( 'at start' ) );

			if( getEnv( 'HTTP_HOST' ) )
				throw new RuntimeException( 'Access denied' );
			$action	= $this->arguments->getArgument();
			if( !$action && $this->arguments->getOption( 'help' ) ){
				array_unshift( $arguments, 'help' );
				$this->parseArguments( $arguments, [], TRUE );
			}
			else if( $this->arguments->getOption( 'version' ) ){
				array_unshift( $arguments, 'version' );
				$this->parseArguments( $arguments, [], TRUE );
			}

			self::$fileName		= $this->arguments->getOption( 'file' );
			$this->dispatch();
		}
		catch( Exception $e ){
			$this->outError( $e->getMessage().'.', Hymn_Client::EXIT_ON_SETUP );
		}
		finally{
			$this->outVeryVerbose( $this->getMemoryUsage( 'at the end' ) );
		}
		if( $this->exit )
			exit( Hymn_Client::EXIT_ON_END );
	}

	public function getFramework(): Hymn_Tool_Framework
	{
		if( !$this->framework )
			$this->framework	= new Hymn_Tool_Framework();
		return $this->framework;
	}

	public function getConfig()
	{
		if( !$this->config )
			$this->readConfig();
		return $this->config;
	}

	public function getConfigPath(): string
	{
		$config	= $this->getConfig();
		if( substr( $config->paths->config, 0, 1 ) === '/' )
			return $config->paths->config;
		return $config->application->uri.$config->paths->config;
	}

	public function getDatabase(): Hymn_Tool_Database_PDO
	{
		if( !$this->database )
			$this->database		= new Hymn_Tool_Database_PDO( $this );
		return $this->database;
	}

	public function getLocale(): Hymn_Tool_Locale
	{
		return $this->locale;
	}

	public function getMemoryUsage( string $position = '' )
	{
		$bytes	= memory_get_usage();
		if( !$this->memoryUsageAtStart )
			$this->memoryUsageAtStart	= $bytes;

		$difference	= $bytes - $this->memoryUsageAtStart;
		return vsprintf( 'Memory usage%s: +%s (%s)', [
			strlen( trim( $position ) ) ? ' '.trim( $position ) : '',
			Hymn_Tool_FileSize::formatBytes( $difference ),
			Hymn_Tool_FileSize::formatBytes( $bytes ),
		] );
	}

/*	public function getModuleInstallMode( string $moduleId, string $defaultInstallMode = 'dev' ): string
	{
		$mode	= $defaultInstallMode;
		if( isset( $this->config->application->{'installMode'} ) )
			$mode	= $this->config->application->{'installMode'};
		return $mode;
	}*/

	public function getModuleInstallType( string $moduleId, string $defaultInstallType = 'copy' ): string
	{
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

	public function getModuleInstallShelf( string $moduleId, array $availableShelfIds, string $defaultInstallShelfId )
	{
		if( !count( $availableShelfIds ) )
			throw new InvalidArgumentException( 'No available source IDs given' );

		$modules	= $this->config->modules;														//  shortcut configured modules
		if( isset( $modules->$moduleId ) )															//  module is configured in hymn file
			if( isset( $modules->$moduleId->source ) )												//  module has configured source shelf
				if( in_array( $modules->$moduleId->source, $availableShelfIds ) )					//  configured shelf source has requested module
					return $modules->$moduleId->source;												//  return configured source shelf

		if( in_array( $defaultInstallShelfId, $availableShelfIds ) )								//  default shelf has requested module
			return $defaultInstallShelfId;															//  return default shelf

		return current( $availableShelfIds );														//  return first available shelf
	}

	/**
	 *	Prints out message of one ore more lines.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@return		void
	 *	@throws		InvalidArgumentException		if neither array nor string nor NULL given
	 */
	public function out( $lines = NULL, bool $newLine = TRUE )
	{
		$this->output->out( $lines, $newLine );
	}

	/**
	 *	Prints out deprecation message of one ore more lines.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@throws		InvalidArgumentException		if neither array nor string given
	 *	@throws		InvalidArgumentException		if given string is empty
	 *	@return		void
	 */
	public function outDeprecation( array $lines = array() )
	{
		$this->output->outDeprecation( $lines );
	}

	/**
	 *	Prints out error message.
	 *	@access		public
	 *	@param		string			$message		Error message to print
	 *	@param		integer			$exitCode		Exit with error code, if given, otherwise do not exit (default)
	 *	@return		void
	 */
	public function outError( string $message, ?int $exitCode = NULL )
	{
		$this->output->outError( $message, $exitCode );
	}

	/**
	 *	Prints out verbose message if verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@return		void
	 */
	public function outVerbose( $lines, bool $newLine = TRUE )
	{
		$this->output->outVerbose( $lines, $newLine );
	}

	/**
	 *	Prints out verbose message if very verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@return		void
	 */
	public function outVeryVerbose( $lines, bool $newLine = TRUE )
	{
		$this->output->outVeryVerbose( $lines, $newLine );
	}

	public function runCommand( string $command, array $arguments = array(), array $addOptions = array(), array $ignoreOptions = array() )
	{
		if( $this->flags & self::FLAG_VERY_VERBOSE )
			$this->outVeryVerbose( $this->getMemoryUsage( 'at Client::runCommand' ) );
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

	protected function applyCommandOptionsToArguments( Hymn_Command_Interface $commandObject )
	{
		$commandOptions	= call_user_func( array( $commandObject, 'getArgumentOptions' ) );			//  get command specific argument options
		$this->parseArguments( $this->originalArguments, $commandOptions, TRUE );					//  parse original arguments again with command specific options
		$this->arguments->removeArgument( 0 );														//  remove the first argument which is the command itself
	}

	protected function dispatch()
	{
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
				$reflectedMethod->invokeArgs( $commandObject, $this->arguments->getArguments() );	//  call reflected object method
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
	protected function getCommandClassFromCommand( string $command ): string
	{
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

	protected function parseArguments( $arguments, array $options = array(), bool $force = FALSE )
	{
		$options	= array_merge( $this->baseArgumentOptions, $options );
		if( $this->arguments && !$force )
			return;
		$this->arguments	= new Hymn_Tool_CLI_Arguments( $arguments, $options );

		if( $force )
			$this->flags	= 0;
		$map	= [
			'dry'			=> [self::FLAG_DRY],
			'force'			=> [self::FLAG_FORCE],
			'quiet'			=> [self::FLAG_QUIET],
			'verbose'		=> [self::FLAG_VERBOSE],
			'very-verbose'	=> [self::FLAG_VERBOSE, self::FLAG_VERY_VERBOSE],
		];
		foreach( $map as $key => $flags )
			if( $this->arguments->getOption( $key ) )
				foreach( $flags as $flag )
					$this->flags	|= $flag;

		if( $this->arguments->getOption( 'db' ) === 'no' )
			$this->flags	|= self::FLAG_NO_DB;
		if( $this->arguments->getOption( 'db' ) === 'only' )
			$this->flags	|= self::FLAG_NO_FILES;
		if( $this->arguments->getOption( 'interactive' ) === 'no' )
			$this->flags	|= self::FLAG_NO_INTERACTION;
	}

	protected function readConfig( bool $forceReload = FALSE )
	{
		if( $this->config && !$forceReload )
			return;

		$config	= new Hymn_Tool_CLI_Config( $this );
		$this->config	= $config->readConfig();
	}

	protected function realizeLanguage()
	{
		if( class_exists( 'Locale' ) ){
			$language	= Locale::getPrimaryLanguage( Locale::getDefault() );
			if( in_array( $language, array( 'en', 'de' ) ) )
				self::$language	= $language;
		}
		$this->locale		= new Hymn_Tool_Locale( self::$language );
		$this->words		= $this->locale->loadWords( 'client' );
	}
}
