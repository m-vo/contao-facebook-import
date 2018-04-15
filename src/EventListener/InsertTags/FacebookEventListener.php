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

namespace Mvo\ContaoFacebookImport\EventListener\InsertTags;

use Mvo\ContaoFacebookImport\Model\FacebookEventModel;

class FacebookEventListener
{
    /**
     * @param string $tag {{fb_event::eventId::parameter}}
     *
     * @return string|false
     */
    public function onReplaceInsertTags(string $tag)
    {
        $chunks = explode('::', $tag);

        if ('fb_event' !== $chunks[0] || !isset($chunks[2])) {
            return false;
        }

        if (null === $event = FacebookEventModel::findBy('eventId', $chunks[1])) {
            return false;
        };

        if (!$event->visible) {
            return '';
        }

        $parameter = $chunks[2];

        switch ($parameter) {
            case 'id':
            case 'name':
            case 'description':
            case 'locationName':
            case 'ticketUri':
                return nl2br(utf8_decode($event->$parameter));

            case 'imageUuid':
                return $event->image;

            default:
                return false;
        }
    }

}