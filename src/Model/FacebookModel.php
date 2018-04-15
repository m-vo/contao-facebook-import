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

namespace Mvo\ContaoFacebookImport\Model;

use Contao\Model;

/**
 * Reads and writes projects
 *
 * @property integer $id
 * @property integer $tstamp
 *
 * @property string  $description
 * @property string  $fbAppId
 * @property string  $fbAppSecret
 * @property string  $fbAccessToken
 * @property string  $fbPageName
 * @property integer $minimumCacheTime
 * @property integer $numberOfPosts
 * @property bool    $importEnabled
 * @property string  $uploadDirectory
 * @property bool    $createNativeEvents
 * @property integer $calendarId
 * @property integer calendarEventAuthor
 *
 * @method static FacebookModel|null findById($id, array $opt = array())
 *
 */
class FacebookModel extends Model
{
    /**
     * Table name
     *
     * @var string
     */
    protected static $strTable = 'tl_mvo_facebook';
}
