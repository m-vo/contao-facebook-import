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

namespace Mvo\ContaoFacebookImport\EventListener\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\DataContainer;
use Contao\Dbafs;
use Contao\Files;
use Contao\FilesModel;
use Doctrine\DBAL\Connection;
use Mvo\ContaoFacebookImport\EventListener\ImportFacebookEventsListener;
use Mvo\ContaoFacebookImport\EventListener\ImportFacebookPostsListener;
use Mvo\ContaoFacebookImport\Facebook\AccessTokenGenerator;
use Psr\Log\LoggerInterface;

class FacebookNode implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /** @var Connection */
    private $database;

    /** @var ImportFacebookPostsListener */
    private $postImporter;

    /** @var ImportFacebookEventsListener */
    private $eventImporter;

    /** @var AccessTokenGenerator */
    private $accessTokenGenerator;

    /** @var LoggerInterface */
    private $logger;

    /**
     * FacebookNode constructor.
     *
     * @param Connection                   $database
     * @param ImportFacebookPostsListener  $postImporter
     * @param ImportFacebookEventsListener $eventImporter
     * @param AccessTokenGenerator         $accessTokenGenerator
     * @param LoggerInterface              $logger
     */
    public function __construct(
        Connection $database,
        ImportFacebookPostsListener $postImporter,
        ImportFacebookEventsListener $eventImporter,
        AccessTokenGenerator $accessTokenGenerator,
        LoggerInterface $logger
    ) {
        $this->database             = $database;
        $this->postImporter         = $postImporter;
        $this->eventImporter        = $eventImporter;
        $this->accessTokenGenerator = $accessTokenGenerator;
        $this->logger               = $logger;
    }


    /**
     * Force import of posts.
     *
     * @param DataContainer $dc
     */
    public function onImportPosts(DataContainer $dc): void
    {
        try {
            $this->postImporter->onImport((int) $dc->id, true);
        } catch (\Exception $e) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->logger->warning(
                sprintf('Facebook Import: Unknown error when importing posts of node ID%s.', $dc->id),
                ['exception' => $e, 'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
        }

        $this->redirectBack();
    }

    /**
     * Force import of events.
     *
     * @param DataContainer $dc
     *
     * @throws \InvalidArgumentException
     */
    public function onImportEvents(DataContainer $dc): void
    {
        try {
            $this->eventImporter->onImport((int) $dc->id, true);
        } catch (\Exception $e) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->logger->warning(
                sprintf('Facebook Import: Unknown error when importing events of node ID%s.', $dc->id),
                ['exception' => $e, 'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
        }

        $this->redirectBack();
    }

    /**
     * Redirect to current listing after action.
     */
    private function redirectBack(): void
    {
        $this->framework->initialize();

        /** @var \Contao\Controller $controller */
        $controller = $this->framework->getAdapter(Controller::class);

        /** @noinspection PhpUndefinedMethodInspection */
        /** @noinspection StaticInvocationViaThisInspection */
        $controller->redirect($controller->addToUrl(null, true, ['key']));
    }

    /**
     * @param DataContainer $dc
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onPrunePostImage(DataContainer $dc): void
    {
        $this->pruneImage($dc, 'SELECT id FROM tl_mvo_facebook_post WHERE image = ? AND id <> ?');
    }


    /**
     * @param DataContainer $dc
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onPruneEventImage(DataContainer $dc): void
    {
        $this->pruneImage($dc, 'SELECT id FROM tl_mvo_facebook_event WHERE image = ? AND id <> ?');
    }

    /**
     * @param DataContainer $dc
     * @param string        $activeElementsSelectorQuery
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function pruneImage(DataContainer $dc, string $activeElementsSelectorQuery): void
    {
        // delete image with record
        $imageUuid = $dc->activeRecord->image;
        if (null === $imageUuid) {
            return;
        }

        $activeElementCount = $this->database
            ->executeQuery($activeElementsSelectorQuery, [$imageUuid, $dc->id])
            ->rowCount();

        if (0 !== $activeElementCount || null === $objFile = FilesModel::findByUuid($imageUuid)) {
            return;
        }

        Files::getInstance()->delete($objFile->path);
        Dbafs::deleteResource($objFile->path);
    }

    /**
     * @param array $row
     *
     * @return string
     */
    public function onGeneratePostLabel(array $row)
    {
        return sprintf('<div class="mvo_facebook_integration_post">%s</div>', nl2br(utf8_decode($row['message'])));
    }


    /**
     * @param string        $varValue
     * @param DataContainer $dc
     */
    public function onGenerateAccessToken(string $varValue, DataContainer $dc): string
    {
        $token = $this->accessTokenGenerator->generateNeverExpiringAccessToken(
            $dc->activeRecord->fbAppId,
            $dc->activeRecord->fbAppSecret,
            $varValue
        );

        if ($token) {
            return $token;
        }

        throw new \InvalidArgumentException('Could not convert access token.');
    }
}