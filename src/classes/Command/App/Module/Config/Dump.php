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
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Module_Config_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: dry, quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		$pathConfig	= $this->client->getConfigPath();
		if( !file_exists( $pathConfig."modules" ) )
			$this->outError( "No modules installed", Hymn_Client::EXIT_ON_SETUP );

		$hymnFile		= Hymn_Tool_ConfigFile::read( Hymn_Client::$fileName );
		/** @var string[] $knownModules */
		$knownModules	= array_keys( $hymnFile->modules );

		$index	= new DirectoryIterator( $pathConfig."modules" );
		$list	= [];
		foreach( $index as $entry ){
			if( $entry->isDir()
				|| $entry->isDot()
				|| !str_ends_with( $entry->getFilename(), '.xml' ) )								//  read XML files, only
				continue;
			$id		= pathinfo( $entry->getFilename(), PATHINFO_FILENAME );					//  extract module name from file name
			$module	= Hymn_Module_Reader2::load( $entry->getPathname(), $id );
			if( $module->config || in_array( $id, $knownModules, TRUE ) ){
				$list[$id]	= new Hymn_Structure_Config_Module();
				foreach( $module->config as $pair )
					$list[$id]->config[$pair->key]	= $pair->value;
			}
		}
		ksort( $list );
		$hymnFile->modules	= $list;
		Hymn_Tool_ConfigFile::save( $hymnFile, Hymn_Client::$fileName );
		$this->out( "Configuration dumped to ".Hymn_Client::$fileName );
	}
}
