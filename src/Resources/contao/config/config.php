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

use Mvo\ContaoFacebookImport\Element\ContentEventList;
use Mvo\ContaoFacebookImport\Element\ContentPostList;

// backend
$GLOBALS['BE_MOD']['mvo_facebook_integration'] = [
    'mvo_facebook' => [
        'tables' => ['tl_mvo_facebook', 'tl_mvo_facebook_post', 'tl_mvo_facebook_event'],
        'synchronizePosts' => ['mvo_contao_facebook.datacontainer.facebook_node', 'onSynchronizePosts'],
        'synchronizeEvents' => ['mvo_contao_facebook.datacontainer.facebook_node', 'onSynchronizeEvents'],
    ],
];

// content elements
$GLOBALS['TL_CTE']['mvo_facebook']['mvo_facebook_post_list'] = ContentPostList::class;
$GLOBALS['TL_CTE']['mvo_facebook']['mvo_facebook_event_list'] = ContentEventList::class;
