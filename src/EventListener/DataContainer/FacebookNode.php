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

    /** @var LoggerInterface */
    private $logger;

    /**
     * FacebookNode constructor.
     *
     * @param ImportFacebookPostsListener  $postImporter
     * @param ImportFacebookEventsListener $eventImporter
     * @param LoggerInterface              $logger
     */
    public function __construct(
        ImportFacebookPostsListener $postImporter,
        ImportFacebookEventsListener $eventImporter,
        LoggerInterface $logger
    ) {
        $this->postImporter  = $postImporter;
        $this->eventImporter = $eventImporter;
        $this->logger        = $logger;
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
}