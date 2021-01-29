<?php
/**
 *	...
 *
 *	Copyright (c) 2014-2021 Christian Würker (ceusmedia.de)
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
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 */
/**
 *	...
 *
 *	@category		Tool
 *	@package		CeusMedia.Hymn.Module
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2014-2021 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/Hymn
 *	@todo    		code documentation
 */
class Hymn_Module_Reader{

	public function load( $filePath, $moduleId ){
		$validator	= new Hymn_Tool_XML_Validator();
		if( !$validator->validateFile( $filePath ) )
			throw new RuntimeException( 'XML file of module "'.$moduleId.'" is invalid: '.$validator->getErrorMessage().' in line '.$validator->getErrorLine() );

		$xml	= new SimpleXMLElement( file_get_contents( $filePath ) );
		$obj	= new stdClass();
		$obj->frameworks			= array( 'Hydrogen' => '<0.9' );
		$obj->id					= $moduleId;
		$obj->title					= (string) $xml->title;
		$obj->category				= (string) $xml->category;
		$obj->description			= (string) $xml->description;
		$obj->deprecation			= array();
		$obj->version				= (string) $xml->version;
		$obj->versionAvailable		= NULL;
		$obj->versionInstalled		= NULL;
		$obj->versionLog			= array();
		$obj->isInstalled			= FALSE;
		$obj->isActive				= TRUE;
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

	public static function loadStatic( $filePath, $moduleId ){
		$reader	= new Hymn_Module_Reader();
		return $reader->load( $filePath, $moduleId );
	}

	/*  --  PROTECTED  --  */
	protected function getAttribute( $xmlNode, $attributeName, $default = NULL, $nsPrefix = NULL ){
		$attributes	= $this->getAttributes( $xmlNode, $nsPrefix );
		if( isset( $attributes[$attributeName] ) )
			return $attributes[$attributeName];
		return $default;
	}

	protected function getAttributes( $xmlNode, $nsPrefix = NULL ){
		$list	= array();
		foreach( $xmlNode->attributes( $nsPrefix, TRUE ) as $name => $value )
			$list[$name]	= (string) $value;
		return $list;
	}

	protected function hasAttribute( $xmlNode, $attributeName, $nsPrefix = NULL ){
		$attributes	= $this->getAttributes( $xmlNode, $nsPrefix );
		return isset( $attributes[$attributeName] );
	}

	protected function readAuthors( $obj, $xml ){
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

	protected function readCompanies( $obj, $xml ){
		foreach( $xml->company as $company ){
			$site	= $this->getAttribute( $company, 'site', '' );
			$obj->companies[]	= (object) array(
				'name'		=> (string) $company,
				'site'		=> $site
			);
		}
	}

	protected function readConfig( $obj, $xml ){
		foreach( $xml->config as $pair ){
			$key		= $this->getAttribute( $pair, 'name' );
			$type		= $this->getAttribute( $pair, 'type', 'string' );
			$values		= $this->getAttribute( $pair, 'values', '' );
			$values		= strlen( $values ) ? preg_split( "/\s*,\s*/", $values ) : array();			//  split value on comma if set
			$title		= $this->getAttribute( $pair, 'title' );
			if( !$title && $this->hasAttribute( $pair, 'info' ) )
				$title	= $this->getAttribute( $pair, 'info' );
			$value		= trim( (string) $pair );
			if( in_array( strtolower( $type ), array( 'boolean', 'bool' ) ) )						//  value is boolean
				$value	= !in_array( strtolower( $value ), array( 'no', 'false', '0', '' ) );		//  value is not negative
			$obj->config[$key]	= (object) array(
				'key'			=> trim( $key ),
				'type'			=> trim( strtolower( $type ) ),
				'value'			=> $value,
				'values'		=> $values,
				'mandatory'		=> $this->getAttribute( $pair, 'mandatory', FALSE ),
				'protected'		=> $this->getAttribute( $pair, 'protected', FALSE ),
				'title'			=> $title,
				'default'		=> $this->getAttribute( $pair, 'default', NULL ),
				'original'		=> $this->getAttribute( $pair, 'original', NULL ),
			);
		}
	}

	protected function readDeprecation( $obj, $xml ){
		if( !isset( $xml->deprecation ) )
			return;
		$obj->deprecation		= array(
			'message'	=> (string) $xml->deprecation,
			'version'	=> $this->getAttribute( $xml->deprecation, 'version', $obj->version ),
			'url'		=> $this->getAttribute( $xml->deprecation, 'url', '' ),
		);
	}

	protected function readFiles( $obj, $xml ){
		if( !$xml->files )
			return;
		$map	= array(
			'class'		=> 'classes',
			'locale'	=> 'locales',
			'template'	=> 'templates',
			'style'		=> 'styles',
			'script'	=> 'scripts',
			'image'		=> 'images',
			'file'		=> 'files',
		);
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

	protected function readFrameworks( $obj, $xml ){
		$frameworks	= $this->getAttribute( $xml, 'frameworks', '' );
		if( !strlen( trim( $frameworks ) ) )
			return;
		$obj->frameworks	= array();
		$list		= preg_split( '/\s*(,|\|)\s*/', $frameworks );
		foreach( $list as $listItem ){
			$parts	= preg_split( '/\s*(:|@)\s*/', $listItem );
			if( count( $parts ) < 2 )
				$parts[1]	= '*';
			$obj->frameworks[(string) $parts[0]]	= (string) $parts[1];
		}
	}

	protected function readHooks( $obj, $xml ){
		foreach( $xml->hook as $hook ){
			$resource	= $this->getAttribute( $hook, 'resource' );
			$event		= $this->getAttribute( $hook, 'event' );
			$obj->hooks[$resource][$event][]	= (string) $hook;
		}
	}

	protected function readInstallation( $obj, $xml ){
		$obj->installType	= (int) $this->getAttribute( $xml->version, 'install-type' );				//  note install type
		$obj->installDate	= strtotime( $this->getAttribute( $xml->version, 'install-date' ) );		//  note install date
		$obj->installSource	= $this->getAttribute( $xml->version, 'install-source' );					//  note install source
	}

	protected function readLicenses( $obj, $xml ){
		foreach( $xml->license as $license ){
			$source	= $this->getAttribute( $license, 'source' );
			$obj->licenses[]	= (object) array(
				'label'		=> (string) $license,
				'source'	=> $source
			);
		}
	}

	protected function readLinks( $obj, $xml ){
		foreach( $xml->link as $link ){
			$access		= $this->getAttribute( $link, 'access' );
			$language	= $this->getAttribute( $link, 'lang', NULL, 'xml' );
			$label		= (string) $link;
			$path		= $this->getAttribute( $link, 'path', $label );
			$rank		= $this->getAttribute( $link, 'rank', 10 );
			$parent		= $this->getAttribute( $link, 'parent' );
			$link		= $this->getAttribute( $link, 'link' );
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
	}

	protected function readLog( $obj, $xml ){
		foreach( $xml->log as $entry ){																//  iterate version log entries if available
			$obj->versionLog[]	= (object) array(													//  append version log entry
				'note'		=> (string) $entry,														//  extract entry note
				'version'	=> $this->getAttribute( $entry, 'version' ),									//  extract entry version
			);
		}
	}

	protected function readRelations( $obj, $xml ){
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

	protected function readSql( $obj, $xml ){
		foreach( $xml->sql as $sql ){
			$event		= $this->getAttribute( $sql, 'on' );
			$to			= $this->getAttribute( $sql, 'version-to', NULL );
			$version	= $this->getAttribute( $sql, 'version', $to );
			$type		= $this->getAttribute( $sql, 'type', '*' );

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
	}
}
?>
