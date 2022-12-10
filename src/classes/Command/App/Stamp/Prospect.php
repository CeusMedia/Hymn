<?php
/**
 *	...
 *
 *	Copyright (c) 2017-2022 Christian Würker (ceusmedia.de)
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
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Base.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2017-2022 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_App_Stamp_Prospect extends Hymn_Command_Abstract implements Hymn_Command_Interface
{
	const CODE_NONE					= 0;
	const CODE_MODULES_OUTDATED		= 1;

	/**
	 *	Execute this command.
	 *	Implements flags:
	 *	Missing flags: quiet, verbose
	 *	@todo		implement missing flags
	 *	@access		public
	 *	@return		int
	 */
	public function run(): int
	{
		$type		= $this->client->arguments->getArgument();
		$shelfId	= $this->client->arguments->getArgument( 1 );
		$shelfId	= $this->evaluateShelfId( $shelfId );

		$listInstalled	= $this->getLibrary()->listInstalledModules( $shelfId );					//  get list of installed modules
		if( !$listInstalled ){																		//  application has no installed modules
			$message	= 'No installed modules found.';
			if( $shelfId )
				$message	= 'No modules installed from source "'.$shelfId.'".';
			$this->client->out( $message );															//  quit with message
			return static::CODE_NONE;
		}
		else{
			$message	= 'Found '.count( $listInstalled ).' installed modules.';
			if( $shelfId )
				$message	= 'Found '.count( $listInstalled ).' installed modules in source "'.$shelfId.'".';
			$this->client->outVerbose( $message );
		}

		$moduleUpdater		= new Hymn_Module_Updater( $this->client, $this->getLibrary() );		//  use module updater on current application installation
		$outdatedModules	= $moduleUpdater->getUpdatableModules( $shelfId );						//  get list of outdated modules
		if( !$outdatedModules ){																	//  application has no installed modules
			$message	= 'No installed modules are outdated.';
			if( $shelfId )
				$message	= 'No installed modules from source "'.$shelfId.'" are outdated.';
			$this->client->out( $message );								//  quit with message
			return static::CODE_NONE;
		}
		else{
			$message	= 'Found '.count( $outdatedModules ).' outdated modules.';
			if( $shelfId )
				$message	= 'Found '.count( $outdatedModules ).' outdated modules from source "'.$shelfId.'".';
			$this->client->outVerbose( $message );
		}

		$diff	= new Hymn_Module_Diff( $this->client, $this->library );
		foreach( $outdatedModules as $outdatedModule ){
			$sourceModule	= $listInstalled[$outdatedModule->id];
			$targetModule	= $this->getLibrary()->getAvailableModule( $outdatedModule->id );
			if( in_array( $type, [NULL, 'all', 'sql', 'db', 'database'] ) ){
				if( ( $scripts = $diff->compareSqlByModules( $sourceModule, $targetModule ) ) ){
					$this->client->outVerbose( vsprintf( ' - Module: %s: v%s -> v%s (%d update(s))', array(
						$targetModule->id,
						$sourceModule->version,
						$targetModule->version,
						count( $scripts ),
					) ) );
					$version	= $sourceModule->version;
					foreach( $scripts as $script ){
						$this->client->outVerbose( vsprintf( '--  UPDATE %s: %s-> %s', array(
							$targetModule->id,
							$version,
							$script->version
						) ) );
						$this->client->out( trim( $script->query ) );
						$version	= $script->version;
					}
					$this->client->outVerbose( '' );
				}
			}
			else if( in_array( $type, [NULL, 'all', 'config'] ) ){
				$changes	= $keys = $diff->compareConfigByModules( $sourceModule, $targetModule );
				if( $changes ){
					if( !$this->flags->quiet )
						$this->client->out( ' - Module: '.$targetModule->id );
					foreach( $changes as $change ){
						if( $change->status === 'removed' ){
							$message	= '   [-] %s (was: %s)';
							$this->client->out( vsprintf( $message, array(
								$change->key,
								$this->formatValue( $change->value, $change->type ),
							) ) );
						}
						else if( $change->status === 'added' ){
							$message	= '   [+] %s (value: %s)';
							$this->client->out( vsprintf( $message, array(
								$change->key,
								$this->formatValue( $change->value, $change->type ),
							) ) );
						}
						else if( $change->status === 'changed' ){
							foreach( $change->properties as $property ){
								if( $property->key !== 'value' )
									continue;
								$message	= '   [$] %1$s => %3$s (was: %2$s)';
								$this->client->out( vsprintf( $message, array(
									$change->key,
									$this->formatValue( $property->valueOld, $change->type ),
									$this->formatValue( $property->valueNew, $change->type ),
								) ) );
								break;
							}
						}
					}
				}
			}
			else {
				$this->client->out( 'No valid type given (try: config or sql)' );
				return static::CODE_NONE;
			}
		}
		return static::CODE_MODULES_OUTDATED;
	}

	protected function formatValue( string $value, string $type )
	{
		if( $type === 'string' ){
			if( !preg_match( '/^[A-Z:_]+$/', $value ) ){
				return '"'.$value.'"';
			}
		}
		return $value;
	}
}
