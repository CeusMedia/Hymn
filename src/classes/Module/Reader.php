<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2018 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Reader{

	static function hasAttribute( $xmlNode, $attributeName, $nsPrefix = NULL ){
		$attributes	= self::getAttributes( $xmlNode );
		return isset( $attributes[$attributeName] );
	}

	static function getAttribute( $xmlNode, $attributeName, $default = NULL, $nsPrefix = NULL ){
		$attributes	= self::getAttributes( $xmlNode );
		if( isset( $attributes[$attributeName] ) )
			return $attributes[$attributeName];
		return $default;
	}

	static function getAttributes( $xmlNode, $nsPrefix = NULL ){
		$list	= array();
		foreach( $xmlNode->attributes( $nsPrefix, TRUE ) as $name => $value )
			$list[$name]	= (string) $value;
		return $list;
	}

	static public function load( $fileName, $id ){
		$validator	= new Hymn_Tool_XmlValidator();
		if( !$validator->validateFile( $fileName ) )
			throw new RuntimeException( 'XML file of module "'.$id.'" is invalid: '.$validator->getErrorMessage().' in line '.$validator->getErrorLine() );

		$xml	= new SimpleXMLElement( file_get_contents( $fileName ) );
		$obj	= new stdClass();
		$obj->id					= $id;
		$obj->title					= (string) $xml->title;
		$obj->category				= (string) $xml->category;
		$obj->description			= (string) $xml->description;
		$obj->version				= (string) $xml->version;
		$obj->versionAvailable		= NULL;
		$obj->versionInstalled		= NULL;
		$obj->versionLog			= array();
		$obj->isInstalled			= FALSE;
		$obj->companies				= array();
		$obj->authors				= array();
		$obj->licenses				= array();
		$obj->price					= (string) $xml->price;
		$obj->icon					= NULL;
		$obj->files					= new stdClass();
		$obj->files->classes		= array();
		$obj->files->locales		= array();
		$obj->files->templates		= array();
		$obj->files->styles			= array();
		$obj->files->scripts		= array();
		$obj->files->images			= array();
		$obj->files->files			= array();
		$obj->config				= array();
		$obj->relations				= new stdClass();
		$obj->relations->needs		= array();
		$obj->relations->supports	= array();
		$obj->sql					= array();
		$obj->links					= array();
		$obj->hooks					= array();
		$obj->installType			= 0;
		$obj->installDate			= NULL;
		$obj->installSource			= NULL;

		/*	--  LOCALLY INSTALLED MODULE  --  */
		$obj->installType	= (int) self::getAttribute( $xml->version, 'install-type' );				//  note install type
		$obj->installDate	= strtotime( self::getAttribute( $xml->version, 'install-date' ) );		//  note install date
		$obj->installSource	= self::getAttribute( $xml->version, 'install-source' );					//  note install source

		foreach( $xml->log as $entry ){																//  iterate version log entries if available
			$obj->versionLog[]	= (object) array(													//  append version log entry
				'note'		=> (string) $entry,														//  extract entry note
				'version'	=> self::getAttribute( $entry, 'version' ),									//  extract entry version
			);
		}
		if( $xml->files ){																			//  iterate files
			$map	= array(																		//  ...
				'class'		=> 'classes',
				'locale'	=> 'locales',
				'template'	=> 'templates',
				'style'		=> 'styles',
				'script'	=> 'scripts',
				'image'		=> 'images',
				'file'		=> 'files',
			);
			foreach( $map as $source => $target ){
				foreach( $xml->files->$source as $file ){
					$object	= (object) array( 'file' => (string) $file );
					foreach( self::getAttributes( $file ) as $key => $value )
						$object->$key	= $value;
					$obj->files->{$target}[]	= $object;
				}
			}
		}

		foreach( $xml->license as $license ){
			$source	= self::getAttribute( $license, 'source' );
			$obj->licenses[]	= (object) array(
				'label'		=> (string) $license,
				'source'	=> $source
			);
		}

		foreach( $xml->company as $company ){
			$site	= self::getAttribute( $company, 'site', '' );
			$obj->companies[]	= (object) array(
				'name'		=> (string) $company,
				'site'		=> $site
			);
		}

		foreach( $xml->author as $author ){
			$email	= self::getAttribute( $author, 'email', '' );
			$site	= self::getAttribute( $author, 'site', '' );
			$obj->authors[]	= (object) array(
				'name'	=> (string) $author,
				'email'	=> $email,
				'site'	=> $site
			);
		}

		foreach( $xml->config as $pair ){
			$key		= self::getAttribute( $pair, 'name' );
			$type		= self::getAttribute( $pair, 'type', 'string' );
			$values		= self::getAttribute( $pair, 'values', '' );
			$values		= strlen( $values ) ? preg_split( "/\s*,\s*/", $values ) : array();			//  split value on comma if set
			$mandatory	= self::getAttribute( $pair, 'mandatory', FALSE );
			$protected	= self::getAttribute( $pair, 'protected', FALSE );
			$title		= self::getAttribute( $pair, 'title' );
			if( !$title && self::hasAttribute( $pair, 'info' ) )
				$title	= self::getAttribute( $pair, 'info' );
			$value		= trim( (string) $pair );
			if( in_array( strtolower( $type ), array( 'boolean', 'bool' ) ) )						//  value is boolean
				$value	= !in_array( strtolower( $value ), array( 'no', 'false', '0', '' ) );		//  value is not negative
			$obj->config[$key]	= (object) array(
				'key'		=> trim( $key ),
				'type'		=> trim( strtolower( $type ) ),
				'value'		=> $value,
				'values'	=> $values,
				'mandatory'	=> $mandatory,
				'protected'	=> $protected,
				'title'		=> $title,
			);
		}
		if( $xml->relations ){
			foreach( $xml->relations->needs as $moduleName )
				$obj->relations->needs[]	= (string) $moduleName;
			foreach( $xml->relations->supports as $moduleName )
				$obj->relations->supports[]	= (string) $moduleName;
		}
		foreach( $xml->sql as $sql ){
			$event		= self::getAttribute( $sql, 'on' );
			$to			= self::getAttribute( $sql, 'version-to', NULL );
			$version	= self::getAttribute( $sql, 'version', $to );
			$type		= self::getAttribute( $sql, 'type', '*' );

			if( $event == "update" )
				if( !$version )
					throw new Exception( 'SQL type "update" needs attribute "version"' );

			foreach( explode( ',', $type ) as $type ){
				$key	= $event.'@'.$type;
				if( in_array( $event, array( 'install', 'update' ) ) )
					$key	= $event.":".$version.'@'.$type;
				$obj->sql[$key] = (object) array(
					'event'		=> $event,
					'version'	=> $version,
					'type'		=> $type,
					'sql'		=> (string) $sql
				);
			}
		}

		foreach( $xml->link as $link ){
			$access		= self::getAttribute( $link, 'access' );
			$language	= self::getAttribute( $link, 'lang', NULL, 'xml' );
			$label		= (string) $link;
			$path		= self::getAttribute( $link, 'path', $label );
			$rank		= self::getAttribute( $link, 'rank', 10 );
			$parent		= self::getAttribute( $link, 'parent' );
			$link		= self::getAttribute( $link, 'link' );
			$obj->links[]	= (object) array(
				'parent'	=> $parent,
				'access'	=> $access,
				'language'	=> $language,
				'path'		=> $path,
				'link'		=> $link,
				'rank'		=> $rank,
				'label'		=> $label,
			);
			(string) $link;
		}

		foreach( $xml->hook as $hook ){
			$resource	= self::getAttribute( $hook, 'resource' );
			$event		= self::getAttribute( $hook, 'event' );
			$obj->hooks[$resource][$event][]	= (string) $hook;
		}
		return $obj;
	}
}
?>
