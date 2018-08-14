<?php

declare(strict_types=1);

/*
 * Contao Facebook Import Bundle for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2017-2018, Moritz Vondano
 * @license    MIT
 * @link       https://github.com/m-vo/contao-facebook-import
 *
 * @author     Moritz Vondano
 */

namespace Mvo\ContaoFacebookImport\EventListener;

use Doctrine\DBAL\Connection;
use Mvo\ContaoFacebookImport\Database\Update;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DatabaseUpdateListener
{
	/** @var Connection */
	private $connection;

	/**
	 * DatabaseUpdateListener constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * Run all database updates.
	 *
	 * @param array $commands
	 *
	 * @return array
	 */
	public function onCompileSqlCommands(array $commands): array
	{
		/** @var SplFileInfo[] $finder */
		$finder = Finder::create()
			->files()
			->name('Version*.php')
			->sortByName()
			->in(__DIR__ . '/../Database');

		foreach ($finder as $file) {
			$class = 'Mvo\ContaoFacebookImport\Database\\' . $file->getBasename('.php');

			/** @var Update $update */
			$update = new $class($this->connection);

			if ($update instanceof Update && $update->shouldBeRun()) {
				$update->run();
			}
		}

		return $commands;
	}
}