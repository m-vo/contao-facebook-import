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

$GLOBALS['TL_LANG']['tl_mvo_facebook']['new'] = ['Add new Facebook node'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['edit'][1] = 'Edit Facebook node';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['events'][1] = 'Show imported events';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['posts'][1] = 'Show imported posts';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['toggle'][1] = 'Activate/Deactivate auto import';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['delete'][1] = 'Delete Facebook node';


$GLOBALS['TL_LANG']['tl_mvo_facebook']['basic_legend'] = 'Basics';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['description'] = ['Description'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fbPageName'] = ['Name or ID of the Facebook page'];

$GLOBALS['TL_LANG']['tl_mvo_facebook']['api_legend'] = 'Facebook API';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fbAppId'] = ['Facebook App ID'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fbAppSecret'] = ['Facebook App Secret'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fbAccessToken'] = ['Never-expiring Facebook Access Token', 'The system will automatically try to generate a never expiring token from your specified token.'];

$GLOBALS['TL_LANG']['tl_mvo_facebook']['import_legend'] = 'Import';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['importEnabled'] = ['Enable auto import'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['minimumCacheTime'] = ['Minimal Cache Age', 'minimal time in seconds before reimporting'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['numberOfPosts'] = ['Number of posts','maximal number of posts to be imported'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['createNativeEvents'] = ['Create a native Contao events','Adds Contao events on import that reference the facebook events.'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['calendarId'] = ['Calendar', 'Choose the calendar into which the events should get inserted.'];

$GLOBALS['TL_LANG']['tl_mvo_facebook']['media_legend'] = 'Media';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['uploadDirectory'] = ['Upload Directory', 'Choose where to upload the scraped images'];

