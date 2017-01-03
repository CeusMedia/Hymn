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

	protected $library	= NULL;

	public function __construct( Hymn_Client $client ){
		$this->client = $client;
	}

	protected function ask( $message, $type = 'string', $default = NULL, $options = array(), $break = FALSE ){
		return Hymn_Client::getInput( $message, $type, $default, $options, $break );
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
		$library	= $this->getLibrary( $config );													//  try to load sources into a library
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
	 *	@param		...			$config			Hymn configuration
	 *	@param		boolean		$forceReload	Flag: reload library (optional, not default)
	 *	@return		Hymn_Module_Library			Library of available modules in found sources
	 */
	protected function getLibrary( $config, $forceReload = FALSE ){
		if( is_null( $this->library ) || $forceReload ){											//  library not loaded yet or reload is forced
			$this->library	= new Hymn_Module_Library();											//  create new module library
			if( !isset( $config->sources ) || empty( $config->sources ) ){
				$msg	= 'Warning: No sources defined in Hymn file.';								//  warning message to show
				Hymn_Client::out( sprintf( $msg, $sourceId ) );										//  output warning
				return $this->library;																//  return empty library
			}
			foreach( $config->sources as $sourceId => $source ){									//  iterate sources defined in Hymn file
				if( !isset( $source->path ) || !strlen( trim( $source->path ) ) ){					//  source path has NOT been set
					$msg	= 'Warning: No path defined for source "%s". Source has been ignored.';	//  warning message to show
					Hymn_Client::out( sprintf( $msg, $sourceId ) );									//  output warning
				}
				else if( !file_exists( $source->path ) ){											//  source path has NOT been detected
					$msg	= 'Path to source "%s" is not existing. Source has been ignored.';		//  warning message to show
					Hymn_Client::out( sprintf( $msg, $sourceId ) );									//  output warning
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

	public function help(){
		$class		= preg_replace( "/^Hymn_Command_/", "", get_class( $this ) );
		$command	= strtolower( str_replace( "_", "-", $class ) );
		$fileName	= "phar://hymn.phar/locales/en/help/".$command.".txt";
		if( file_exists( $fileName ) )
			Hymn_Client::out( file( $fileName ) );
		else{
			Hymn_Client::out( "" );																	//  print empty line as optical separator
			Hymn_Client::out( "Outch! Help on this topic is not available yet. I am sorry :-/" );
			Hymn_Client::out( "" );																	//  print empty line as optical separator
			Hymn_Client::out( "But YOU can improve this situation :-)" );
			Hymn_Client::out( "- get more information on: https://ceusmedia.de/" );
			Hymn_Client::out( "- make a fork or patch on: https://github.com/CeusMedia/Hymn" );
			Hymn_Client::out( "" );																	//  print empty line as optical separator
		}
	}

	/**
	 *	This method is automatically called by client dispatcher.
	 *	Commands need to implement this method.
	 *	@access		public
	 *	@return		void
	 */
	abstract function run();
}
