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
use Doctrine\DBAL\Connection;
use Mvo\ContaoFacebookImport\EventListener\ImportFacebookEventsListener;
use Mvo\ContaoFacebookImport\EventListener\ImportFacebookPostsListener;
use Mvo\ContaoFacebookImport\Facebook\AccessTokenGenerator;
use Psr\Log\LoggerInterface;

class Facebook implements FrameworkAwareInterface
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
        ImportFacebookPostsListener $postImporter,
        ImportFacebookEventsListener $eventImporter,
        AccessTokenGenerator $accessTokenGenerator,
        LoggerInterface $logger
    ) {
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
     * @param string        $varValue
     * @param DataContainer $dc
     *
     * @return string
     * @throws \InvalidArgumentException
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