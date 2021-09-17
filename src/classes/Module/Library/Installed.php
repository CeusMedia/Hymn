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
 *	@package		CeusMedia.Hymn.Module.Library
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module.Library
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Library_Installed
{
	protected $client;

	public function __construct( Hymn_Client $client )
	{
		$this->client		= $client;
	}

	public function get( string $moduleId )
	{
		$pathModules	= $this->client->getConfigPath().'modules/';
		$filename		= $pathModules.$moduleId.'.xml';
		if( !file_exists( $filename ) )
			throw new RangeException( 'Module "'.$moduleId.'" not installed in '.$pathModules );
		$reader			= new Hymn_Module_Reader();
		return $reader->load( $filename, $moduleId );
	}

	public function getAll( string $shelfId = NULL ): array
	{
//		if( self::$useCache && self::$listModulesInstalled !== NULL )			//  @todo realize shelves in cache
//			return self::$listModulesInstalled;									//  @todo realize shelves in cache
		$list			= array();
		$pathModules	= $this->client->getConfigPath().'modules/';
		if( file_exists( $pathModules ) ){
			$iterator	= new RecursiveDirectoryIterator( realpath( $pathModules ) );
			$index		= new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );
			foreach( $index as $entry ){
				if( !$entry->isFile() || !preg_match( "/\.xml$/", $entry->getFilename() ) )
					continue;
				$key	= pathinfo( $entry->getFilename(), PATHINFO_FILENAME );
				$module	= $this->get( $key );
				if( !$shelfId || $module->installSource === $shelfId )
					$list[$key]	= $module;
			}
		}
		ksort( $list );
//		self::$listModulesInstalled	= $list;									//  @todo realize shelves in cache
		return $list;
	}

	public function has( string $moduleId, string $shelfId = NULL )
	{
		$list	= $this->getAll( $shelfId );
		return array_key_exists( $moduleId, $list );
	}
}
