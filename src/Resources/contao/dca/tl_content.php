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

use Mvo\ContaoFacebookImport\Entity\FacebookPost;

$GLOBALS['TL_DCA']['tl_content']['palettes']['mvo_facebook_post_list'] =
    '{type_legend},type,headline;{mvo_facebook_options_legend},mvo_facebook_node,mvo_facebook_number_of_elements,mvo_facebook_allowed_post_types;{image_legend},size,fullsize;{template_legend:hide},customTpl;{expert_legend:hide},cssID;';

$GLOBALS['TL_DCA']['tl_content']['palettes']['mvo_facebook_event_list'] =
    '{type_legend},type,headline;{mvo_facebook_options_legend},mvo_facebook_node,mvo_facebook_number_of_elements;{image_legend},size,fullsize;{template_legend:hide},customTpl;{expert_legend:hide},cssID;';

$GLOBALS['TL_DCA']['tl_content']['fields']['mvo_facebook_node'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['mvo_facebook_node'],
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_mvo_facebook.description',
    'eval' => [
        'chosen' => true,
        'includeBlankOption' => false,
        'mandatory' => true,
        'tl_class' => 'w50 wizard',
    ],
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
    'sql' => 'int(10) unsigned NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['mvo_facebook_number_of_elements'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['mvo_facebook_number_of_elements'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql' => "smallint(5) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['mvo_facebook_allowed_post_types'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['mvo_facebook_allowed_post_types'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options' => FacebookPost::types,
    'default' => FacebookPost::types,
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => 'text NULL',
];
