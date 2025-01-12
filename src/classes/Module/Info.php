<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2024 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Info
{
	protected Hymn_Client $client;

	public function __construct( Hymn_Client $client )
	{
		$this->client	= $client;
	}

	public function showModuleConfig( object $module ): void
	{
		if( !isset( $module->config ) || !count( $module->config ) )
			return;
		$this->client->out( ' - Configuration: ' );
		foreach( $module->config as $item ){
			$this->client->out( '    - '.$item->key.':' );
			if( '' !== trim( $item->title ?? '' ) )
				$this->client->out( '       - Title:     '.trim( $item->title ) );
			$this->client->out( '       - Type:      '.( $item->type ?? '?' ) );
			$this->client->out( '       - Value:     '.( $item->value ?? '?' ) );
			if( $item->values )
				$this->client->out( '       - Values:    '.join( ', ', $item->values ) );
			$this->client->out( '       - Mandatory: '.( $item->mandatory ? 'yes' : 'no' ) );
			$this->client->out( '       - Protected: '.$item->protected );
		}
	}

	public function showModuleFiles( Hymn_Structure_Module $module ): void
	{
		$list	= [];
		if( isset( $module->files ) ){
			foreach( $module->files->toArray() as $sectionKey => $sectionFiles ){
				if( !count( $sectionFiles ) )
				continue;
				$list[]	= '    - '.ucfirst( $sectionKey );
				foreach( $sectionFiles as $file ){
					$line	= $file->file;
					$attr	= [];
					if( 'styles' === $sectionKey ){
						if( !empty( $file->source ) )
						$attr['source']	= $file->source;
						if( !empty( $file->load ) )
						$attr['load']	= $file->load;
					}
					else if( 'images' === $sectionKey ){
						if( !empty( $file->source ) )
						$attr['source']	= $file->source;
					}
					if( count( $attr ) ){
						foreach( $attr as $key => $value )
						$attr[$key]	= '@'.$key.': '.$value;
						$line .= ' ('.join( ', ', $attr ).')';
					}
					$list[]	= '       - '.$line;
				}
			}
		}
		if( $list ){
			$this->client->out( ' - Included files: ' );
			foreach( $list as $line ){
				$this->client->out( $line );
			}
		}
	}

	public function showModuleHook( Hymn_Structure_Module $module ): void
	{
		if( !isset( $module->hooks) || !count( $module->hooks ) )
			return;
		$this->client->out( ' - Hooks: ' );
		foreach( $module->hooks as $resource => $events ){
			foreach( $events as $event => $hooks ){
				foreach( $hooks as $hook ){
					if( !preg_match( '/\n/', $hook->callback ) )
						$this->client->out( '    - '.$resource.' > '.$event.' >> '.$hook->callback );
					else
						$this->client->out( '    - '.$resource.' > '.$event.' >> <func> !DEPRECATED!' );
				}
			}
		}
	}

	public function showModuleRelations( Hymn_Module_Library $library, Hymn_Structure_Module $module ): void
	{
		$module->relations->requiredBy	= [];
		foreach( $library->listInstalledModules() as $moduleId => $installedModule )
			if( array_key_exists( $moduleId, $installedModule->relations->needs ) )
				if( Hymn_Structure_Module_Relation::TYPE_MODULE === $installedModule->relations->needs[$module->id]->type )
					$module->relations->requiredBy[$installedModule->id]	= $installedModule;

		$module->relations->neededBy	= [];
		foreach( $library->getAvailableModules() as $moduleId => $availableModule )
			if( array_key_exists( $moduleId, $availableModule->relations->needs ) )
				if( Hymn_Structure_Module_Relation::TYPE_MODULE === $availableModule->relations->needs[$moduleId]->type )
					$module->relations->neededBy[$availableModule->id]	= $availableModule;

		if( count( $module->relations->needs ) ){
			$this->client->out( ' - Modules needed: ' );
			foreach( $module->relations->needs as $moduleId => $relation )
				$this->client->out( '    - '.ucfirst( $this->getRelationTypeLabel( $relation->type ) ).': '.$moduleId );
		}
		if( count( $module->relations->supports ) ){
			$this->client->out( ' - Modules supported: ' );
			foreach( $module->relations->supports as $moduleId => $relation )
				$this->client->out( '    - '.ucfirst( $this->getRelationTypeLabel( $relation->type ) ).': '.$moduleId );
		}
		if( count( $module->relations->neededBy ) ){
			$this->client->out( ' - Modules needing: ' );
			foreach( $module->relations->neededBy as $moduleId => $module )
				$this->client->out( '    - '.$moduleId );
		}
		if( count( $module->relations->requiredBy ) ){
			$this->client->out( ' - Modules requiring: ' );
			foreach( $module->relations->requiredBy as $moduleId => $module )
				$this->client->out( '    - '.$moduleId );
		}
	}

	public function showModuleVersions( Hymn_Structure_Module $module ): void
	{
		if( !count( $module->version->log ) )
			return;
		$this->client->out( ' - Versions: ' );
		/** @var object{note: string, version: string} $item */
	  foreach( $module->version->log as $item )
			$this->client->out( '    - '.str_pad( $item->version, 10 ).' '.$item->note );
	}

	protected function getRelationTypeLabel( int $type ): string
	{
		return match( $type ){
			Hymn_Structure_Module_Relation::TYPE_MODULE		=> 'module',
			Hymn_Structure_Module_Relation::TYPE_PACKAGE	=> 'package',
			default											=> 'unknown',
		};
	}
}
