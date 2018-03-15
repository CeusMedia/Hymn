<?php
/**
 *	...
 *
 *	Copyright (c) 2017-2018 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Stamp_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
//		$key	= $this->client->arguments->getArgument( 0 );
//		$pathConfig	= $this->client->getConfigPath();
		$library	= $this->getLibrary();
		$shelfId	= $this->client->arguments->getArgument( 0 );
		$shelfId	= $this->evaluateShelfId( $shelfId );

		if( $shelfId ){
			$modules	= $library->listInstalledModules( $shelfId );
			$fileName	= '.stamp_'.$shelfId.'_'.date( 'Y-m-d_H:i:s' );
			Hymn_Client::out( count( $modules )." modules of shelf ".$shelfId." installed:" );
		}
		else{
			$modules	= $library->listInstalledModules();
			$fileName	= '.stamp_'.date( 'Y-m-d_H:i:s' );
			Hymn_Client::out( count( $modules )." modules installed:" );
		}
		ksort( $modules );
		$data	= (object) array( 'modules' => array() );
		foreach( $modules as $module ){
			unset( $module->versionAvailable );
			unset( $module->versionInstalled );
			unset( $module->versionLog );
			unset( $module->isInstalled );
			unset( $module->versionInstalled );
			$data->modules[$module->id]	= $module;
		}
		file_put_contents( $fileName, json_encode( $data/*, JSON_PRETTY_PRINT*/ ) );

		Hymn_Client::out( 'Saved app stamp to '.$fileName.'.' );
	}
}
