<?php

class Hymn_Test_Structure_GraphTest extends PHPUnit_Framework_TestCase
{
	protected Hymn_Client $client;

	protected function setUp(): void
	{
	}

	public function testCheckForLoop(): void
	{
		$library	= new Hymn_Module_Library( new Hymn_Client( ['--quiet'], FALSE ) );
		$library->addSource( 'Source1', 'src/', 'folder' );
		$graph		= new Hymn_Structure_Graph( $library );

		$module1	= new Hymn_Structure_Module( 'Module1', '1.0.0', '' );
		$module1->install	= new Hymn_Structure_Module_Installation();
		$module1->install->path	= '/modules/Module1';

		$module2	= new Hymn_Structure_Module( 'Module2', '1.0.0', '' );
		$module2->install	= new Hymn_Structure_Module_Installation();
		$module2->install->path	= '/modules/Module2';

		$module1->relations->needs[] = new Hymn_Structure_Module_Relation(
			'Module2',
			Hymn_Structure_Module_Relation::TYPE_MODULE,
			'Source1',
			'1.0.0',
			'needs'
		);

		$source1	= $library->getSource( 'Source1' );
		$library->addModuleToSource( $module1, $source1 );
		$library->addModuleToSource( $module2, $source1 );

		$graph->addModule( $module1 );
		self::assertSame( [], $graph->getErrors() );

		$nodes	= $graph->getNodes();
		self::assertSame( 2, count( $nodes ) );

		$graph->realizeRelations();

		$node1	= $graph->getNode( 'Module1' );
		self::assertSame( 1, count( $node1->out ) );
		self::assertSame( $graph->getNode( 'Module2' ), current( $node1->out ) );

		$loop	= $graph->checkForLoop( $graph->getNode( 'Module1' ) );
		self::assertNull( $loop );

		$loop	= $graph->checkForLoop( $graph->getNode( 'Module2' ) );
		self::assertNull( $loop );
	}

	public function testCheckForLoop_withLoop(): void
	{
		$library	= new Hymn_Module_Library( new Hymn_Client( ['--quiet'], FALSE ) );
		$library->addSource( 'Source1', 'src/', 'folder' );
		$graph		= new Hymn_Structure_Graph( $library );

		$module1	= new Hymn_Structure_Module( 'Module1', '1.0.0', '' );
		$module1->install	= new Hymn_Structure_Module_Installation();
		$module1->install->path	= '/modules/Module1';

		$module2	= new Hymn_Structure_Module( 'Module2', '1.0.0', '' );
		$module2->install	= new Hymn_Structure_Module_Installation();
		$module2->install->path	= '/modules/Module2';

		$module1->relations->needs[] = new Hymn_Structure_Module_Relation(
			'Module2',
			Hymn_Structure_Module_Relation::TYPE_MODULE,
			'Source1',
			'1.0.0',
			'needs'
		);

		$module2->relations->needs[] = new Hymn_Structure_Module_Relation(
			'Module1',
			Hymn_Structure_Module_Relation::TYPE_MODULE,
			'Source1',
			'1.0.0',
			'needs'
		);

		$source1	= $library->getSource( 'Source1' );
		$library->addModuleToSource( $module1, $source1 );
		$library->addModuleToSource( $module2, $source1 );

		$graph->addModule( $module1 );
		self::assertSame( [], $graph->getErrors() );

		$nodes	= $graph->getNodes();
		self::assertSame( 2, count( $nodes ) );

		$graph->realizeRelations();

		$node1	= $graph->getNode( 'Module1' );
		self::assertSame( 1, count( $node1->out ) );
		self::assertSame( $graph->getNode( 'Module2' ), current( $node1->out ) );

		$loop	= $graph->checkForLoop( $graph->getNode( 'Module1' ) );
		self::assertIsObject( $loop );
		self::assertSame( 'Module1', $loop->node->module->id );
		self::assertSame( 2, count( $loop->steps ) );
		self::assertEquals( [
			$module1->install->path	=> $graph->getNode( 'Module1' ),
			$module2->install->path	=> $graph->getNode( 'Module2' ),
		], $loop->steps );

		$loop	= $graph->checkForLoop( $graph->getNode( 'Module2' ) );
		self::assertIsObject( $loop );
		self::assertSame( 'Module2', $loop->node->module->id );
		self::assertSame( 2, count( $loop->steps ) );
		self::assertEquals( [
			$module2->install->path	=> $graph->getNode( 'Module2' ),
			$module1->install->path	=> $graph->getNode( 'Module1' ),
		], $loop->steps );
	}

}
