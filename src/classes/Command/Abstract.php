<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2025 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
abstract class Hymn_Command_Abstract
{
	/** @var	Hymn_Client					$client */
	protected Hymn_Client $client;

	/** @var	Hymn_Module_Library|NULL	$library */
	protected ?Hymn_Module_Library $library	= NULL;

	/** @var object{dry: bool, force: bool, quiet: bool, verbose: bool, veryVerbose: bool} $flags */
	protected object $flags;

	protected Hymn_Tool_Locale $locale;

	protected object $words;

	protected array $argumentOptions	= [];

	public function getArgumentOptions(): array
	{
		return $this->argumentOptions;
	}

	/**
	 *	Constructor.
	 *	@access		public
	 *	@param		Hymn_Client		$client		Hymn client instance
	 *	@return		void
	 */
	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
		$this->flags	= (object) [
			'dry'			=> (bool) ( $this->client->flags & Hymn_Client::FLAG_DRY ),
			'force'			=> (bool) ( $this->client->flags & Hymn_Client::FLAG_FORCE ),
			'quiet'			=> (bool) ( $this->client->flags & Hymn_Client::FLAG_QUIET ),
			'verbose'		=> (bool) ( $this->client->flags & Hymn_Client::FLAG_VERBOSE ),
			'veryVerbose'	=> (bool) ( $this->client->flags & Hymn_Client::FLAG_VERY_VERBOSE ),
		];
		$this->locale	= $this->client->getLocale();

