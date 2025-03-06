<?php
class Hymn_Structure_Config
{
	/** @var Hymn_Structure_Config_Application $application */
	public Hymn_Structure_Config_Application $application;

	/** @var Hymn_Structure_Config_Source[] $sources */
	public array $sources		= [];

	/** @var Hymn_Structure_Config_Module[] $modules */
	public array $modules		= [];

	/** @var Hymn_Structure_Config_Paths $paths */
	public Hymn_Structure_Config_Paths $paths;

	/** @var Hymn_Structure_Config_System $application */
	public Hymn_Structure_Config_System $system;

	/** @var Hymn_Structure_Config_Database $application */
	public Hymn_Structure_Config_Database $database;

	public Hymn_Structure_Config_Layout $layout;

	public function __construct()
	{
		$this->application	= new Hymn_Structure_Config_Application();
		$this->system		= new Hymn_Structure_Config_System();
		$this->database		= new Hymn_Structure_Config_Database();
		$this->paths		= new Hymn_Structure_Config_Paths();
		$this->layout		= new Hymn_Structure_Config_Layout();
	}
}