<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Client
{
	public const FLAG_VERY_VERBOSE		= 1;
	public const FLAG_VERBOSE			= 2;
	public const FLAG_QUIET				= 4;
	public const FLAG_DRY				= 8;
	public const FLAG_FORCE				= 16;
	public const FLAG_NO_DB				= 32;
	public const FLAG_NO_FILES			= 64;
	public const FLAG_NO_INTERACTION	= 128;

	public const EXIT_ON_END			= 0;
	public const EXIT_ON_LOAD			= 1;
	public const EXIT_ON_SETUP			= 2;
	public const EXIT_ON_RUN			= 4;
	public const EXIT_ON_INPUT			= 8;
	public const EXIT_ON_EXEC			= 16;
	public const EXIT_ON_OUTPUT			= 32;

	public static string $fileName				= '.hymn';

	public static string $outputMethod			= 'print';

	public static string $language				= 'en';

	public static string $version				= '1.0.1c';

	public static string $mode					= 'prod';

	public static string $phpPath				= '/usr/bin/php';

	/** @var	string		$pharPathroot		PHAR file resource link  */
	public static string $pharPath				= 'phar://hymn.phar/';


	/** @var	Hymn_Tool_CLI_Arguments 		$arguments		Parsed CLI arguments and options */
	public Hymn_Tool_CLI_Arguments $arguments;

	public int $flags							= 0;

	public ?Hymn_Tool_Locale $locale			= NULL;

	public int $memoryUsageAtStart				= 0;

	protected array $baseArgumentOptions		= [
		'db'		=> [
			'pattern'	=> '/^--db=(\S+)$/',
			'resolve'	=> '\\1',
			'values'	=> ['yes', 'no', 'only'],
			'default'	=> 'yes',
		],
		'dry'		=> [
			'pattern'	=> '/^-d|--dry/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		],
		'file'		=> [
			'pattern'	=> '/^--file=(\S+)$/',
			'resolve'	=> '\\1',
			'default'	=> '.hymn',
		],
		'force'		=> [
			'pattern'	=> '/^-f|--force$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		],
		'help'		=> [
			'pattern'	=> '/^-h|--help/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		],
		'quiet'		=> [
			'pattern'	=> '/^-q|--quiet$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
			'excludes'	=> 'verbose',
		],
		'verbose'	=> [
			'pattern'	=> '/^-v|--verbose$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		],
		'very-verbose'	=> [
			'pattern'	=> '/^-vv|--very-verbose$/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
			'includes'	=> 'verbose',
		],
		'version'	=> [
			'pattern'	=> '/^--version/',
			'resolve'	=> TRUE,
			'default'	=> NULL,
		],
		'interactive'	=> [
			'pattern'	=> '/^--interactive=(\S+)$/',
			'resolve'	=> '\\1',
			'values'	=> ['yes', 'no'],
			'default'	=> 'yes',
		],
		'comment'	=> [
			'pattern'	=> '/^--comment=(\S+)$/',
			'resolve'	=> '\\1',
			'default'	=> NULL,
		]
	];

	protected static array $commandWithoutConfig	= [
		//  APP CREATION
		'init',
		//  SELF MANAGEMENT
		'help',
		'self-update',
		'test-syntax',
		'version',
	];

	protected ?Hymn_Structure_Config $config		= NULL;

	protected ?Hymn_Tool_Database_PDO $database		= NULL;

	protected ?Hymn_Tool_Framework $framework		= NULL;

	/** @var object{outPrefixError: string, outPrefixDeprecation: string, errorCommandUnknown: string, errorCommandClassNotImplementingInterface: string}  */
	protected ?object $words;

	protected bool $isLiveCopy						= FALSE;

	protected array $originalArguments				= [];

	/** @var	Hymn_Tool_CLI_Output|NULL			$output */
	protected ?Hymn_Tool_CLI_Output $output			= NULL;

	protected bool $exit;

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

		$phar	= Hymn_Client::$pharPath;
		if( file_exists( $phar.'.mode' ) )
			self::$mode		= file_get_contents( $phar.'.mode' ) ?: 'prod';
		if( file_exists( $phar.'.php' ) )
			self::$phpPath	= file_get_contents( $phar.'.php' ) ?: '/usr/bin/env php';

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

			self::$fileName		= (string) $this->arguments->getOption( 'file' );
			$this->dispatch();
		}
		catch( Exception $e ){
			$this->outError( $e->getMessage().'.' );
			$this->outVerbose( Hymn_Tool_CLI_ExceptionTraceView::getInstance( $e )->render() );
			exit( Hymn_Client::EXIT_ON_SETUP );
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

	public function getConfig(): Hymn_Structure_Config
	{
		$this->readConfig();
		return $this->config;
	}

	public function getConfigPath(): string
	{
		$config	= $this->getConfig();
		if( str_starts_with( $config->paths->config, '/' ) )
			return $config->paths->config;
		return $config->application->uri.$config->paths->config;
	}

	public function getDatabase(): Hymn_Tool_Database_PDO
	{
		if( !$this->database )
			$this->database		= new Hymn_Tool_Database_PDO( $this );
		return $this->database;
	}

	public function getLocale(): ?Hymn_Tool_Locale
	{
		return $this->locale;
	}

	public function getMemoryUsage( string $position = '' ): string
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
		$type	= $this->config->application->installType ?? $defaultInstallType;
		if( isset( $this->config->modules[$moduleId] ) )
			if( '' !== ( $this->config->modules[$moduleId]->installType ?? '' ) )
				$type	= $this->config->modules[$moduleId]->installType;
		return $type;
	}

	public function getModuleInstallSource( string $moduleId, array $availableSourceIds, ?string $defaultInstallSourceId = NULL )
	{
		if( !count( $availableSourceIds ) )
			throw new InvalidArgumentException( 'No available source IDs given' );

		$modules	= $this->config->modules;														//  shortcut configured modules
		if( isset( $modules->$moduleId ) )															//  module is configured in hymn file
			if( isset( $modules->$moduleId->source ) )												//  module has configured source source
				if( in_array( $modules->$moduleId->source, $availableSourceIds ) )					//  configured source source has requested module
					return $modules->$moduleId->source;												//  return configured source source

		if( $defaultInstallSourceId !== NULL )														//  default source given
			if( in_array( $defaultInstallSourceId, $availableSourceIds ) )							//  default source has requested module
				return $defaultInstallSourceId;														//  return default source

		return current( $availableSourceIds );														//  return first available source
	}

	/**
	 *	Prints out message of one or more lines.
	 *	@access		public
	 *	@param		string|bool|int|float|array|NULL	$lines		List of message lines or one string
	 *	@param		boolean								$newLine	Flag: add newline at the end
	 *	@return		self
	 *	@throws		InvalidArgumentException			if neither array nor string nor NULL given
	 */
	public function out( string|bool|int|float|array|NULL $lines = NULL, bool $newLine = TRUE ): self
	{
		$this->output?->out( $lines, $newLine );
		return $this;
	}

	/**
	 *	Prints out deprecation message of one or more lines.
	 *	@access		public
	 *	@param		string|array		$lines		List of message lines or one string
	 *	@throws		InvalidArgumentException		if neither array nor string given
	 *	@throws		InvalidArgumentException		if given string is empty
	 *	@return		self
	 */
	public function outDeprecation( string|array $lines = [] ): self
	{
		$this->output?->outDeprecation( $lines );
		return $this;
	}

	/**
	 *	Prints out error message.
	 *	@access		public
	 *	@param		string			$message		Error message to print
	 *	@param		integer|NULL	$exitCode		Exit with error code, if given, otherwise do not exit (default)
	 *	@return		self
	 */
	public function outError( string $message, ?int $exitCode = NULL ): self
	{
		$this->output?->outError( $message, $exitCode );
		return $this;
	}

	/**
	 *	Prints out verbose message if verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		string|bool|int|float|array|NULL	$lines		List of message lines or one string
	 *	@param		boolean								$newLine	Flag: add newline at the end
	 *	@return		self
	 */
	public function outVerbose( string|bool|int|float|array|NULL $lines, bool $newLine = TRUE ): self
	{
		$this->output?->outVerbose( $lines, $newLine );
		return $this;
	}

	/**
	 *	Prints out verbose message if very verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		string|bool|int|float|array|NULL	$lines		List of message lines or one string
	 *	@param		boolean								$newLine	Flag: add newline at the end
	 *	@return		self
	 */
	public function outVeryVerbose( string|bool|int|float|array|NULL $lines, bool $newLine = TRUE ): self
	{
		$this->output?->outVeryVerbose( $lines, $newLine );
		return $this;
	}

	public function runCommand( string $command, array $arguments = [], array $addOptions = [], array $ignoreOptions = [] ): void
	{
		if( $this->flags & self::FLAG_VERY_VERBOSE )
			$this->outVeryVerbose( $this->getMemoryUsage( 'at Client::runCommand' ) );
		$args	= [$command];
		foreach( $arguments as $argument )
			$args[]	= $argument;

		foreach( $this->arguments->getOptions() as $key => $value ){
			if( !strlen( $value ?? '' ) || !array_key_exists( $key, $this->baseArgumentOptions ) )
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
			if( !strlen( $value ?? '' ) || !array_key_exists( $key, $this->baseArgumentOptions ) )
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

	protected function applyCommandOptionsToArguments( Hymn_Command_Interface $commandObject ): void
	{
		$commandOptions	= call_user_func( [$commandObject, 'getArgumentOptions'] );			//  get command specific argument options
		if( $commandOptions ){
			$this->parseArguments( $this->originalArguments, $commandOptions, TRUE );				//  parse original arguments again with command specific options
			$this->arguments->removeArgument( 0 );														//  remove the first argument which is the command itself
		}
	}

	protected function dispatch(): void
	{
		$calledAction	= trim( $this->arguments->getArgument( 0 ) ?? '' );							//  get called command
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
				$commandObject		= $reflectedClass->newInstanceArgs( [$this] );			//  create object of reflected class
				$reflectedObject	= new ReflectionObject( $commandObject );						//  reflect object for method call
				$reflectedMethod    = $reflectedObject->getMethod( 'run' );							//  reflect object method "run"
				$this->applyCommandOptionsToArguments( $commandObject );							//  extend argument options by command specific options
				$reflectedMethod->invokeArgs( $commandObject, $this->arguments->getArguments() );	//  call reflected object method
			}
			catch( Exception $e ){
				$this->outError( $e->getMessage().'.' );
				$this->outVerbose( Hymn_Tool_CLI_ExceptionTraceView::getInstance( $e )->render() );
				exit( Hymn_Client::EXIT_ON_RUN );
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

	protected function parseArguments( array $arguments, array $options = [], bool $force = FALSE ): void
	{
		$options	= array_merge( $this->baseArgumentOptions, $options );
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

	protected function readConfig( bool $forceReload = FALSE ): void
	{
		if( $this->config && !$forceReload )
			return;

		$config			= new Hymn_Tool_CLI_Config( $this );
		$this->config	= $config->readConfig();
	}

	protected function realizeLanguage(): void
	{
		if( class_exists( 'Locale' ) ){
			$language	= Locale::getPrimaryLanguage( Locale::getDefault() );
			if( in_array( $language, ['en', 'de'] ) )
				self::$language	= $language;
		}
		$this->locale		= new Hymn_Tool_Locale( self::$language );
		/** @var object{outPrefixError: string, outPrefixDeprecation: string, errorCommandUnknown: string, errorCommandClassNotImplementingInterface: string} $words */
		$words				= $this->locale->loadWords( 'client' );
		$this->words		= $words;
	}
}
