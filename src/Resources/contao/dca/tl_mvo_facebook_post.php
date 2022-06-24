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

$GLOBALS['TL_DCA']['tl_mvo_facebook_post'] =
    [
        // Config
        'config' => [
                'dataContainer' => 'Table',
                'ptable' => 'tl_mvo_facebook',
                'enableVersioning' => false,
                'notEditable' => true,
                'closed' => true,
                'ondelete_callback' => [
                    [
                        'mvo_contao_facebook.datacontainer.facebook_element',
                        'onDeletePost',
                    ],
                ],
            ],

        // List
        'list' => [
                'sorting' => [
                        'mode' => 1,
                        'fields' => ['post_time'],
                        'flag' => 8,
                        'panelLayout' => 'limit',
                    ],
                'label' => [
                        'fields' => [''],
                        'label_callback' => [
                            'mvo_contao_facebook.datacontainer.facebook_element',
                            'onGeneratePostLabel',
                        ],
                    ],
                'global_operations' => [
                    'all' => [
                            'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                            'href' => 'act=select',
                            'class' => 'header_edit_all',
                            'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
                        ],
                    'import' => [
                            'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_post']['import'],
                            'href' => 'key=synchronizePosts',
                            'class' => 'header_icon',
                            'icon' => 'sync.svg',
                        ],
                ],
                'operations' => [
                        'show' => [
                                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_post']['show'],
                                'href' => 'act=show',
                                'icon' => 'show.svg',
                            ],
                        'toggle' => [
                                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_post']['toggle'],
                                'attributes' => 'onclick="Backend.getScrollOffset();"',
                                'haste_ajax_operation' => [
                                    'field' => 'visible',
                                    'options' => [
                                        [
                                            'value' => '0',
                                            'icon' => 'invisible.svg',
                                        ],
                                        [
                                            'value' => '1',
                                            'icon' => 'visible.svg',
                                        ],
                                    ],
                                ],
                            ],
                        'delete' => [
                                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_post']['delete'],
                                'href' => 'act=delete',
                                'icon' => 'delete.svg',
                            ],
                    ],
            ],

        // Fields
        'fields' => [
                'id' => [],
                'pid' => [
                        'foreignKey' => 'tl_mvo_facebook.description',
                    ],
                'tstamp' => [],
                'visible' => [
                        'inputType' => 'checkbox',
                        'eval' => ['isBoolean' => true],
                        'sql' => ['type' => 'boolean', 'default' => false]
                    ],
                'fb_post_id' => [],
                'type' => [],
                'post_time' => [],
                'link' => [],
                'last_changed' => [],
            ],
    ];
