<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2019 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Info extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		if( !file_exists( Hymn_Client::$fileName ) )
			throw new RuntimeException( "Hymn project '".Hymn_Client::$fileName."' is missing. Please run 'hymn init'!" );

		$config		= $this->client->getConfig();
		$moduleId	= $this->client->arguments->getArgument( 0 );
//		$shelfId	= $this->client->arguments->getArgument( 1 );
//		$shelfId	= $this->evaluateShelfId( $shelfId );

		if( $moduleId ){
			$library			= $this->getLibrary();
			$modulesAvailable	= $library->getModules();
			$modulesInstalled	= $library->listInstalledModules();								//  get list of installed modules
			foreach( $modulesAvailable as $availableModule ){
				if( $moduleId !== $availableModule->id )
					continue;
				$this->client->out( 'Module: '.$availableModule->title );
				if( $availableModule->description )
					$this->client->out( $availableModule->description );
				$this->client->out( 'Category: '.$availableModule->category );
				$this->client->out( 'Source: '.$availableModule->sourceId );
				$this->client->out( 'Version: '.$availableModule->version );
				if( array_key_exists( $moduleId, $modulesInstalled ) ){
					$installedModule	= $modulesInstalled[$moduleId];
					$this->client->out( 'Installed: '.$installedModule->version );
					if( version_compare( $availableModule->version, $installedModule->version, '>' ) ){
						$message	= 'Update available: %s -> %s';
						$message	= sprintf( $message, $installedModule->version, $availableModule->version );
						$this->client->out( $message );
					}
				}
				return;
			}
			$this->client->out( 'Module '.$moduleId.' not available.' );
			return;
		}
		$this->client->out( "Application Settings:" );
		foreach( $config->application as $key => $value ){
			if( is_object( $value ) )
				$value	= json_encode( $value, JSON_PRETTY_PRINT );
			$this->client->out( "- ".$key." => ".$value );
		}
	}
}
