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
$GLOBALS['TL_LANG']['tl_mvo_facebook']['events'][1] = 'Show synchronized events';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['posts'][1] = 'Show synchronized posts';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['toggle'][1] = 'Activate/Deactivate auto synchronization';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['delete'][1] = 'Delete Facebook node';


$GLOBALS['TL_LANG']['tl_mvo_facebook']['basic_legend'] = 'Basics';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['description'] = ['Description'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_page_name'] = ['Name or ID of the Facebook page'];

$GLOBALS['TL_LANG']['tl_mvo_facebook']['api_legend'] = 'Facebook API';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_app_id'] = ['Facebook App ID'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_app_secret'] = ['Facebook App Secret'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_access_token'] = ['Facebook Access Token', 'Either enter a never expiring token or enter a regular one, hit the alongside checkbox and save the record.'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['convert_access_token'] = ['Convert Facebook Access Token', 'The system will automatically try to generate a never expiring token from your specified token.'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['error_converting_token'] = 'The specified Access Token could not be converted to a never expiring one.';

$GLOBALS['TL_LANG']['tl_mvo_facebook']['import_legend'] = 'Import';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['import_enabled'] = ['Enable automatic synchronization of posts and events'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['number_of_posts'] = ['Number of posts','maximal number of posts to be imported'];

$GLOBALS['TL_LANG']['tl_mvo_facebook']['media_legend'] = 'Media';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['upload_directory'] = ['Upload Directory', 'Choose where to upload the scraped images'];