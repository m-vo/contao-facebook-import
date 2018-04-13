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

use Contao\Database;
use Contao\Model;

/**
 * Reads and writes projects
 *
 * @property integer $id
 * @property integer $pid
 * @property integer $tstamp
 * @property bool    $visible
 *
 * @property string  $eventId
 * @property string  $name
 * @property string  $description
 * @property integer $startTime
 * @property string  $locationName
 * @property string  $image
 * @property string  $ticketUri
 * @property integer $lastChanged
 *
 * @method static Model\Collection|FacebookEventModel[]|FacebookEventModel|null findByPid($val, array $opt=array())
 */
class FacebookEventModel extends Model
{
    /**
     * Table name
     *
     * @var string
     */
    protected static $strTable = 'tl_mvo_facebook_event';

    /**
     * @param int $pid
     *
     * @return int
     */
    public static function getLastTimestamp($pid)
    {
        $objResult = Database::getInstance()
            ->prepare("SELECT tstamp FROM tl_mvo_facebook_event WHERE pid = ? ORDER BY tstamp DESC LIMIT 1")
            ->execute($pid);

        return (0 == $objResult->numRows) ? 0 : $objResult->tstamp;
    }
}
