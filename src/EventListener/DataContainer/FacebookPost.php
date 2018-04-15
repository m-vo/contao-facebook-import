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
use Mvo\ContaoFacebookImport\Model\FacebookPostModel;

class FacebookPost
{
    /**
     * @param DataContainer $dc
     */
    public function onDelete(DataContainer $dc): void
    {
        if ($post = FacebookPostModel::findByPk($dc->id)) {
            $post->delete();
        }
    }

    /**
     * @param array $row
     *
     * @return string
     */
    public function onGeneratePostLabel(array $row): string
    {
        return sprintf('<div class="mvo_facebook_integration_post">%s</div>', nl2br(utf8_decode($row['message'])));
    }
}