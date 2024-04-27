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
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.App.Module.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Command_App_Module_Config_Get extends Hymn_Command_Abstract implements Hymn_Command_Interface
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
		$key		= $this->client->arguments->getArgument() ?? '';
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'First argument "key" is missing' );

		$keyParts	= explode( '.', $key );
		$moduleId	= array_shift( $keyParts );
		if( !$moduleId )
			throw new InvalidArgumentException( 'Key must be of syntax "Module_Name[.(section.)key]"' );
		$configKey	= join( '.', $keyParts );

		if( !$keyParts || $configKey === '*' ){
			$configurator	= new Hymn_Module_Config( $this->client, $this->getLibrary() );
			foreach( $configurator->getAll( $moduleId ) as $item ){
				if( $this->flags->verbose ){
					$type	= preg_replace( '/^bool$/', 'boolean', $item->type );
					$type	= preg_replace( '/^int$/', 'integer', $type );
					$type	= preg_replace( '/^(double|single)$/', 'float', $type );
					$this->out( ' - Key:       '.$item->key );
					$this->out( ' - Value:     '.$this->renderValue( $item ) );
					if( $item->values )
						$this->out( ' - Values:     '.join( ', ', preg_split( '/\s*,\s*/', $item->values ) ) );
					if( $item->title )
						$this->out( ' - Title:      '.$item->title );
					$this->out( ' - Type:      '.$type );
					$this->out( ' - Protected: '.$item->protected );
					$this->out( ' - Mandatory: '.( $item->mandatory ? 'yes' : 'no' ) );
				}
				else
					$this->out( $item->key.': '.$this->renderValue( $item ) );
			}
		}
		else{
			$configurator	= new Hymn_Module_Config( $this->client, $this->getLibrary() );
			$config			= $configurator->get( $moduleId, $configKey );
			$this->out( $config->value );
		}
	}

	protected function renderValue( $item ): int|float|string
	{
		if( in_array( $item->type, ['bool', 'boolean'] ) )
			return $item->value ? 'yes' : 'no';
		if( in_array( $item->type, ['int', 'integer'] ) )
			return (int) $item->value;
		if( in_array( $item->type, ['float', 'single', 'double'] ) )
			return (float) $item->value;
		return (string) $item->value;
	}
}
