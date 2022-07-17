<?php

declare(strict_types=1);

/*
 * Contao Facebook Import Bundle for Contao Open Source CMS
 *
 * @copyright  Copyright (c), Moritz Vondano
 * @license    MIT
 * @link       https://github.com/m-vo/contao-facebook-import
 *
 * @author     Moritz Vondano
 */

namespace Mvo\ContaoFacebookImport\EventListener;

use Contao\Controller;
use Doctrine\DBAL\Connection;
use Mvo\ContaoFacebookImport\Database\Update;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class DatabaseUpdateListener
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * DatabaseUpdateListener constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Run all database updates.
     */
    public function onCompileSqlCommands(array $commands): array
    {
        /** @var array<SplFileInfo> $finder */
        $finder = Finder::create()
            ->files()
            ->name('Version*.php')
            ->sortByName()
            ->in(__DIR__.'/../Database')
        ;

        foreach ($finder as $file) {
            $class = 'Mvo\ContaoFacebookImport\Database\\'.$file->getBasename('.php');

            /** @var Update $update */
            $update = new $class($this->connection);

            if ($update instanceof Update && $update->shouldBeRun()) {
                $update->run();

                // reload so that the install tool hash is fresh
                Controller::reload();
            }
        }

        return $commands;
    }
}
