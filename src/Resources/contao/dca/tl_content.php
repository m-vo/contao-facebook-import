<?php

/**
 * DCA tl_content
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['mvo_facebook_post_list'] =
    '{type_legend},type,headline;{mvo_facebook_options_legend},mvo_facebook_node,mvo_facebook_numberOfPosts;{image_legend},size,fullsize;{expert_legend:hide},cssID;';

$GLOBALS['TL_DCA']['tl_content']['palettes']['mvo_facebook_event_list'] =
    '{type_legend},type,headline;{mvo_facebook_options_legend},mvo_facebook_node;{image_legend},size,fullsize;{expert_legend:hide},cssID;';

$GLOBALS['TL_DCA']['tl_content']['fields']['mvo_facebook_node'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_content']['mvo_facebook_node'],
    'exclude'    => true,
    'inputType'  => 'select',
    'foreignKey' => 'tl_mvo_facebook.description',
    'eval'       => array('chosen'             => true,
                          'includeBlankOption' => false,
                          'mandatory'          => true,
                          'tl_class'           => 'w50 wizard'
    ),
    'relation'   => array('type' => 'hasOne', 'load' => 'lazy'),
    'sql'        => "int(10) unsigned NULL",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['mvo_facebook_numberOfPosts'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['mvo_facebook_numberOfPosts'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['rgxp' => 'natural', 'tl_class' => 'w50'],
    'sql'       => "smallint(5) unsigned NOT NULL default '0'"
];