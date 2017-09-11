<?php

/**
 * DCA tl_mvo_facebook
 */
$GLOBALS['TL_DCA']['tl_mvo_facebook'] = array
(

    // Config
    'config'      => array
    (
        'dataContainer'    => 'Table',
        'ctable'           => array('tl_mvo_facebook_event', 'tl_mvo_facebook_post'),
        'switchToEdit'     => true,
        'enableVersioning' => true,
        'sql'              => array
        (
            'keys' => array
            (
                'id' => 'primary',
            )
        )
    ),

    // List
    'list'        => array
    (
        'sorting'           => array
        (
            'mode'        => 2,
            'fields'      => array('description'),
            'flag'        => 1,
            'panelLayout' => 'sort,search,limit'
        ),
        'label'             => array
        (
            'fields' => array('description'),
            'format' => '%s',
        ),
        'global_operations' => array(),
        'operations'        => array
        (
            'edit'   => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_theme']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.svg'
            ),
            'events' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['events'],
                'href'  => 'table=tl_mvo_facebook_event',
                'icon'  => 'down.svg'
            ),
            'posts'  => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['posts'],
                'href'  => 'table=tl_mvo_facebook_post',
                'icon'  => 'down.svg'
            ),
            'toggle' => array
            (
                'label'                => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['toggle'],
                'attributes'           => 'onclick="Backend.getScrollOffset();"',
                'haste_ajax_operation' => [
                    'field'   => 'importEnabled',
                    'options' => [
                        [
                            'value' => '',
                            'icon'  => 'invisible.svg'
                        ],
                        [
                            'value' => '1',
                            'icon'  => 'visible.svg'
                        ]
                    ]
                ]
            ),
            'delete' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['delete'],
                'href'  => 'act=delete',
                'icon'  => 'delete.svg'
            )
        )
    ),

    // Select
    'select'      => array
    (
        'buttons_callback' => array()
    ),

    // Edit
    'edit'        => array
    (
        'buttons_callback' => array()
    ),

    // Palettes
    'palettes'    => array
    (
        '__selector__' => array(''),
        'default'      => '{basic_legend},description,fbPageName;' .
                          '{api_legend},fbAppId,fbAppSecret,fbAccessToken;' .
                          '{import_legend},importEnabled,minimumCacheTime,numberOfPosts;' .
                          '{media_legend},uploadDirectory;'
    ),

    // Subpalettes
    'subpalettes' => array
    (),

    // Fields
    'fields'      => array
    (
        'id'     => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp' => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),

        'description'      => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['description'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 255),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'fbAppId'          => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['fbAppId'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'fbAppSecret'      => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['fbAppSecret'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'fbAccessToken'   => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['fbAccessToken'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'fbPageName'       => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['fbPageName'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'minimumCacheTime' => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['minimumCacheTime'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 6, 'rgxp' => 'natural', 'tl_class' => 'w50'),
            'sql'       => "int(6) unsigned NOT NULL default '250'"
        ),
        'numberOfPosts'    => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['numberOfPosts'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => array('mandatory' => true, 'maxlength' => 5, 'rgxp' => 'natural', 'tl_class' => 'w50'),
            'sql'       => "int(5) unsigned NOT NULL default '15'"
        ),
        'importEnabled'    => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['importEnabled'],
            'exclude'   => true,
            'default'   => false,
            'inputType' => 'checkbox',
            'eval'      => array('isBoolean' => true),
            'sql'       => "char(1) NOT NULL default '0'"
        ),
        'uploadDirectory'     => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['uploadDirectory'],
            'exclude'   => true,
            'inputType' => 'fileTree',
            'eval'      => array('mandatory' => true, 'fieldType' => 'radio'),
            'sql'       => "blob NOT NULL"
        )
    )
);