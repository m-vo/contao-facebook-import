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

$GLOBALS['TL_DCA']['tl_mvo_facebook_event'] =
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
                    'onPruneEventImage'
                ],
                'sql'               =>
                    [
                        'keys' =>
                            [
                                'id'  => 'primary',
                                'pid' => 'index',
                            ]
                    ]
            ],

        // List
        'list'   =>
            [
                'sorting'           =>
                    [
                        'mode'        => 1,
                        'fields'      => ['startTime'],
                        'flag'        => 8,
                        'panelLayout' => 'limit'
                    ],
                'label'             =>
                    [
                        'fields' => ['name'],
                        'format' => '%s',
                    ],
                'global_operations' => [
                    'import' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_event']['import'],
                        'href'  => 'key=importEvents',
                        'class' => 'header_icon',
                        'icon'  => 'sync.svg'
                    ]
                ],
                'operations'        =>
                    [
                        'show'   =>
                            [
                                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_event']['show'],
                                'href'  => 'act=show',
                                'icon'  => 'show.svg'
                            ],
                        'toggle' =>
                            [
                                'label'                => &$GLOBALS['TL_LANG']['tl_mvo_facebook_event']['toggle'],
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
                                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_event']['delete'],
                                'href'  => 'act=delete',
                                'icon'  => 'delete.svg'
                            ]
                    ]
            ],

        // Fields
        'fields' =>
            [
                // contao
                'id'           =>
                    [
                        'sql' => "int(10) unsigned NOT NULL auto_increment"
                    ],
                'pid'          =>
                    [
                        'foreignKey' => 'tl_mvo_facebook.description',
                        'sql'        => "int(10) unsigned NOT NULL default '0'",
                        'relation'   => ['type' => 'belongsTo', 'load' => 'lazy']
                    ],
                'tstamp'       =>
                    [
                        'sql' => "int(10) unsigned NOT NULL default '0'"
                    ],
                'visible'      =>
                    [
                        'exclude'   => true,
                        'default'   => true,
                        'inputType' => 'checkbox',
                        'eval'      => ['isBoolean' => true],
                        'sql'       => "char(1) NOT NULL default '1'"
                    ],

                // facebook
                'eventId'      =>
                    [
                        'sql' => "varchar(255) NOT NULL default ''"
                    ],
                'name'         =>
                    [
                        'sql' => "varchar(255) NOT NULL default ''"
                    ],
                'description'  =>
                    [
                        'sql' => "mediumtext NULL"
                    ],
                'startTime'    =>
                    [
                        'sql' => "int(10) unsigned NOT NULL default '0'"
                    ],
                'locationName' =>
                    [
                        'sql' => "varchar(255) NOT NULL default ''"
                    ],
                'image'        =>
                    [
                        'inputType' => 'fileTree',
                        'sql'       => "binary(16) NULL"
                    ],
                'ticketUri'    =>
                    [
                        'sql' => "varchar(255) NOT NULL default ''"
                    ],
                'lastChanged'  =>
                    [
                        'sql' => "int(10) unsigned NOT NULL default '0'"
                    ],
            ]
    ];