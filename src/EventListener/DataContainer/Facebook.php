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

use Contao\BackendUser;
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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class Facebook implements FrameworkAwareInterface, ContainerAwareInterface
{
    use FrameworkAwareTrait;
    use ContainerAwareTrait;

    /** @var Connection */
    private $database;

    /** @var ImportFacebookPostsListener */
    private $postImporter;

    /** @var ImportFacebookEventsListener */
    private $eventImporter;

    /** @var AccessTokenGenerator */
    private $facebookAccessTokenGenerator;

    /** @var TokenInterface */
    private $userToken;

    /** @var LoggerInterface */
    private $logger;

    /**
     * FacebookNode constructor.
     *
     * @param ImportFacebookPostsListener  $postImporter
     * @param ImportFacebookEventsListener $eventImporter
     * @param AccessTokenGenerator         $facebookAccessTokenGenerator
     * @param TokenStorageInterface        $userTokenStorage
     * @param Connection                   $connection
     * @param LoggerInterface              $logger
     */
    public function __construct(
        ImportFacebookPostsListener $postImporter,
        ImportFacebookEventsListener $eventImporter,
        AccessTokenGenerator $facebookAccessTokenGenerator,
        TokenStorageInterface $userTokenStorage,
        Connection $connection,
        LoggerInterface $logger
    ) {
        $this->postImporter                 = $postImporter;
        $this->eventImporter                = $eventImporter;
        $this->facebookAccessTokenGenerator = $facebookAccessTokenGenerator;
        $this->userToken                    = $userTokenStorage->getToken();
        $this->database                     = $connection;
        $this->logger                       = $logger;
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
        $token = $this->facebookAccessTokenGenerator->generateNeverExpiringAccessToken(
            $dc->activeRecord->fbAppId,
            $dc->activeRecord->fbAppSecret,
            $varValue
        );

        if ($token) {
            return $token;
        }

        throw new \InvalidArgumentException('Could not convert access token.');
    }

    /**
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function onGetCalendars(): array
    {
        if (!array_key_exists('ContaoCalendarBundle', $this->container->getParameter('kernel.bundles'))) {
            return [];
        }

        /** @var BackendUser $user */
        $user = $this->userToken->getUser();

        if (!$user || (!$user->isAdmin && !\is_array($user->forms))) {
            return [];
        }

        $calendarCandidates = $this->database
            ->executeQuery('SELECT id, title FROM tl_calendar ORDER BY title')
            ->fetchAll(\PDO::FETCH_KEY_PAIR);

        $calendars = [];
        foreach ($calendarCandidates as $id => $title) {
            if ($user->hasAccess($id, 'calendars')) {
                $calendars[$id] = $title . ' (ID ' . $id . ')';
            }
        }

        return $calendars;
    }
}