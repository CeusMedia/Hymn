<?php
/**
 *	...
 *
 *	Copyright (c) 2017-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Stamp_Dump extends Hymn_Command_Abstract implements Hymn_Command_Interface
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
//		$key	= $this->client->arguments->getArgument();
		$library	= $this->getLibrary();
		$pathDump	= $this->client->getConfigPath().'dumps/';
		$shelfId	= $this->client->arguments->getArgument();
		$shelfId	= $this->evaluateShelfId( $shelfId );
		$datetime	= date( 'Y-m-d_H:i:s' );

		if( $shelfId ){
			$modules	= $library->listInstalledModules( $shelfId );
			$fileName	= $pathDump.'stamp_'.$shelfId.'_'.$datetime.'.json';
			$this->out( count( $modules )." modules of source ".$shelfId." installed:" );
		}
		else{
			$modules	= $library->listInstalledModules();
			$fileName	= $pathDump.'stamp_'.$datetime.'.json';
			$this->out( count( $modules )." modules installed:" );
		}
		if( dirname( $fileName) )																//  path is not existing
			exec( "mkdir -p ".dirname( $fileName ) );											//  create path

		ksort( $modules );
		$data	= (object) ['modules' => []];
		foreach( $modules as $module ){
			unset( $module->versionAvailable );
			unset( $module->versionInstalled );
			unset( $module->versionLog );
			unset( $module->isInstalled );
			unset( $module->versionInstalled );
			$data->modules[$module->id]	= $module;
		}
		file_put_contents( $fileName, json_encode( $data, JSON_PRETTY_PRINT ) );

		$this->out( 'Saved app stamp to '.$fileName.'.' );
	}
}
