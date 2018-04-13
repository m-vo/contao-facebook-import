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

$GLOBALS['TL_DCA']['tl_mvo_facebook_post'] =
    [

        // Config
        'config' =>
            [
                'dataContainer'     => 'Table',
                'ptable'            => 'tl_mvo_facebook',
                'enableVersioning'  => false,
                'notEditable'       => true,
                'closed'            => true,
                'ondelete_callback' => [
                    'mvo_contao_facebook.listener.datacontainer.facebook_node',
                    'onPrunePostImage'
                ],
                'sql'               =>
                    [
                        'keys' =>
                            [
                                'id'  => 'primary',
                                'pid' => 'index',
                            ]
                    ],
            ],

        // List
        'list'   =>
            [
                'sorting'           =>
                    [
                        'mode'        => 1,
                        'fields'      => ['postTime'],
                        'flag'        => 8,
                        'panelLayout' => 'limit'
                    ],
                'label'             =>
                    [
                        'fields' => ['message'],
                        'label_callback' => [
                            'mvo_contao_facebook.listener.datacontainer.facebook_node',
                            'onGeneratePostLabel'
                        ]
                    ],
                'global_operations' => [
                    'import' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_post']['import'],
                        'href'  => 'key=importPosts',
                        'class' => 'header_icon',
                        'icon'  => 'sync.svg',
                    ]
                ],
                'operations'        =>
                    [
                        'show'   =>
                            [
                                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_post']['show'],
                                'href'  => 'act=show',
                                'icon'  => 'show.svg'
                            ],
                        'toggle' =>
                            [
                                'label'                => &$GLOBALS['TL_LANG']['tl_mvo_facebook_post']['toggle'],
                                'attributes'           => 'onclick="Backend.getScrollOffset();"',
                                'haste_ajax_operation' => [
                                    'field'   => 'visible',
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
                            ],
                        'delete' =>
                            [
                                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_post']['delete'],
                                'href'  => 'act=delete',
                                'icon'  => 'delete.svg'
                            ]
                    ]
            ],

        // Fields
        'fields' =>
            [
                // contao
                'id'          =>
                    [
                        'sql' => "int(10) unsigned NOT NULL auto_increment"
                    ],
                'pid'         =>
                    [
                        'foreignKey' => 'tl_mvo_facebook.description',
                        'sql'        => "int(10) unsigned NOT NULL default '0'",
                        'relation'   => ['type' => 'belongsTo', 'load' => 'lazy']
                    ],
                'tstamp'      =>
                    [
                        'sql' => "int(10) unsigned NOT NULL default '0'"
                    ],
                'visible'     =>
                    [
                        'default'   => true,
                        'inputType' => 'checkbox',
                        'eval'      => ['isBoolean' => true],
                        'sql'       => "char(1) NOT NULL default '1'"
                    ],

                // facebook
                'postId'      =>
                    [
                        'sql' => "varchar(255) NOT NULL default ''"
                    ],
                'postTime'    =>
                    [
                        'sql' => "int(10) unsigned NOT NULL default '0'"
                    ],
                'message'     =>
                    [
                        'sql' => "mediumtext NULL"
                    ],
                'image'       =>
                    [
                        'inputType' => 'fileTree',
                        'sql'       => "binary(16) NULL"
                    ],
                'lastChanged' =>
                    [
                        'sql' => "int(10) unsigned NOT NULL default '0'"
                    ],
            ]
    ];