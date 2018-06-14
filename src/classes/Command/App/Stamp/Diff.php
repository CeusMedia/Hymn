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
class Hymn_Command_App_Stamp_Diff extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$pathName	= $this->client->arguments->getArgument( 0 );
		$shelfId	= $this->client->arguments->getArgument( 1 );
		$type		= $this->client->arguments->getArgument( 2 );
		$shelfId	= $this->evaluateShelfId( $shelfId );
		$stamp		= $this->getStamp( $pathName, $shelfId );
		$modules	= $this->getAvailableModules( $shelfId );					//  load available modules

		/*  --  FIND MODULE CHANGES  --  */
		$changes	= array();
		foreach( $modules as $module ){
			if( !isset( $stamp->modules->{$module->id} ) ){
				if( !$this->flags->quiet )
					Hymn_Client::out( 'Module '.$module->id.' was not installed before.' );
			}
			else{
				$oldModule	= $stamp->modules->{$module->id};
				if( !version_compare( $oldModule->version, $module->version, '<' ) )
					continue;
				$changes[$module->id]	= (object) array(
					'type'		=> '...',
					'source'	=> $oldModule,
					'target'	=> $module,
				);
			}
		}
		if( !$changes ){
			if( !$this->flags->quiet )
				Hymn_Client::out( 'No modules have changed.' );
			return;
		}
		if( !$this->flags->quiet )
			Hymn_Client::out( 'Found '.count( $changes ).' modules have changed:' );

		$helperSql	= new Hymn_Module_SQL( $this->client );
		foreach( $changes as $change ){
			if( !$this->flags->quiet )
				Hymn_Client::out( ' - Module: '.$change->target->id );
			$moduleOld		= $change->source;
			$moduleNew		= $change->target;
			if( in_array( $type, array( NULL, 'all', 'sql' ) ) ){
				$sql	= $scripts = $helperSql->getModuleUpdateSql( $change->source, $moduleNew );
				if( $sql ){
					if( !$this->flags->quiet )
						Hymn_Client::out( '   SQL: '.count( $scripts ).' updates:' );
					if( $this->flags->verbose )
						Hymn_Client::out( '--  UPDATE '.strtoupper( $moduleNew->id ).'  --' );
					$version	= $change->source->version;
					foreach( $scripts as $script ){
						$query	= $helperSql->realizeTablePrefix( $script->sql );
						if( $this->flags->verbose ){
							Hymn_Client::out( sprintf(
								'--  UPDATE %s: '.$version.' -> '.$script->version.'',
								strtoupper( $moduleNew->id )
							) );
						}
						Hymn_Client::out( trim( $query ) );
						$version	= $script->version;
					}
				}
			}
			if( in_array( $type, array( NULL, 'all', 'config' ) ) ){
				$moduleConfigOld	= (object) $moduleOld->config;
				$moduleConfigNew	= (object) $moduleNew->config;
				foreach( $moduleConfigOld as $item ){
					if( !isset( $moduleConfigNew->{$item->key} ) ){
						Hymn_Client::out( '   - '.$item->key.' has been removed.' );
					}
				}
				foreach( $moduleConfigNew as $item ){
					if( in_array( $item->key, array( 'title', 'values' ) ) )
						continue;
					if( !isset( $moduleConfigOld->{$item->key} ) ){
						Hymn_Client::out( '   - '.$item->key.' has beend added with default value: '.$item->value );
					}
					else if( $item != $moduleConfigOld->{$item->key} ){
						foreach( $item as $property => $value ){
							$propValOld	= $moduleConfigOld->{$item->key}->{$property};
							$propValNew	= $item->{$property};
							if( $propValOld !== $propValNew ){
								Hymn_Client::out( '   - '.$item->key.': '.$property.' has changed from '.$propValOld.' to '.$propValNew );
							}
						}
					}
				}
			}
		}
	}

	protected function getAvailableModules( $shelfId = NULL ){
		$library	= $this->getLibrary();
		$modules	= $library->listInstalledModules( $shelfId );
		if( $this->flags->verbose && !$this->flags->quiet ){
			$message	= 'Found '.count( $modules ).' installed modules in source '.$shelfId.'.';
			if( !$shelfId )
				$message	= 'Found '.count( $modules ).' installed modules.';
			Hymn_Client::out( $message );
		}
		return $modules;
	}

	protected function getLatestStamp( $path = NULL, $shelfId = NULL ){
		$pathDump	= $this->client->getConfigPath().'dumps/';
		$path		= $path ? $path : $pathDump;
		if( $this->flags->verbose && !$this->flags->quiet )
			Hymn_Client::out( "Scanning folder ".$path."..." );
		if( file_exists( $path ) ){
			$list	= array();
			$index	= new DirectoryIterator( $path );
			foreach( $index as $entry ){
				if( $entry->isDir() || $entry->isDot() )
					continue;
				$pattern	= '/^stamp_.[0-9:_-]+\.json$/';
				if( $shelfId )
					$pattern	= '/^stamp_'.preg_quote( $shelfId, '/' ).'_[0-9:_-]+\.json$/';
				if( !preg_match( $pattern, $entry->getFilename() ) )
					continue;
				$key		= str_replace( array( '_', '-' ), '_', $entry->getFilename() );
				$list[$key]	= $entry->getFilename();
			}
			krsort( $list );
			if( $list ){
				return $path.array_shift( $list );
			}
		}
		return NULL;
	}

	protected function getStamp( $pathName, $shelfId ){
		if( $pathName && file_exists( $pathName ) ){
			$fileName	= $pathName;
			if( is_dir( $pathName ) )
				$fileName	= $this->getLatestStamp( $pathName, $shelfId );
		}
		else
			$fileName		= $this->getLatestStamp( NULL, $shelfId );
		if( !( $fileName && file_exists( $fileName ) ) ){
			if( !$this->flags->quiet )
				Hymn_Client::out( "No comparable stamp file found." );
			exit( 0 );
		}
		if( $this->flags->verbose && !$this->flags->quiet )
			Hymn_Client::out( 'Loading stamp: '.$fileName );
		return json_decode( trim( file_get_contents( $fileName ) ) );
	}
}
