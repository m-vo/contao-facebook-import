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

// backend
$GLOBALS['BE_MOD']['mvo_facebook_integration'] = [
	'mvo_facebook' => [
		'tables'            => ['tl_mvo_facebook', 'tl_mvo_facebook_post', 'tl_mvo_facebook_event'],
		'synchronizePosts'  => ['mvo_contao_facebook.datacontainer.facebook_node', 'onSynchronizePosts'],
		'synchronizeEvents' => ['mvo_contao_facebook.datacontainer.facebook_node', 'onSynchronizeEvents'],
	]
];

if ('BE' === TL_MODE) {
	$GLOBALS['TL_CSS'][] = 'bundles/mvocontaofacebookimport/css/backend.css';
}

// content elements
$GLOBALS['TL_CTE']['mvo_facebook']['mvo_facebook_post_list']  = ContentPostList::class;
$GLOBALS['TL_CTE']['mvo_facebook']['mvo_facebook_event_list'] = ContentEventList::class;

// background synchronization
$GLOBALS['TL_CRON']['minutely'][] = ['mvo_contao_facebook.listener.contao_cron_listener', 'onExecuteByContaoCron'];

$coreVersion = explode(
	'.',
	\Contao\System::getContainer()->getParameter('kernel.packages')['contao/core-bundle']
);
if (0 > version_compare(
        \Contao\System::getContainer()->getParameter('kernel.packages')['contao/core-bundle'],
        '4.5.0'
    ))
{
	// contao/core-bundle < 4.5.0 doesn't support hooks as tagged services
	$GLOBALS['TL_HOOKS']['sqlCompileCommands'][] =
		['mvo_contao_facebook.listener.database_update', 'onCompileSqlCommands'];
}