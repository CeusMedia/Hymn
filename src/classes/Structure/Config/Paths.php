<?php
class Hymn_Structure_Config_Paths
{
	public const DEFAULTS	= [
		'config'		=> 'config/',
		'classes'		=> 'classes/',
		'images'		=> 'images/',
		'locales'		=> 'locales/',
		'scripts'		=> 'scripts/',
		'templates'		=> 'templates/',
		'themes'		=> 'themes/',
		'logs'			=> 'logs/',
		'cache'			=> 'cache/',
		'contents'		=> 'contents/',
	];

	public ?string $config		= NULL;
	public ?string $classes		= NULL;
	public ?string $images		= NULL;
	public ?string $locales		= NULL;
	public ?string $templates	= NULL;
	public ?string $scripts		= NULL;
	public ?string $themes		= NULL;
	public ?string $logs		= NULL;
	public ?string $cache		= NULL;
	public ?string $contents	= NULL;
}