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

namespace Mvo\ContaoFacebookImport\Database;

use Doctrine\DBAL\DBALException;

class Version300DropTables extends Update
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_mvo_facebook_post'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_mvo_facebook_post');

        return !isset($columns['fb_post_id']);
    }

    /**
     * {@inheritdoc}
     *
     * @throws DBALException
     */
    public function run(): void
    {
        $this->connection->executeQuery(
            'DROP TABLE IF EXISTS tl_mvo_facebook_post'
        );
        $this->connection->executeQuery(
            'DROP TABLE IF EXISTS tl_mvo_facebook_event'
        );
    }
}
