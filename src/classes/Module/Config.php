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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Config{

	protected $client;
	protected $config;
	protected $library;
	protected $quiet;
	protected $isLiveCopy	= FALSE;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library, $quiet = FALSE ){
		$this->client	= $client;
		$this->config	= $this->client->getConfig();
		$this->library	= $library;
		$this->quiet	= $quiet;
		$this->app		= $this->config->application;												//  shortcut to application config
	}

	public function get( $moduleId, $configKey, $verbose = FALSE ){
		$module		= $this->library->readInstalledModule( $this->app->uri, $moduleId );
		if( array_key_exists( $configKey, $module->config ) )
			return $module->config[$configKey];
		$msg	= 'No configuration value for key "%2$s" in module "%1$s" set';						//  exception message
		throw new InvalidArgumentException( sprintf( $msg, $moduleId, $configKey ) );				//  throw exception
	}

	public function set( $moduleId, $configKey, $configValue, $verbose = FALSE, $dry = FALSE ){
		$this->get( $moduleId, $configKey, FALSE );

		$target	= $this->app->uri.'config/modules/'.$moduleId.'.xml';
		$xml	= file_get_contents( $target );
		$xml	= new Hymn_Tool_XmlElement( $xml );
		foreach( $xml->config as $nr => $node ){													//  iterate original module config pairs
			$key	= (string) $node['name'];														//  shortcut config pair key
			if( $key !== $configKey )
				continue;
			$dom = dom_import_simplexml( $node );													//  import DOM node of module file
			$dom->nodeValue = $configValue;															//  set new value on DOM node
			if( $verbose && !$this->quiet )															//  verbose mode is on
				Hymn_Client::out( "  … configured ".$key );											//  inform about configures config pair
		}
		if( $dry )
			return;
		$xml->saveXml( $target );																	//  save changed DOM to module file
		@unlink( $this->app->uri.'config/modules.cache.serial' );			 						//  remove modules cache file
		clearstatcache();
	}
}
?>
