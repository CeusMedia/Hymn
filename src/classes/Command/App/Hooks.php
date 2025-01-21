<?php /** @noinspection PhpUnused */
declare(strict_types=1);

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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Hooks extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		if( !file_exists( Hymn_Client::$fileName ) )
			throw new RuntimeException( "Hymn project '".Hymn_Client::$fileName."' is missing. Please run 'hymn init'!" );

		$list		= [];
		$tree		= [];
		$library	= $this->getLibrary();
		foreach( $library->listInstalledModules() as $moduleId => $module ){
			foreach( $module->hooks as $resource => $events ){
				foreach( $events as $event => $hooks ){
					foreach( $hooks as $hook ){
						$id		= $resource.'_'.$event.'_'.$moduleId.'_'.$hook->callback;
						$dto	= (object) [
							'moduleId'		=> $moduleId,
							'resource'		=> $resource,
							'event'			=> $event,
							'callback'		=> $hook->callback,
							'level'			=> $hook->level,
						];
						$list[$id]	= $dto;
						$tree[$resource][$event][]	= $dto;
					}
				}
			}
		}
		ksort( $list );

		if( $this->flags->verbose ){
			foreach( $tree as $resource => $events ){
				$this->out( '- Resource: '.$resource );
				foreach( $events as $event => $hooks ){
					$this->out( '  - Event: '.$event );
					foreach( $hooks as $hook ){
						$this->out( '    - Module: '.$hook->moduleId );
						$this->out( '      Callback: '.$hook->callback );
						if( 5 !== $hook->level )
							$this->out( '      Level: '.$hook->level );
					}
				}
			}
		}
		else{
			foreach( $list as $hook ) {
				$this->out( vsprintf( '- %s > %s >> [%s] %s', [
					$hook->resource,
					$hook->event,
					$hook->moduleId,
					$hook->callback,
				] ) );
			}
		}
	}
}
