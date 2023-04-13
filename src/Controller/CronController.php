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

namespace Mvo\ContaoFacebookImport\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_scope" = "frontend", "_token_check" = true})
 */
class CronController extends AbstractController
{
    /**
     * @Route("/_sync_fb_nodes", name="sync_fb_nodes")
     */
    public function synchronisationAction(Request $request): Response
    {
        if ('route' !== $this->getParameter('mvo_contao_facebook_import.trigger_type')) {
            return new Response(Response::$statusTexts[403], 403);
        }

        $this->get('mvo_contao_facebook.synchronization.scheduler')->run(
            $request->query->has('node') ?
                $request->query->getInt('node') : null
        );

        return new Response(Response::$statusTexts[204], 204);
    }
}
