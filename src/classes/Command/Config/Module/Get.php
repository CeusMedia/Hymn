<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2017 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Command.Config.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Command.Config,Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2017 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Command_Config_Module_Get extends Hymn_Command_Abstract implements Hymn_Command_Interface{

	/**
	 *	Execute this command.
	 *	@access		public
	 *	@return		void
	 */
	public function run(){
		$config		= $this->client->getConfig();
		$key		= $this->client->arguments->getArgument( 0 );
		if( !strlen( trim( $key ) ) )
			throw new InvalidArgumentException( 'First argument "key" is missing' );

		$parts	= explode( ".", $key );
		$module	= array_shift( $parts );
		if( !$parts )
			throw new InvalidArgumentException( 'Key must be of syntax "Module_Name.(section.)key"' );
		$configKey	= join( ".", $parts );

		$availableModules	= $this->getAvailableModulesMap( $config );

		if( !isset( $config->modules->{$module} ) && !isset( $availableModules[$module] ) )
			throw new InvalidArgumentException( 'Module "'.$module.'" not installed and not configured' );

		//  ATTENTION: In this view the ACTUAL value is taken from Hymn CONFIG file and seconded by module installation, only.
		$settings	= (object) array(																//  identified values of config key
			'configured'	=> NULL,																//  value from Hymn config file
			'installed'		=> NULL,																//  value from installed module config
			'actual'		=> NULL,																//  actual value, configuration over installation
		);
		if( isset( $availableModules[$module]->config[$configKey] ) )								//  config key is set in installed module
			$settings->installed	= $availableModules[$module]->config[$configKey]->value;		//  note installed value
		if( isset( $config->modules->{$module}->config->{$configKey} ) )							//  config key is set in Hymn config file
			$settings->configured	= $config->modules->{$module}->config->{$configKey};			//  note valued configured in Hymn file
		if( is_null( $settings->configured ) && is_null( $settings->installed ) ){					//  module key value is not set
			$msg	= 'No configuration value for key "%2$s" in module "%1$s" set';					//  exception message
			throw new InvalidArgumentException( sprintf( $msg, $module, $configKey ) );				//  throw exception
		}
		$settings->actual	= $settings->installed;													//  take possible value from installation
		if( !is_null( $settings->configured ) )														//  module value is configured by Hymn file
			$settings->actual	= $settings->configured;											//  take possible value from Hymn file
		Hymn_Client::out( $settings->actual, FALSE );												//  return actual value
	}
}
