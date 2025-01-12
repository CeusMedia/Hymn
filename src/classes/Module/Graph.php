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
class Hymn_Module_Graph
{
	public const int STATUS_EMPTY		= 0;
	public const int STATUS_CHANGED		= 1;
	public const int STATUS_LINKED		= 2;
	public const int STATUS_PRODUCED	= 3;
	public const int STATUS_DRAWN		= 4;

	/** @var		Hymn_Client				$client */
	public Hymn_Client $client;

	/** @var		Hymn_Module_Library		$library */
	public Hymn_Module_Library $library;

	/** @var		array					$nodes */
	public array $nodes						= [];

	protected Hymn_Structure_Graph $graph;

	/** @var		object{quiet: bool, verbose: bool}	$flags */
	protected object $flags;

	/** @var		integer					$status */
	protected int $status					= self::STATUS_EMPTY;

	public function __construct( Hymn_Client $client, Hymn_Module_Library $library )
	{
		$this->client	= $client;
		$this->library	= $library;
		$this->graph	= new Hymn_Structure_Graph( $library );
		$this->flags	= (object) [
			'quiet'		=> (bool) ($this->client->flags & Hymn_Client::FLAG_QUIET ),
			'verbose'	=> (bool) ($this->client->flags & Hymn_Client::FLAG_VERBOSE ),
		];
	}

	/**
	 *	Adds a module to graph as well as all modules linked as 'needed'.
	 *	Sets status to 'changed'.
	 *	@access		public
	 *	@param		Hymn_Structure_Module	$module		Module data object
	 *	@param		integer					$level		Load level of module, default: 0
	 *	@return		void
	 */
	public function addModule( Hymn_Structure_Module $module, int $level = 0 ): void
	{
		$this->graph->addModule( $module, $level );
		$errors		= $this->graph->getErrors( TRUE );
		if( [] !== $errors )
			$this->client->outError( current( $errors ), Hymn_Client::EXIT_ON_RUN );
		$this->status	= self::STATUS_CHANGED;														//  set internal status to "changed"
	}

	/**
	 *	Return list of all needed modules by installation order.
	 *	Calculates order key by call level and needed modules.
	 *	Resulting list will start with modules which are needed by others and end with meta modules.
	 *	@access		public
	 *	@return		array
	 */
	public function getModulesOrderedByDependency(): array
	{
		if( $this->status < self::STATUS_CHANGED )
			throw new RuntimeException( 'No modules loaded' );
		if( $this->status < self::STATUS_LINKED )
			$this->realizeRelations();

		/*  calculate maximum relation depth  */
		$list	= [];
		$max	= pow( 10, 8 ) - 1;
		$this->client->outVeryVerbose( 'Checking for loop in module relation graph...' );
		foreach( $this->graph->getNodes() as $id => $node ){
			/** @var ?object{module: Hymn_Structure_Module, modules: array<Hymn_Structure_Module>} $loop */
			$loop	= $this->graph->checkForLoop( $node );
			if( NULL !== $loop ){
				$this->client->outError( 'Module relation loop found in module '.$loop->module->id.' @ '.$loop->module->sourceId );
				foreach( array_values( $loop->modules ) as $nr => $item ){
					$bullet	= str_pad( (string) ++$nr, 3, ' ', STR_PAD_LEFT );
					$this->client->out( ' '.$bullet.'. '.$item->id.' @ '.$item->sourceId );
				}
				$this->client->outError( 'Please resolve loop, first!', Hymn_Client::EXIT_ON_RUN );
			}
			$edges	= $this->graph->countModuleEdgesToRoot( $node );
			$rand	= str_pad( (string) rand( 0, $max ), 8, '0', STR_PAD_LEFT );
			$list[(float) $edges.'.'.$rand]	= $id;
		}
		krsort( $list );																			//  sort module order list

		$this->client->outVeryVerbose( $this->client->getMemoryUsage( 'after calculating module order by dependency' ) );

		/*  collect modules by installation order  */
		$modules	= [];																		//  prepare empty module list
		foreach( $list as $id )														//  iterate module order list
			$modules[$id]	= $this->graph->getNode( $id )->module;											//  collect module by installation order
		return $modules;																			//  return list of modules by installation order
	}

	//  @todo	make independent from need/support
	public function renderGraphFile( string $targetFile = NULL/*, string $type = 'needs'*/ ): string
	{
		if( $this->status < self::STATUS_LINKED )
			$this->realizeRelations();
		$nodeStyle	= 'fontsize=9 shape=box color=black style=filled color="#00007F" fillcolor="#CFCFFF"';
		$nodes	= [];
		$edges	= [];
		foreach( $this->graph->getNodes() as $id => $node ){
			$label		= 'label="'.$node->module->title.'"';
			$nodes[]	= $node->module->id.' ['.$label.' '.$nodeStyle.'];';
			foreach( $node->out as $out )
				$edges[]	= $node->module->id.' -> '.$out->module->id.' []';
		}
		$options	= "\n\t".'rankdir="LR"';
		$this->status	= self::STATUS_PRODUCED;
		if( $this->flags->verbose && !$this->flags->quiet )
			$this->client->out( "Produced graph with ".count( $nodes )." nodes and ".count( $edges )." edged." );
		$nodes		= $nodes ? "\n\t".join( "\n\t", $nodes ) : '';
		$edges		= $edges ? "\n\t".join( "\n\t", $edges ) : '';
		$graph		= "digraph {".$options.$nodes.$edges."\n}";
		if( $targetFile ){
			file_put_contents( $targetFile, $graph );
			if( !$this->flags->quiet )
				$this->client->out( "Saved graph file to ".$targetFile."." );
		}
		return $graph;
	}

	public function renderGraphImage( ?string $graph = NULL, ?string $targetFile = NULL ): ?string
	{
		$this->client->out( "Checking graphviz: ", FALSE );
		$toolTest	= new Hymn_Tool_Test( $this->client );
		$toolTest->checkShellCommand( "graphviz" );
		$this->client->out( "OK" );
		try{
			if( !$graph )
				$graph		= $this->renderGraphFile();
			$sourceFile	= tempnam( sys_get_temp_dir(), 'Hymn' );
			file_put_contents( $sourceFile, $graph );												//  save temporary

			if( $targetFile ){
				@exec( 'dot -Tpng -o'.$targetFile.' '.$sourceFile );
				if( !$this->flags->quiet )
					$this->client->out( 'Graph image saved to '.$targetFile.'.' );
			}
			else{
				exec( 'dot -Tpng -O '.$sourceFile );
				unlink( $sourceFile );
				$graphImage	= file_get_contents( $sourceFile.'.png' );
				@unlink( $sourceFile );
				return $graphImage;
			}
		}
		catch( Exception $e ){
			$this->client->out( 'Graph rendering failed: '.$e->getMessage().'.' );
		}
		return NULL;
	}

	protected function realizeRelations(): void
	{
		if( self::STATUS_LINKED <= $this->status )
			return;
		$this->graph->realizeRelations();
		$this->status	= self::STATUS_LINKED;
		if( $this->flags->verbose && !$this->flags->quiet )
			$this->client->outVeryVerbose( "Found ".count( $this->nodes )." modules." );
		$this->client->outVeryVerbose( $this->client->getMemoryUsage( 'after realizing module relations' ) );
	}
}
