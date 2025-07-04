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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Config
{
	protected Hymn_Client $client;
	protected Hymn_Module_Library $library;
	protected object $flags;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library )
	{
		$this->client	= $client;
		$this->library	= $library;
		$this->flags	= (object) [
			'dry'		=> $this->client->flags & Hymn_Client::FLAG_DRY,
			'quiet'		=> $this->client->flags & Hymn_Client::FLAG_QUIET,
			'verbose'	=> $this->client->flags & Hymn_Client::FLAG_VERBOSE,
		];
	}

	public function get( string $moduleId, string $configKey ): Hymn_Structure_Module_Config
	{
		$module		= $this->library->readInstalledModule( $moduleId );
		if( array_key_exists( $configKey, $module->config ) )
			return $module->config[$configKey];
		$msg	= 'No configuration value for key "%2$s" in module "%1$s" set';					//  exception message
		throw new InvalidArgumentException( sprintf( $msg, $moduleId, $configKey ) );			//  throw exception
	}

	/**
	 *	@param		string		$moduleId
	 *	@return		array<Hymn_Structure_Module_Config>
	 */
	public function getAll( string $moduleId ): array
	{
		$module		= $this->library->readInstalledModule( $moduleId );
		return $module->config;
	}

	public function set( string $moduleId, string $configKey, mixed $configValue ): void
	{
		$this->get( $moduleId, $configKey );
		$target		= $this->client->getConfigPath().'modules/'.$moduleId.'.xml';
		$xml		= file_get_contents( $target );
		$xml		= new Hymn_Tool_XML_Element( $xml );
		foreach( $xml->config as $node ){														//  iterate original module config pairs
			$key	= (string) $node['name'];													//  shortcut config pair key
			if( $key !== $configKey )
				continue;
//			$dom = dom_import_simplexml( $node );												//  import DOM node of module file
//			$dom->nodeValue = $configValue;														//  set new value on DOM node
			$node->setValue( (string) $configValue );
			if( $this->flags->verbose && !$this->flags->quiet )									//  verbose mode is on
				$this->client->out( "  … configured ".$key );									//  inform about configures config pair
		}
		if( $this->flags->dry )
			return;
		$xml->saveXml( $target );																//  save changed DOM to module file
		Hymn_Tool_Cache_AppModules::staticInvalidate( $this->client );							//  remove modules cache file
		clearstatcache();
	}
}
