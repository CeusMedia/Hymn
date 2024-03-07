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
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2024 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo			code documentation
 */
class Hymn_Module_Reader
{
	public function load( string $filePath, string $moduleId ): object
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
		$this->readFrameworks( $obj, $xml );
		$this->readInstallation( $obj, $xml );
		$this->readLog( $obj, $xml );
		$this->readFiles( $obj, $xml );
		$this->readLicenses( $obj, $xml );
		$this->readCompanies( $obj, $xml );
		$this->readAuthors( $obj, $xml );
		$this->readConfig( $obj, $xml );
		$this->readRelations( $obj, $xml );
		$this->readSql( $obj, $xml );
		$this->readLinks( $obj, $xml );
		$this->readHooks( $obj, $xml );
		$this->readDeprecation( $obj, $xml );
		if( isset( $obj->config['active'] ) )
			$obj->isActive	= $obj->config['active']->value;
		$obj->isDeprecated	= count( $obj->deprecation ) > 0;
		return $obj;
	}

	public static function loadStatic( string $filePath, string $moduleId ): object
	{
		$reader	= new Hymn_Module_Reader();
		return $reader->load( $filePath, $moduleId );
	}

	/*  --  PROTECTED  --  */
	protected function getAttribute( SimpleXMLElement $xmlNode, string $attributeName, $default = NULL, ?string $nsPrefix = NULL )
	{
		$attributes	= $this->getAttributes( $xmlNode, $nsPrefix );
		if( isset( $attributes[$attributeName] ) )
			return $attributes[$attributeName];
		return $default;
	}

	protected function getAttributes( SimpleXMLElement $xmlNode, ?string $nsPrefix = NULL ): array
	{
		$list	= [];
		foreach( $xmlNode->attributes( $nsPrefix, TRUE ) as $name => $value )
			$list[$name]	= (string) $value;
		return $list;
	}

	protected function hasAttribute( SimpleXMLElement $xmlNode, string $attributeName, ?string $nsPrefix = NULL ) : bool
	{
		$attributes	= $this->getAttributes( $xmlNode, $nsPrefix );
		return isset( $attributes[$attributeName] );
	}

	protected function readAuthors( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->author as $author ){
			$email	= $this->getAttribute( $author, 'email', '' );
			$site	= $this->getAttribute( $author, 'site', '' );
			$obj->authors[]	= (object) array(
				'name'	=> (string) $author,
				'email'	=> $email,
				'site'	=> $site
			);
		}
	}

	protected function readCompanies( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->company as $company ){
			$site	= $this->getAttribute( $company, 'site', '' );
			$obj->companies[]	= (object) array(
				'name'		=> (string) $company,
				'site'		=> $site
			);
		}
	}

	protected function readConfig( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->config as $pair ){
			$key		= $this->getAttribute( $pair, 'name' );
			$type		= $this->getAttribute( $pair, 'type', 'string' );
			$values		= $this->getAttribute( $pair, 'values', '' );
			$values		= strlen( $values ) ? preg_split( "/\s*,\s*/", $values ) : [];			//  split value on comma if set
			$title		= $this->getAttribute( $pair, 'title' );
			if( !$title && $this->hasAttribute( $pair, 'info' ) )
				$title	= $this->getAttribute( $pair, 'info' );
			$value		= trim( (string) $pair );
			if( in_array( strtolower( $type ), ['boolean', 'bool'] ) )						//  value is boolean
				$value	= !in_array( strtolower( $value ), ['no', 'false', '0', ''] );		//  value is not negative
			$obj->config[$key]	= (object) array(
				'key'			=> trim( $key ),
				'type'			=> trim( strtolower( $type ) ),
				'value'			=> $value,
				'values'		=> $values,
				'mandatory'		=> $this->getAttribute( $pair, 'mandatory', FALSE ),
				'protected'		=> $this->getAttribute( $pair, 'protected', FALSE ),
				'title'			=> $title,
				'default'		=> $this->getAttribute( $pair, 'default' ),
				'original'		=> $this->getAttribute( $pair, 'original' ),
			);
		}
	}

	protected function readDeprecation( $obj, SimpleXMLElement $xml ): void
	{
		if( !isset( $xml->deprecation ) )
			return;
		$obj->deprecation		= array(
			'message'	=> (string) $xml->deprecation,
			'version'	=> $this->getAttribute( $xml->deprecation, 'version', $obj->version ),
			'url'		=> $this->getAttribute( $xml->deprecation, 'url', '' ),
		);
	}

	protected function readFiles( $obj, SimpleXMLElement $xml ): void
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
				$object	= (object) array(
					'type'	=> $source,
					'file'	=> (string) $file,
				);
				foreach( $this->getAttributes( $file ) as $key => $value )
					$object->$key	= $value;
				$obj->files->{$target}[]	= $object;
			}
		}
	}

	protected function readFrameworks( $obj, SimpleXMLElement $xml ): void
	{
		$frameworks	= $this->getAttribute( $xml, 'frameworks', 'Hydrogen:>=0.8' );
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

	protected function readHooks( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->hook as $hook ){
			$resource	= $this->getAttribute( $hook, 'resource' );
			$event		= $this->getAttribute( $hook, 'event' );
			$obj->hooks[$resource][$event][]	= (object) array(
				'level'	=> $this->getAttribute( $hook, 'level', 5 ),
				'hook'	=> trim( (string) $hook, ' ' ),
			);
		}
	}

  protected function readInstallation( $obj, SimpleXMLElement $xml ): void
  {
		$installDate		= $this->getAttribute( $xml->version, 'install-date', '' );
		$obj->installDate	= $installDate ? strtotime( $installDate ) : '';									//  note install date
		$obj->installType	= (int) $this->getAttribute( $xml->version, 'install-type' );			//  note install type
		$obj->installSource	= $this->getAttribute( $xml->version, 'install-source' );				//  note install source
	}

	protected function readLicenses( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->license as $license ){
			$source	= $this->getAttribute( $license, 'source' );
			$obj->licenses[]	= (object) array(
				'label'		=> (string) $license,
				'source'	=> $source
			);
		}
	}

	protected function readLinks( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->link as $link ){
			$access		= $this->getAttribute( $link, 'access' );
			$language	= $this->getAttribute( $link, 'lang', NULL, 'xml' );
			$label		= (string) $link;
			$path		= $this->getAttribute( $link, 'path', $label );
			$rank		= $this->getAttribute( $link, 'rank', 10 );
			$parent		= $this->getAttribute( $link, 'parent' );
			$link		= $this->getAttribute( $link, 'link' );
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

	protected function readLog( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->log as $entry ){																//  iterate version log entries if available
			$obj->versionLog[]	= (object) array(													//  append version log entry
				'note'		=> (string) $entry,														//  extract entry note
				'version'	=> $this->getAttribute( $entry, 'version' ),							//  extract entry version
			);
		}
	}

	protected function readRelations( $obj, SimpleXMLElement $xml ): void
	{
		if( $xml->relations ){
			foreach( $xml->relations->needs as $moduleName )
				$obj->relations->needs[(string) $moduleName]	= (object) array(
					'relation'	=> 'needs',
					'type'		=> $this->getAttribute( $moduleName, 'type' ),
					'id'		=> (string) $moduleName,
					'source'	=> $this->getAttribute( $moduleName, 'source' ),
					'version'	=> $this->getAttribute( $moduleName, 'version' ),
				);
			foreach( $xml->relations->supports as $moduleName )
				$obj->relations->supports[(string) $moduleName]	= (object) array(
					'relation'	=> 'supports',
					'type'		=> $this->getAttribute( $moduleName, 'type' ),
					'id'		=> (string) $moduleName,
					'source'	=> $this->getAttribute( $moduleName, 'source' ),
					'version'	=> $this->getAttribute( $moduleName, 'version' ),
				);
		}
	}

	protected function readSql( $obj, SimpleXMLElement $xml ): void
	{
		foreach( $xml->sql as $sql ){
			$event		= $this->getAttribute( $sql, 'on' );
			$to			= $this->getAttribute( $sql, 'version-to' );
			$version	= $this->getAttribute( $sql, 'version', $to );
			$type		= $this->getAttribute( $sql, 'type', '*' );

			if( $event == "update" )
				if( !$version )
					throw new Exception( 'SQL type "update" needs attribute "version"' );

			foreach( explode( ',', $type ) as $type ){
				$key	= $event.'@'.$type;
				if( in_array( $event, ['install', 'update'] ) )
					$key	= $event.":".$version.'@'.$type;
				$obj->sql[$key] = (object) array(
					'event'		=> $event,
					'version'	=> $version,
					'type'		=> $type,
					'sql'		=> (string) $sql
				);
			}
		}
	}
}
