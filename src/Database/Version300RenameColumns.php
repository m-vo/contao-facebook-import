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

class Version300RenameColumns extends Update
{
    /**
     * {@inheritdoc}
     */
    public function shouldBeRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        if (!$schemaManager->tablesExist(['tl_mvo_facebook'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_mvo_facebook');

        return !isset($columns['fb_app_id']);
    }

    /**
     * {@inheritdoc}
     *
     * @throws DBALException
     */
    public function run(): void
    {
        $this->connection->executeQuery(
            "ALTER TABLE tl_mvo_facebook CHANGE fbAppId fb_app_id VARCHAR(255) DEFAULT '' NOT NULL"
        );
        $this->connection->executeQuery(
            "ALTER TABLE tl_mvo_facebook CHANGE fbAppSecret fb_app_secret VARCHAR(255) DEFAULT '' NOT NULL"
        );
        $this->connection->executeQuery(
            "ALTER TABLE tl_mvo_facebook CHANGE fbAccessToken fb_access_token VARCHAR(255) DEFAULT '' NOT NULL"
        );
        $this->connection->executeQuery(
            "ALTER TABLE tl_mvo_facebook CHANGE fbPageName fb_page_name VARCHAR(255) DEFAULT '' NOT NULL"
        );
        $this->connection->executeQuery(
            "ALTER TABLE tl_mvo_facebook CHANGE importEnabled import_enabled TINYINT(1) DEFAULT '0' NOT NULL"
        );
        $this->connection->executeQuery(
            'ALTER TABLE tl_mvo_facebook CHANGE numberOfPosts number_of_posts INT UNSIGNED DEFAULT 100 NOT NULL'
        );
        $this->connection->executeQuery(
            'ALTER TABLE tl_mvo_facebook CHANGE uploadDirectory upload_directory BINARY(16) DEFAULT NULL'
        );
    }
}
