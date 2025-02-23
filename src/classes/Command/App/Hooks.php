<?php /** @noinspection PhpUnused */
declare(strict_types=1);

/**
 *	Lists found hooks in installed modules.
 *	Prints tree in verbose mode.
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
 *	Lists found hooks in installed modules.
 *	Prints tree in verbose mode.
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Command_App_Hooks extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	/**
	 *	Execute this command.
	 *	Implements flags: verbose
	 *	@access		public
	 *	@return		void
	 */
	public function run(): void
	{
		if( !file_exists( Hymn_Client::$fileName ) )
			throw new RuntimeException( vsprintf( "Hymn project '%s' is missing. Please run 'hymn init'!", [
				Hymn_Client::$fileName
			] ) );

		$tree	= $this->collectHooksFromInstalledModules();
		if( $this->flags->verbose ){
			$this->printTree( $tree );
			return;
		}
		foreach( $this->reduceTreeToList( $tree ) as $hook ) {
			$this->out( vsprintf( '- %s > %s >> [%s] %s', [
				$hook->resource,
				$hook->event,
				$hook->moduleId,
				$hook->callback,
			] ) );
		}
	}

	//  --  PROTECTED  --  //

	/**
	 *	Traverses installed modules to collect hook information.
	 *	Use ::printTree to display this tree.
	 *	Use ::reduceTreeToList to create a simple list of collected hook information.
	 *
	 *	@return		array<string,array<string,Hymn_Structure_ModuleHook[]>>
	 */
	protected function collectHooksFromInstalledModules(): array
	{
		$tree	= [];
		foreach( $this->getLibrary()->listInstalledModules() as $moduleId => $module )
			foreach( $module->hooks as $resource => $events )
				foreach( $events as $event => $hooks )
					foreach( $hooks as $hook )
						$tree[$resource][$event][]	= new Hymn_Structure_ModuleHook(
							$moduleId,
							$resource,
							$event,
							$hook->callback,
							$hook->level,
						);
		return $tree;
	}

	/**
	 *	@param		array<string,array<string,Hymn_Structure_ModuleHook[]>>		$tree
	 *	@return		void
	 */
	protected function printTree( array $tree ): void
	{
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

	/**
	 *	Reduces tree of collected module hooks to a list of module hook information.
	 *	@param		array<string,array<string,Hymn_Structure_ModuleHook[]>>		$tree
	 *	@return		array<string,Hymn_Structure_ModuleHook>
	 */
	protected function reduceTreeToList( array $tree ): array
	{
		$list		= [];
		foreach( $tree as $resource => $events )
			foreach( $events as $event => $hooks )
				foreach( $hooks as $hook )
					$list[$resource.'_'.$event.'_'.$hook->moduleId.'_'.$hook->callback]	= $hook;
		ksort( $list );
		return $list;
	}
}
