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
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
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
	public function run()
	{
		$pathConfig	= $this->client->getConfigPath();
		$fileName	= Hymn_Client::$fileName;
		if( !file_exists( $pathConfig."modules" ) )
			return $this->client->out( "No modules installed" );

		$hymnFile		= json_decode( file_get_contents( $fileName ) );
		$knownModules	= array_keys( (array) $hymnFile->modules );

		$index	= new DirectoryIterator( $pathConfig."modules" );
		$reader	= new Hymn_Module_Reader();
		$list	= array();
		foreach( $index as $entry ){
			if( $entry->isDir() || $entry->isDot() )
				continue;
			if( !preg_match( "/\.xml$/", $entry->getFilename() ) )
				continue;
			$id		= pathinfo( $entry->getFilename(), PATHINFO_FILENAME );
			$module	= $reader->load( $entry->getPathname(), $id );
			if( $module->config || in_array( $id, $knownModules ) ){
				$list[$id]	= array( 'config' => (object) array() );
				foreach( $module->config as $pair )
					$list[$id]['config']->{$pair->key}	= $pair->value;
			}
		}
		ksort( $list );
		$hymnFile->modules	= $list;
		file_put_contents( $fileName, json_encode( $hymnFile, JSON_PRETTY_PRINT ) );
		return $this->client->out( "Configuration dumped to ".$fileName );
	}
}
