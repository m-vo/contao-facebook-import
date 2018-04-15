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

use Contao\DataContainer;
use Mvo\ContaoFacebookImport\Model\FacebookEventModel;

class FacebookEvent
{
    /**
     * @param DataContainer $dc
     */
    public function onDelete(DataContainer $dc): void
    {
        if ($event = FacebookEventModel::findByPk($dc->id)) {
            $event->delete();
        }
    }
}