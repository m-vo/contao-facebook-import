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

use Mvo\ContaoFacebookImport\Element\ContentEventList;
use Mvo\ContaoFacebookImport\Element\ContentPostList;
use Mvo\ContaoFacebookImport\Model\FacebookEventModel;
use Mvo\ContaoFacebookImport\Model\FacebookPostModel;
use Mvo\ContaoFacebookImport\Model\FacebookModel;


// models
$GLOBALS['TL_MODELS']['tl_mvo_facebook']       = FacebookModel::class;
$GLOBALS['TL_MODELS']['tl_mvo_facebook_post']  = FacebookPostModel::class;
$GLOBALS['TL_MODELS']['tl_mvo_facebook_event'] = FacebookEventModel::class;

// BE
$GLOBALS['BE_MOD']['mvo_facebook_integration'] = [
    'mvo_facebook' => [
        'tables'       => ['tl_mvo_facebook', 'tl_mvo_facebook_post', 'tl_mvo_facebook_event'],
        'importPosts'  => ['mvo_contao_facebook.listener.datacontainer.facebook_node', 'onImportPosts'],
        'importEvents' => ['mvo_contao_facebook.listener.datacontainer.facebook_node', 'onImportEvents'],
    ]
];

if ('BE' === TL_MODE) {
    $GLOBALS['TL_CSS'][] = 'bundles/mvocontaofacebookimport/css/backend.css';
}

// FE
$GLOBALS['TL_CTE']['mvo_facebook']['mvo_facebook_post_list']  = ContentPostList::class;
$GLOBALS['TL_CTE']['mvo_facebook']['mvo_facebook_event_list'] = ContentEventList::class;

// data import
$GLOBALS['TL_CRON']['minutely'][] = ['mvo_contao_facebook.listener.import_posts', 'onImportAll'];
$GLOBALS['TL_CRON']['minutely'][] = ['mvo_contao_facebook.listener.import_events', 'onImportAll'];