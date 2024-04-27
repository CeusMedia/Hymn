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
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
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

		$list			= [];
		$library		= $this->getLibrary();
		$nrInlineFunctions	= 0;
		foreach( $library->listInstalledModules() as $moduleId => $module ){
			foreach( $module->hooks as $resource => $events ){
				foreach( $events as $event => $functions ){
					foreach( $functions as $function ){
						if( !preg_match( '/\n/', $function->callback ) ){
							$id	= $resource.'_'.$event.'_'.$moduleId.'_'.$function->callback;
							$list[$id]	= (object) [
								'moduleId'		=> $moduleId,
								'resource'		=> $resource,
								'event'			=> $event,
								'type'			=> 'staticPublicFunctionCall',
								'function'		=> $function,
							];
						}
						else{
							$nrInlineFunctions++;
							$id	= vsprintf( '%s_%s_%s_func-%s', [
								$resource,
								$event,
								$moduleId,
								str_pad( (string) $nrInlineFunctions, 4, '0', STR_PAD_LEFT ),
							] );
							$list[$id]	= (object) [
								'moduleId'		=> $moduleId,
								'resource'		=> $resource,
								'event'			=> $event,
								'type'			=> 'inlineFunction',
								'function'		=> '<inline_function>',
							];
						}
					}
				}
			}
		}
		ksort( $list );

		foreach( $list as $hook ) {
			switch( $hook->type ){
				case 'staticPublicFunctionCall':
					$this->out( vsprintf( '- %s > %s >> [%s] %s', [
						$hook->resource,
						$hook->event,
						$hook->moduleId,
						$hook->function,
					] ) );
					break;
				case 'inlineFunction':
					$this->out( vsprintf( '- %s > %s >> [%s] %s', [
						$hook->resource,
						$hook->event,
						$hook->moduleId,
						'<inline_function> !DEPRECATED!',
					] ) );
					break;
			}
		}
	}
}
