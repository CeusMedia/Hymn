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
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		https://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Reader
{
	public static function load( string $filePath, string $moduleId ): object
	{
		$validator	= new Hymn_Tool_XML_Validator();
		if( !$validator->validateFile( $filePath ) )
			throw new RuntimeException( 'XML file of module "'.$moduleId.'" is invalid: '.$validator->getErrorMessage().' in line '.$validator->getErrorLine() );

		$xml	= new SimpleXMLElement( file_get_contents( $filePath ) );
		$obj	= new stdClass();
		$obj->id					= $moduleId;
		$obj->title					= (string) $xml->title;
		$obj->category				= (string) $xml->category;
		$obj->description			= (string) $xml->description;
		$obj->deprecation			= [];
		$obj->frameworks			= [];
		$obj->version				= (string) $xml->version;
		$obj->versionAvailable		= NULL;
		$obj->versionInstalled		= NULL;
		$obj->versionLog			= [];
		$obj->isInstalled			= FALSE;
		$obj->isActive				= TRUE;
		$obj->companies				= [];
		$obj->authors				= [];
		$obj->licenses				= [];
		$obj->price					= (string) $xml->price;
		$obj->icon					= NULL;
		$obj->files					= new stdClass();
		$obj->files->classes		= [];
		$obj->files->locales		= [];
		$obj->files->templates		= [];
		$obj->files->styles			= [];
		$obj->files->scripts		= [];
		$obj->files->images			= [];
		$obj->files->files			= [];
		$obj->config				= [];
		$obj->relations				= new stdClass();
		$obj->relations->needs		= [];
		$obj->relations->supports	= [];
		$obj->sql					= [];
		$obj->links					= [];
		$obj->hooks					= [];
		$obj->installType			= 0;
		$obj->installDate			= NULL;
		$obj->installSource			= NULL;
		self::decorateObjectWithFrameworks( $obj, $xml );
		self::readInstallation( $obj, $xml );
		self::decorateObjectWithLog( $obj, $xml );
		self::decorateObjectWithFiles( $obj, $xml );
		self::decorateObjectWithLicenses( $obj, $xml );
		self::decorateObjectWithCompanies( $obj, $xml );
		self::decorateObjectWithAuthors( $obj, $xml );
		self::decorateObjectWithConfig( $obj, $xml );
		self::decorateObjectWithRelations( $obj, $xml );
		self::decorateObjectWithSql( $obj, $xml );
		self::decorateObjectWithLinks( $obj, $xml );
		self::decorateObjectWithHooks( $obj, $xml );
		self::decorateObjectWithDeprecation( $obj, $xml );
		if( isset( $obj->config['active'] ) )
			$obj->isActive	= $obj->config['active']->value;
		$obj->isDeprecated	= count( $obj->deprecation ) > 0;
		return $obj;
	}

	/*  --  PROTECTED  --  */
	protected static function getAttribute( SimpleXMLElement $xmlNode, string $attributeName, $default = NULL, ?string $nsPrefix = NULL )
	{
		$attributes	= self::getAttributes( $xmlNode, $nsPrefix );
		if( isset( $attributes[$attributeName] ) )
			return $attributes[$attributeName];
		return $default;
	}

	protected static function getAttributes( SimpleXMLElement $xmlNode, ?string $nsPrefix = NULL ): array
	{
		$list	= [];
		foreach( $xmlNode->attributes( $nsPrefix, TRUE ) as $name => $value )
			$list[$name]	= (string) $value;
		return $list;
	}

	protected static function hasAttribute( SimpleXMLElement $xmlNode, string $attributeName, ?string $nsPrefix = NULL ) : bool
	{
		$attributes	= self::getAttributes( $xmlNode, $nsPrefix );
		return isset( $attributes[$attributeName] );
	}

	protected static function decorateObjectWithAuthors( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->author as $author ){
			$email	= self::getAttribute( $author, 'email', '' );
			$site	= self::getAttribute( $author, 'site', '' );
			$obj->authors[]	= (object) [
				'name'	=> (string) $author,
				'email'	=> $email,
				'site'	=> $site
			];
		}
	}

	protected static function decorateObjectWithCompanies( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->company as $company ){
			$site	= self::getAttribute( $company, 'site', '' );
			$obj->companies[]	= (object) [
				'name'		=> (string) $company,
				'site'		=> $site
			];
		}
	}

	protected static function decorateObjectWithConfig( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->config as $pair ){
			$key		= self::getAttribute( $pair, 'name' );
			$type		= self::getAttribute( $pair, 'type', 'string' );
			$values		= self::getAttribute( $pair, 'values', '' );
			$values		= strlen( $values ) ? preg_split( "/\s*,\s*/", $values ) : [];			//  split value on comma if set
			$title		= self::getAttribute( $pair, 'title' );
			if( !$title && self::hasAttribute( $pair, 'info' ) )
				$title	= self::getAttribute( $pair, 'info' );
			$value		= trim( (string) $pair );
			if( in_array( strtolower( $type ), ['boolean', 'bool'] ) )						//  value is boolean
				$value	= !in_array( strtolower( $value ), ['no', 'false', '0', ''] );		//  value is not negative
			$obj->config[$key]	= (object) [
				'key'			=> trim( $key ),
				'type'			=> trim( strtolower( $type ) ),
				'value'			=> $value,
				'values'		=> $values,
				'mandatory'		=> self::getAttribute( $pair, 'mandatory', FALSE ),
				'protected'		=> self::getAttribute( $pair, 'protected', FALSE ),
				'title'			=> $title,
				'default'		=> self::getAttribute( $pair, 'default' ),
				'original'		=> self::getAttribute( $pair, 'original' ),
			];
		}
	}

	protected static function decorateObjectWithDeprecation( $obj, SimpleXMLElement $xml ): void
	{
		if( !isset( $xml->deprecation ) )
			return;
		$obj->deprecation		= [
			'message'	=> (string) $xml->deprecation,
			'version'	=> self::getAttribute( $xml->deprecation, 'version', $obj->version ),
			'url'		=> self::getAttribute( $xml->deprecation, 'url', '' ),
		];
	}

	protected static function decorateObjectWithFiles( $obj, SimpleXMLElement $xml ): void
	{
		if( !$xml->files )
			return;
		$map	= [
			'class'		=> 'classes',
			'locale'	=> 'locales',
			'template'	=> 'templates',
			'style'		=> 'styles',
			'script'	=> 'scripts',
			'image'		=> 'images',
			'file'		=> 'files',
		];
		foreach( $map as $source => $target ){														//  iterate files
			foreach( $xml->files->$source as $file ){
				$object	= (object) [
					'type'	=> $source,
					'file'	=> (string) $file,
				];
				foreach( self::getAttributes( $file ) as $key => $value )
					$object->$key	= $value;
				$obj->files->{$target}[]	= $object;
			}
		}
	}

	protected static function decorateObjectWithFrameworks( $obj, SimpleXMLElement $xml ): void
	{
		$frameworks	= self::getAttribute( $xml, 'frameworks', 'Hydrogen:>=0.8' );
		if( !strlen( trim( $frameworks ) ) )
			return;
		$list		= preg_split( '/\s*(,|\|)\s*/', $frameworks );
		foreach( $list as $listItem ){
			$parts	= preg_split( '/\s*(:|@)\s*/', $listItem );
			if( count( $parts ) < 2 )
				$parts[1]	= '*';
			$obj->frameworks[(string) $parts[0]]	= (string) $parts[1];
		}
	}

	protected static function decorateObjectWithHooks( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->hook as $hook ){
			$resource	= self::getAttribute( $hook, 'resource' );
			$event		= self::getAttribute( $hook, 'event' );
			$obj->hooks[$resource][$event][]	= (object) [
				'level'	=> self::getAttribute( $hook, 'level', 5 ),
				'hook'	=> trim( (string) $hook, ' ' ),
			];
		}
	}

  protected static function readInstallation( $obj, SimpleXMLElement $xml ): void
  {
		$installDate		= self::getAttribute( $xml->version, 'install-date', '' );
		$obj->installDate	= $installDate ? strtotime( $installDate ) : '';									//  note install date
		$obj->installType	= (int) self::getAttribute( $xml->version, 'install-type' );			//  note install type
		$obj->installSource	= self::getAttribute( $xml->version, 'install-source' );				//  note install source
	}

	protected static function decorateObjectWithLicenses( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->license as $license ){
			$source	= self::getAttribute( $license, 'source' );
			$obj->licenses[]	= (object) [
				'label'		=> (string) $license,
				'source'	=> $source
			];
		}
	}

	protected static function decorateObjectWithLinks( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->link as $link ){
			$access		= self::getAttribute( $link, 'access' );
			$language	= self::getAttribute( $link, 'lang', NULL, 'xml' );
			$label		= (string) $link;
			$path		= self::getAttribute( $link, 'path', $label );
			$rank		= self::getAttribute( $link, 'rank', 10 );
			$parent		= self::getAttribute( $link, 'parent' );
			$link		= self::getAttribute( $link, 'link' );
			$obj->links[]	= (object) [
				'parent'	=> $parent,
				'access'	=> $access,
				'language'	=> $language,
				'path'		=> $path,
				'link'		=> $link,
				'rank'		=> $rank,
				'label'		=> $label,
			];
		}
	}

	protected static function decorateObjectWithLog( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->log as $entry ){																//  iterate version log entries if available
			$obj->versionLog[]	= (object) [													//  append version log entry
				'note'		=> (string) $entry,														//  extract entry note
				'version'	=> self::getAttribute( $entry, 'version' ),							//  extract entry version
			];
		}
	}

	protected static function decorateObjectWithRelations( $obj, SimpleXMLElement $xml ): void
	{
		if( $xml->relations ){
			foreach( $xml->relations->needs as $moduleName )
				$obj->relations->needs[(string) $moduleName]	= (object) [
					'relation'	=> 'needs',
					'type'		=> self::getAttribute( $moduleName, 'type' ),
					'id'		=> (string) $moduleName,
					'source'	=> self::getAttribute( $moduleName, 'source' ),
					'version'	=> self::getAttribute( $moduleName, 'version' ),
				];
			foreach( $xml->relations->supports as $moduleName )
				$obj->relations->supports[(string) $moduleName]	= (object) [
					'relation'	=> 'supports',
					'type'		=> self::getAttribute( $moduleName, 'type' ),
					'id'		=> (string) $moduleName,
					'source'	=> self::getAttribute( $moduleName, 'source' ),
					'version'	=> self::getAttribute( $moduleName, 'version' ),
				];
		}
	}

	protected static function decorateObjectWithSql( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->sql as $sql ){
			$event		= self::getAttribute( $sql, 'on' );
			$to			= self::getAttribute( $sql, 'version-to' );
			$version	= self::getAttribute( $sql, 'version', $to );
			$type		= self::getAttribute( $sql, 'type', '*' );

			if( $event == "update" )
				if( !$version )
					throw new Exception( 'SQL type "update" needs attribute "version"' );

			foreach( explode( ',', $type ) as $type ){
				$key	= $event.'@'.$type;
				if( in_array( $event, ['install', 'update'] ) )
					$key	= $event.":".$version.'@'.$type;
				$obj->sql[$key] = (object) [
					'event'		=> $event,
					'version'	=> $version,
					'type'		=> $type,
					'sql'		=> (string) $sql
				];
			}
		}
	}
}
