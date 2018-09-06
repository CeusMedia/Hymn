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
 */
abstract class Hymn_Command_Abstract{

	protected $client;
	protected $library	= NULL;
	protected $flags;
	protected $locale;
	protected $words;

	public function __construct( Hymn_Client $client ){
		$this->client	= $client;
		$this->flags	= (object) array(
			'force'		=> $this->client->flags & Hymn_Client::FLAG_FORCE,
			'quiet'		=> $this->client->flags & Hymn_Client::FLAG_QUIET,
			'verbose'	=> $this->client->flags & Hymn_Client::FLAG_VERBOSE,
			'dry'		=> $this->client->flags & Hymn_Client::FLAG_DRY,
		);
		$this->locale	= $this->client->getLocale();
		$localeKey		= preg_replace( '/^Hymn_/', '', get_class( $this ) );
		$localeKey		= str_replace( '_', '/', strtolower( $localeKey ) );
		$this->words	= (object) array();
		if( $this->locale->hasWords( $localeKey ) )
			$this->words	= $this->locale->loadWords( $localeKey );
		$this->__onInit();
	}

	protected function __onInit(){
	}

	protected function ask( $message, $type = 'string', $default = NULL, $options = array(), $break = FALSE ){
		return $this->client->getInput( $message, $type, $default, $options, $break );
	}

	protected function deprecate( $messageLines, $exit = FALSE ){
		$this->client->outDeprecation( $messageLines );
	}

	/**
	 *	@throws
	 */
	protected function evaluateShelfId( $shelfId = NULL, $strict = TRUE ){
		$all	= array( 'all' );
		if( is_null( $shelfId ) || in_array( $shelfId, $all ) )
			return NULL;
		$library	= $this->getLibrary();
		if( $library->isShelf( $shelfId ) )
			return $shelfId;
		if( $strict )
			throw new \RangeException( 'Source ID '.$shelfId.' is invalid' );
		return FALSE;
	}

	/**
	 *	Return all available modules in library as map by module ID.
	 *	Reduce all available modules in library (from all source) to map by module ID.
	 *	ATTENTION: Modules with same ID from different sources will collide. Only latest of these modules is noted.
	 *	@access		protected
	 *	@param		...			$config		...
	 *	@param		...			$shelfId	...
	 *	@return		array					Map of modules by ID
	 *	@todo		find a better solution!
	 */
	protected function getAvailableModulesMap( $config, $shelfId = NULL ){
		$library	= $this->getLibrary();															//  try to load sources into a library
		$moduleMap	= array();																		//  prepare empty list of available modules
		foreach( $library->getModules( $shelfId ) as $module )										//  iterate available modules in library
			$moduleMap[$module->id]	= $module;														//  note module by ID (=invalid override)
		return $moduleMap;																			//  return map of modules by ID
	}

	/**
	 *	Returns library of available modules in found sources.
	 *	Note: Several sources are stored as shelfes, so same module IDs are allowed.
	 *	Loads library sources on first call, returns already loaded library on second call.
	 *	Reloading library is possible with flag 'forceReload'.
	 *	@access		protected
	 *	@param		boolean		$forceReload	Flag: reload library (optional, not default)
	 *	@return		Hymn_Module_Library			Library of available modules in found sources
	 */
	protected function getLibrary( $forceReload = FALSE ){
		$config	= $this->client->getConfig();
		if( is_null( $this->library ) || $forceReload ){											//  library not loaded yet or reload is forced
			$this->library	= new Hymn_Module_Library( $this->client );								//  create new module library
			if( !isset( $config->sources ) || empty( $config->sources ) ){
				$msg	= 'Warning: No sources defined in Hymn file.';								//  warning message to show
				$this->client->out( sprintf( $msg, $sourceId ) );										//  output warning
				return $this->library;																//  return empty library
			}
			foreach( $config->sources as $sourceId => $source ){									//  iterate sources defined in Hymn file
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
					$type	= isset( $source->type ) ? $source->type : 'folder';					//  set default source type is not defined
					$this->library->addShelf( $sourceId, $source->path, $type, $active );			//  add source as shelf in library
				}
			}
		}
		return $this->library;																		//  return loaded library
	}

	protected function realizeWildcardedModuleIds( $givenModuleIds, $availableModuleIds ){
		$list	= array();
		foreach( $givenModuleIds as $givenModuleId ){
			if( !substr_count( $givenModuleId, '*' ) ){
				if( in_array( $givenModuleId, $availableModuleIds ) ){
					$list[]	= $givenModuleId;
				}
				continue;
			}
			$pattern	= str_replace( '\*', '.+', preg_quote( $givenModuleId, '/' ) );
			if( $this->flags->verbose ){
				$this->client->out( sprintf(
					'Looking for suitable modules for module group: %s ...',
					$givenModuleId
				) );
			}
			foreach( $availableModuleIds as $availableModuleId ){
				if( preg_match( '/^'.$pattern.'$/i', $availableModuleId ) ){
					if( $this->flags->verbose )
						$this->client->out( ' - found module '.$availableModuleId );
					$list[]	= $availableModuleId;
				}
			}
		}
		return $list;
	}

	/**
	 *	This method is automatically called by client dispatcher.
	 *	Commands need to implement this method.
	 *	@access		public
	 *	@return		void
	 */
	abstract function run();
}
