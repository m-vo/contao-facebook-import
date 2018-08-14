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

namespace Mvo\ContaoFacebookImport\Database;

use Doctrine\DBAL\Connection;

abstract class Update
{
	/** @var Connection */
	protected $connection;

	/**
	 * @return bool
	 */
	abstract public function shouldBeRun(): bool;

	/**
	 * @return void
	 */
	abstract public function run(): void;


	/**
	 * Update constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}
}