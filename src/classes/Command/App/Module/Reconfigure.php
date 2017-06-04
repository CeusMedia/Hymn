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
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Module_Reconfigure extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	protected $installType	= "link";
	protected $force		= FALSE;
	protected $verbose		= FALSE;
	protected $quiet		= FALSE;

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$this->dry		= $this->client->arguments->getOption( 'dry' );
		$this->force	= $this->client->arguments->getOption( 'force' );
		$this->quiet	= $this->client->arguments->getOption( 'quiet' );
		$this->verbose	= $this->client->arguments->getOption( 'verbose' );

		if( $this->dry )
			Hymn_Client::out( "## DRY RUN: Simulated actions - no changes will take place." );

		$config			= $this->client->getConfig();
		$library		= $this->getLibrary( $config );
		$moduleId		= trim( $this->client->arguments->getArgument() );
		$listInstalled	= $library->listInstalledModules( $config->application->uri );
		if( !$moduleId )
			Hymn_Client::out( "No module id given" );
		else if( !array_key_exists( $moduleId, $listInstalled ) )
			Hymn_Client::out( "Module '".$moduleId."' is not installed" );
		else{
			$values				= array();
			$moduleInstalled	= $listInstalled[$moduleId];
			$moduleSource		= $library->getModule( $moduleId, $moduleInstalled->installSource, FALSE );
			if( !$moduleSource )
				Hymn_Client::out( "Module '".$moduleId."' is not available" );
			else{
				foreach( $moduleSource->config as $configKey => $configData ){
					if( !isset( $moduleInstalled->config[$configKey] ) )
						continue;
					$installed	= $moduleInstalled->config[$configKey];
					if( $configData->value === $installed->value )
						continue;

					Hymn_Client::out( '- Config key "'.$configKey.'" differs from source: '.$configData->value.' <-> '.$installed->value );
					$answer		= Hymn_Tool_Decision::askStatic( "Keep custom value?" );
					if( $answer !== "y" )
						continue;
					$values[$configKey]	= $configData->value;
				}
			}
			$target		= $config->application->uri.'config/modules/'.$moduleId.'.xml';
			$installer	= new Hymn_Module_Installer( $this->client, $library );
			if( !$this->dry )
				$installer->configure( $moduleSource, $this->verbose, $this->dry );

			if( $values ){
				$xml	= file_get_contents( $target );
				$xml	= new Hymn_Tool_XmlElement( $xml );
				foreach( $xml->config as $nr => $node ){											//  iterate original module config pairs
					$key	= (string) $node['name'];												//  shortcut config pair key
					if( !array_key_exists( $key, $values ) )
						continue;
					$dom = dom_import_simplexml( $node );											//  import DOM node of module file
					$dom->nodeValue = $values[$key];												//  set new value on DOM node
					if( $this->verbose && !$this->quiet )											//  verbose mode is on
						Hymn_Client::out( "  … configured ".$key );									//  inform about configures config pair
				}
				if( !$this->dry ){
					$xml->saveXml( $target );														//  save changed DOM to module file
					@unlink( $config->application->uri.'config/modules.cache.serial' );			 	//  remove modules cache file
					clearstatcache();

				}
			}
		}
	}
}
