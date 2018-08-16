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

$GLOBALS['TL_LANG']['tl_mvo_facebook']['new'] = ['Neuen Facebook Knoten hinzufügen'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['edit'][1] = 'Facebook Knoten bearbeiten';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['events'][1] = 'Synchronisierte Veranstaltungen anzeigen';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['posts'][1] = 'Synchronisierte Posts anzeigen';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['toggle'][1] = 'Auto-Synchronisierung aktivieren/deaktivieren';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['delete'][1] = 'Facebook Knoten löschen';


$GLOBALS['TL_LANG']['tl_mvo_facebook']['basic_legend'] = 'Allgemeines';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['description'] = ['Bezeichner'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_page_name'] = ['Name oder ID der Facebook Seite'];

$GLOBALS['TL_LANG']['tl_mvo_facebook']['api_legend'] = 'Facebook API';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_app_id'] = ['Facebook App ID'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_app_secret'] = ['Facebook App Secret'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_access_token'] = ['Nie ablaufender Facebook Access Token', 'nie ablaufendes Token eingeben oder reguläres durch Anklicken der nebenstehenden Checkbox und Speichern des Eintrags erzeugen'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['convert_access_token'] = ['Facebook Access Token umwandeln', 'Das System wird automatisch versuchen einen nie ablaufenden Token aus dem angegebenen Token zu generieren.'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['error_converting_token'] = 'Das angegebene Access Token konnte nicht in ein nie ablaufendes umgewandelt werden.';

$GLOBALS['TL_LANG']['tl_mvo_facebook']['import_legend'] = 'Import';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['import_enabled'] = ['Automatische Synchronisierung von Posts und Veranstaltungen aktivieren'];
$GLOBALS['TL_LANG']['tl_mvo_facebook']['number_of_posts'] = ['Post-Anzahl','maximale Anzahl an importierten Posts'];

$GLOBALS['TL_LANG']['tl_mvo_facebook']['media_legend'] = 'Medien';
$GLOBALS['TL_LANG']['tl_mvo_facebook']['upload_directory'] = ['Upload-Ordner', 'Ort, an dem die gescrapten Bilddaten ablegt werden'];