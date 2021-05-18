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

namespace Mvo\ContaoFacebookImport\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Mvo\ContaoFacebookImport\Entity\FacebookNode as FacebookNodeEntity;
use Mvo\ContaoFacebookImport\GraphApi\AccessTokenGenerator;
use Mvo\ContaoFacebookImport\Synchronization\Scheduler;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class FacebookNode implements FrameworkAwareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;
    use FrameworkAwareTrait;

    /** @var Registry */
    private $doctrine;

    /** @var Scheduler */
    private $scheduler;

    /** @var AccessTokenGenerator */
    private $facebookAccessTokenGenerator;

    /**
     * FacebookNode constructor.
     */
    public function __construct(
        Registry $doctrine,
        Scheduler $scheduler,
        AccessTokenGenerator $facebookAccessTokenGenerator
    ) {
        $this->doctrine = $doctrine;
        $this->scheduler = $scheduler;
        $this->facebookAccessTokenGenerator = $facebookAccessTokenGenerator;
    }

    public function onDelete(DataContainer $dc): void
    {
        /** @var FacebookNodeEntity $element */
        $element = $this->doctrine
            ->getRepository(FacebookNodeEntity::class)
            ->find($dc->id);

        if (null !== $element) {
            $manager = $this->doctrine->getManager();
            $manager->remove($element);
            $manager->flush();
        }
    }

    /**
     * Force import of posts.
     */
    public function onSynchronizePosts(DataContainer $dc): void
    {
        $node = $this->doctrine
            ->getRepository(FacebookNodeEntity::class)
            ->find((int) $dc->id);

        if (null !== $node) {
            $this->scheduler->synchronizePosts($node);
        }

        $this->redirectBack();
    }

    /**
     * Force import of events.
     */
    public function onSynchronizeEvents(DataContainer $dc): void
    {
        $node = $this->doctrine
            ->getRepository(FacebookNodeEntity::class)
            ->find((int) $dc->id);

        if (null !== $node) {
            $this->scheduler->synchronizeEvents($node);
        }

        $this->redirectBack();
    }

    public function onGenerateAccessToken(DataContainer $dc): void
    {
        if (!Input::post('convert_access_token')) {
            return;
        }

        $token = $this->facebookAccessTokenGenerator->generateNeverExpiringAccessToken(
            $dc->activeRecord->fb_app_id ?? '',
            $dc->activeRecord->fb_app_secret ?? '',
            $dc->activeRecord->fb_access_token ?? ''
        );

        if (!$token) {
            Message::addError($GLOBALS['TL_LANG']['tl_mvo_facebook']['error_converting_token']);
        }
    }

    /**
     * Redirect to current listing after action.
     */
    private function redirectBack(): void
    {
        $this->framework->initialize();

        Controller::redirect(Controller::addToUrl('', true, ['key']));
    }
}
