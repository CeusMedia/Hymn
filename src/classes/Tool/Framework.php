<?php
declare(strict_types=1);

/**
 *	...
 *
 *	Copyright (c) 2021-2025 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2021-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Tool
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2025 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
class Hymn_Tool_Framework
{
	protected bool $isInstalled				= FALSE;
	protected ?string $version				= NULL;
	protected string $defaultFrameworkPath	= 'vendor/ceus-media/hydrogen-framework/';

	public function __construct()
	{
		$this->detect( NULL, FALSE );
	}

	public function checkModuleSupport( object $module ): bool
	{
		return TRUE;
/*		if( NULL === $this->version )
			return FALSE;
		$frameworks	= $module->frameworks ? (array) $module->frameworks : [];
		if( !isset( $frameworks['Hydrogen'] ) ){
			$message	= 'Module "%s" version %s is not installable for framework "Hydrogen"';
			throw new RuntimeException( sprintf( $message, $module->id, $module->version ) );
		}
		$semver	= new Hymn_Tool_Semver();
		$semver->setActualVersion( $this->version );
		$semver->setSemanticVersion( $frameworks['Hydrogen'] );
		if( !$semver->check() ){
			$message	= 'Module "%1$s" version %2$s is not installable for framework version %4$s (needed: %3$s)';
			throw new RuntimeException( vsprintf( $message, [
				$module->id,
				$module->version,
				$frameworks['Hydrogen'],
				$this->version,
			] ) );
		}
		return TRUE;*/
	}

	public function getVersion(): ?string
	{
		return $this->version;
	}

	public function isInstalled(): bool
	{
		return $this->isInstalled;
	}

	protected function detect( ?string $pathToFramework = NULL, bool $strict = TRUE ): bool
	{
		$pathFramework	= $pathToFramework ?? $this->defaultFrameworkPath;
		$filePath		= $pathFramework.'hydrogen.ini';
		if( !file_exists( $filePath ) ){
			if( $strict )
				throw new RuntimeException( 'Framework "Hydrogen" is not installed in vendors folder' );
			return FALSE;
		}
		$ini	= parse_ini_file( $filePath, TRUE );
		if( !isset( $ini['project'] ) ){
			if( $strict )
				throw new RuntimeException( 'Missing section "project" in Hydrogen INI file ('.$filePath.')' );
			return FALSE;
		}
		if( !isset( $ini['project']['version'] ) ){
			if( $strict )
				throw new RuntimeException( 'Missing config pair "version" in Hydrogen INI file ('.$filePath.')' );
			return FALSE;
		}
		$this->version		= $ini['project']['version'];
		$this->isInstalled	= TRUE;
		return TRUE;
	}
}