		/** @var string $localeKey */
		$localeKey		= preg_replace( '/^Hymn_/', '', get_class( $this ) );
		$localeKey		= str_replace( '_', '/', strtolower( $localeKey ) );
		$this->words	= (object) [];
		if( $this->locale->hasWords( $localeKey ) )
			$this->words	= $this->locale->loadWords( $localeKey );
		$this->__onInit();
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
		$this->client->out( $lines, $newLine );
		return $this;
	}

	/**
	 *	Prints out deprecation message of one or more lines.
	 *	@access		public
	 *	@param		array|string		$lines		List of message lines or one string
	 *	@throws		InvalidArgumentException		if neither array nor string given
	 *	@throws		InvalidArgumentException		if given string is empty
	 *	@return		void
	 */
	public function outDeprecation( string|array $lines = [] ): void
	{
		$this->client->outDeprecation( $lines );
	}

	/**
	 *	Prints out error message.
	 *	@access		public
	 *	@param		string				$message		Error message to print
	 *	@param		integer|NULL		$exitCode		Exit with error code, if given, otherwise do not exit (default)
	 *	@return		void
	 */
	public function outError( string $message, ?int $exitCode = NULL ): void
	{
		$this->client->outError( $message, $exitCode );
	}

	/**
	 *	Prints out verbose message if verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		array|string|NULL	$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@return		void
	 */
	public function outVerbose( array|string|NULL $lines, bool $newLine = TRUE ): void
	{
		$this->client->outVerbose( $lines, $newLine );
	}

	/**
	 *	Prints out verbose message if very verbose mode is on and quiet mode is off.
	 *	@access		public
	 *	@param		array|string|NULL	$lines		List of message lines or one string
	 *	@param		boolean				$newLine	Flag: add newline at the end
	 *	@return		void
	 */
	public function outVeryVerbose( array|string|NULL $lines, bool $newLine = TRUE ): void
	{
		$this->client->outVeryVerbose( $lines, $newLine );
	}

	/**
	 *	This method is automatically called by client dispatcher.
	 *	Commands need to implement this method.
	 *	@access		public
	 *	@return		int|void
	 */
	abstract public function run();

	protected function __onInit(): void
	{
	}

	protected function ask( string $message, string $type = 'string', ?string $default = NULL, ?array $options = [], bool $break = FALSE ): float|bool|int|string
	{
		return Hymn_Tool_CLI_Question::getInstance(
			$this->client,
			$message,
			$type,
			$default,
			$options ?? [],
			$break
		)->ask();
	}

	protected function denyOnProductionMode(): void
	{
		if( Hymn_Client::$mode === 'prod' )
			$this->outError( 'Not allowed in production mode', Hymn_Client::EXIT_ON_SETUP );
	}

	protected function deprecate( array|string $messageLines ): void
	{
		$this->client->outDeprecation( $messageLines );
	}

	/**
	 *	...
	 *	@access		protected
	 *	@param		string|NULL		$sourceId		ID of source or all [all,*] (default)
	 *	@param		boolean			$strict			Flag: throw exception if source ID is not existing
	 *	@return		string|FALSE|NULL
	 *	@throws		RangeException					if source ID is given and not existing
	 *	@todo		sharpen return value
	 *	@todo		finish code doc
	 */
	protected function evaluateSourceId( ?string $sourceId = NULL, bool $strict = TRUE )
	{
		$all	= ['all', '*'];
		if( is_null( $sourceId ) || in_array( $sourceId, $all ) )
			return NULL;
		$library	= $this->getLibrary();
		if( $library->isSource( $sourceId ) )
			return $sourceId;
		if( $strict )
			throw new RangeException( 'Source ID '.$sourceId.' is invalid' );
		return FALSE;
	}

	/**
	 *	Return all available modules in library as map by module ID.
	 *	Reduce all available modules in library (from all source) to map by module ID.
	 *	ATTENTION: Modules with same ID from different sources will collide. Only latest of these modules is noted.
	 *	@access		protected
	 *	@param		string|NULL			$sourceId	...
	 *	@return		array<string,Hymn_Structure_Module>		Map of modules by ID
	 *	@todo		find a better solution!
	 */
	protected function getAvailableModulesMap( ?string $sourceId = NULL ): array
	{
		$library	= $this->getLibrary();															//  try to load sources into a library
		$moduleMap	= [];																		//  prepare empty list of available modules
		foreach( $library->getAvailableModules( $sourceId ) as $module )										//  iterate available modules in library
			$moduleMap[$module->id]	= $module;														//  note module by ID (=invalid override)
		return $moduleMap;																			//  return map of modules by ID
	}

	/**
	 *	Returns library of available modules in found sources.
	 *	Note: Several sources are stored as sources, so same module IDs are allowed.
	 *	Loads library sources on first call, returns already loaded library on second call.
	 *	Reloading library is possible with flag 'forceReload'.
	 *	@access		protected
	 *	@param		boolean		$forceReload	Flag: reload library (optional, not default)
	 *	@return		Hymn_Module_Library			Library of available modules in found sources
	 */
	protected function getLibrary( bool $forceReload = FALSE ): Hymn_Module_Library
	{
		$config	= $this->client->getConfig();
		if( is_null( $this->library ) || $forceReload )												//  library not loaded yet or reload is forced
			$this->readLibrary( $config );
		return $this->library;																		//  return loaded library
	}

	protected function readLibrary( object $config ): void
	{
		$this->library	= new Hymn_Module_Library( $this->client );								//  create new module library
		if( $this->flags->force )																//  on force mode
			$this->library->setReadMode( Hymn_Module_Library_Available::MODE_FOLDER );			//  ... skip module source indices

		if( !isset( $config->sources ) || 0 === count( (array) $config->sources ) ){
			$this->client->out( 'Warning: No sources defined in Hymn file.' );					//  output warning
			return;																//  return empty library
		}
		foreach( $config->sources as $sourceId => $source ){									//  iterate sources defined in Hymn file
			if( isset( $source->active ) && $source->active === FALSE )							//  source is (explicitly) disabled
				continue;																		//  skip this source
			if( !isset( $source->path ) || !strlen( trim( $source->path ) ) ){					//  source path has NOT been set
				$msg	= 'Warning: No path defined for source "%s". Source has been ignored.';	//  warning message to show
				$this->client->out( sprintf( $msg, $sourceId ) );								//  output warning
			}
			else if( !file_exists( $source->path ) ){											//  source path has NOT been detected
				$msg	= 'Path to source "%s" is not existing. Source has been ignored.';		//  warning message to show
				$this->client->out( sprintf( $msg, $sourceId ) );									//  output warning
			}
			else{
				$active	= !isset( $source->active ) || $source->active;							//  evaluate source activity
				$type	= $source->type ?? 'folder';											//  set default source type if not defined
				$title	= $source->title ?? '';													//  set source title
				$this->library->addSource( $sourceId, $source->path, $type, $active, $title );	//  add source as source in library
			}
		}
	}

	protected function realizeWildcardedModuleIds( array $givenModuleIds, array $availableModuleIds ): array
	{
		$list	= [];
		foreach( $givenModuleIds as $givenModuleId ){
			if( !substr_count( $givenModuleId, '*' ) ){
				if( in_array( $givenModuleId, $availableModuleIds ) ){
					$list[]	= $givenModuleId;
				}
				continue;
			}
			$pattern	= str_replace( '\*', '.+', preg_quote( $givenModuleId, '/' ) );
			$this->client->outVerbose( sprintf(
				'Looking for suitable modules for module group: %s ...',
				$givenModuleId
			) );
			foreach( $availableModuleIds as $availableModuleId ){
				if( preg_match( '/^'.$pattern.'$/i', $availableModuleId ) ){
					$this->client->outVerbose( ' - found module '.$availableModuleId );
					$list[]	= $availableModuleId;
				}
			}
		}
		return $list;
	}
}
