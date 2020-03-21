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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2019 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Info{

	protected $client;

	public function __construct( $client ){
		$this->client	= $client;
	}

	public function showModuleConfig( $module ){
		if( !count( $module->config ) )
			return;
		$this->client->out( ' - Configuration: ' );
		foreach( $module->config as $item ){
			$this->client->out( '    - '.$item->key.':' );
			if( strlen( trim( $item->title ) ) )
				$this->client->out( '       - Title:     '.trim( $item->title ) );
			$this->client->out( '       - Type:      '.$item->type );
			$this->client->out( '       - Value:     '.$item->value );
			if( $item->values )
				$this->client->out( '       - Values:    '.join( ', ', $item->values ) );
			$this->client->out( '       - Mandatory: '.( $item->mandatory ? 'yes' : 'no' ) );
			$this->client->out( '       - Protected: '.$item->protected );
		}
	}

	public function showModuleFiles( $module ){
		$list	= array();
		foreach( $module->files as $sectionKey => $sectionFiles ){
			if( !count( $sectionFiles ) )
				continue;
			$list[]	= '    - '.ucfirst( $sectionKey );
			foreach( $sectionFiles as $file ){
				$line	= $file->file;
				$attr	= array();
				if( $file->type === 'style' ){
					if( !empty( $file->source ) )
						$attr['source']	= $file->source;
					if( !empty( $file->load ) )
						$attr['load']	= $file->load;
				}
				if( $file->type === 'image' ){
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
		if( $list ){
			$this->client->out( ' - Included files: ' );
			foreach( $list as $line ){
				$this->client->out( $line );
			}
		}
	}

	public function showModuleHook( $module ){
		if( !count( $module->hooks ) )
			return;
		$this->client->out( ' - Hooks: ' );
		foreach( $module->hooks as $resource => $events ){
			foreach( $events as $event => $functions ){
				foreach( $functions as $function ){
					if( !preg_match( '/\n/', $function ) )
						$this->client->out( '    - '.$resource.' > '.$event.' >> '.$function );
					else
						$this->client->out( '    - '.$resource.' > '.$event.' >> <func> !DEPRECATED!' );
				}
			}
		}
	}

	public function showModuleRelations( Hymn_Module_Library $library, $module ){
		$module->relations->neededBy	= array();
		foreach( $library->listInstalledModules() as $installedModule )
			if( array_key_exists( $module->id, $installedModule->relations->needs ) )
				$module->relations->neededBy[$installedModule->id]	= $installedModule;

		if( count( (array) $module->relations->needs ) ){
			$this->client->out( ' - Modules needed: ' );
			foreach( $module->relations->needs as $moduleId => $relation )
				$this->client->out( '    - '.$moduleId );
		}
		if( count( (array) $module->relations->supports ) ){
			$this->client->out( ' - Modules supported: ' );
			foreach( $module->relations->supports as $moduleId => $relation )
				$this->client->out( '    - '.$moduleId );
		}
		if( count( $module->relations->neededBy ) ){
			$this->client->out( ' - Modules related: ' );
			foreach( $module->relations->neededBy as $moduleId => $relation )
				$this->client->out( '    - '.$moduleId );
		}
	}

	public function showModuleVersions( $module ){
		if( !count( $module->versionLog ) )
			return;
		$this->client->out( ' - Versions: ' );
		foreach( $module->versionLog as $item )
			$this->client->out( '    - '.str_pad( $item->version, 10, ' ', STR_PAD_RIGHT ).' '.$item->note );
	}
}
