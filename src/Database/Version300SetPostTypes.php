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

class Version300SetPostTypes extends Update
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_content'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_content');

        return !isset($columns['mvo_facebook_allowed_post_types']);
    }

    /**
     * {@inheritdoc}
     *
     * @throws DBALException
     */
    public function run(): void
    {
        $this->connection->executeQuery(
            'ALTER TABLE tl_content ADD mvo_facebook_allowed_post_types TEXT DEFAULT NULL'
        );

        $this->connection->executeQuery(
            "UPDATE tl_content SET mvo_facebook_allowed_post_types = ?  WHERE type='mvo_facebook_post_list'",
            [serialize(['status', 'photo', 'event', 'album'])]
        );
    }
}
