<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2016 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Config_Module_Get extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	public function run(){
		$filename	= Hymn_Client::$fileName;
		if( !file_exists( $filename ) )
			throw new RuntimeException( 'File "'.$filename.'" is missing' );
		$config	= json_decode( file_get_contents( $filename ) );
		if( is_null( $config ) )
			throw new RuntimeException( 'Configuration file "'.$filename.'" is not valid JSON' );

		$key	= $this->client->arguments->getArgument( 0 );
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'Missing first argument "key" is missing' );

		$parts	= explode( ".", $key );
		$module	= array_shift( $parts );
		if( !$parts )
			throw new InvalidArgumentException( 'Invalid key - must be of syntax "Module_Name.(section.)key"' );
		$configKey	= join( ".", $parts );

		if( !isset( $config->modules->{$module} ) || !isset( $config->modules->{$module}->config ) )
			throw new InvalidArgumentException( 'No configuration for module "'.$module.'" set' );
		if( !isset( $config->modules->{$module}->config->{$configKey} ) )
			throw new InvalidArgumentException( 'No configuration value for key "'.$configKey.'" in module "'.$module.'" set' );

		$current	= $config->modules->{$module}->config->{$configKey};
		Hymn_Client::out( $current );
	}
}
