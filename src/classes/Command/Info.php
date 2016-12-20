<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Info extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		if( !file_exists( Hymn_Client::$fileName ) )
			throw new RuntimeException( "Hymn project '".Hymn_Client::$fileName."' is missing. Please run 'hymn init'!" );

		$config		= $this->client->getConfig();
		$moduleId	= $this->client->arguments->getArgument( 0 );
//		$shelfId	= $this->client->arguments->getArgument( 1 );

		if( $moduleId ){
			$library	= new Hymn_Module_Library();
			foreach( $config->sources as $sourceId => $source ){
				$active	= !isset( $source->active ) || $source->active;
				$library->addShelf( $sourceId, $source->path, $active );
			}
			$modulesAvailable	= $library->getModules();
			$modulesInstalled	= $library->listInstalledModules( $config->application->uri );		//  get list of installed modules
			foreach( $modulesAvailable as $availableModule ){
				if( $moduleId !== $availableModule->id )
					continue;
				Hymn_Client::out( 'Module: '.$availableModule->title );
				if( $availableModule->description )
					Hymn_Client::out( $availableModule->description );
				Hymn_Client::out( 'Category: '.$availableModule->category );
				Hymn_Client::out( 'Source: '.$availableModule->sourceId );
				Hymn_Client::out( 'Version: '.$availableModule->version );
				if( array_key_exists( $moduleId, $modulesInstalled ) ){
					$installedModule	= $modulesInstalled[$moduleId];
					Hymn_Client::out( 'Installed: '.$installedModule->version );
					if( version_compare( $availableModule->version, $installedModule->version, '>' ) ){
						$message	= 'Update available: %s -> %s';
						$message	= sprintf( $message, $installedModule->version, $availableModule->version );
						Hymn_Client::out( $message );
					}
				}
				return;
			}
			Hymn_Client::out( 'Module '.$moduleId.' not available.' );
		}
		else{
			Hymn_Client::out( "Application Settings:" );
			foreach( $config->application as $key => $value ){
				if( is_object( $value ) )
					$value	= json_encode( $value, JSON_PRETTY_PRINT );
				Hymn_Client::out( "- ".$key." => ".$value );
			}
		}
	}
}
