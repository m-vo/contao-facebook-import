<?php

/**
 * DCA tl_mvo_facebook_event
 */
$GLOBALS['TL_DCA']['tl_mvo_facebook_event'] = array
(

    // Config
    'config' => array
    (
        'dataContainer'     => 'Table',
        'ptable'            => 'tl_mvo_facebook',
        'enableVersioning'  => false,
        'notEditable'       => true,
        'closed'            => true,
        'ondelete_callback' => array(
            function (DataContainer $dc) {
                // delete image with record
                $imageUuid = $dc->activeRecord->image;
                if (null != $imageUuid) {
                    if (Database::getInstance()
                            ->prepare("SELECT id FROM tl_mvo_facebook_event WHERE image = ? AND id <> ?")
                            ->execute($imageUuid, $dc->id)
                            ->numRows == 0
                    ) {
                        $objFile = FilesModel::findByUuid($imageUuid);
                        if ($objFile != null) {
                            Files::getInstance()->delete($objFile->path);
                            Dbafs::deleteResource($objFile->path);
                        }
                    }
                };
            }
        ),
        'sql'               => array
        (
            'keys' => array
            (
                'id'  => 'primary',
                'pid' => 'index',
            )
        )
    ),

    // List
    'list'   => array
    (
        'sorting'           => array
        (
            'mode'   => 1,
            'fields' => array('startTime'),
            'flag'   => 8,
            'panelLayout' => 'limit'
        ),
        'label'             => array
        (
            'fields' => array('name'),
            'format' => '%s',
        ),
        'global_operations' => array(
            'import' => array(
                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_event']['import'],
                'href'  => 'key=importEvents',
                'class' => 'header_icon',
                'icon'  => 'sync.svg'
            )
        ),
        'operations'        => array
        (
            'show'   => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_event']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.svg'
            ),
            'toggle' => array
            (
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
            ),
            'delete' => array
            (
                'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_event']['delete'],
                'href'  => 'act=delete',
                'icon'  => 'delete.svg'
            )
        )
    ),

    // Fields
    'fields' => array
    (
        // contao
        'id'           => array
        (
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'pid'          => array
        (
            'foreignKey' => 'tl_mvo_facebook.description',
            'sql'        => "int(10) unsigned NOT NULL default '0'",
            'relation'   => array('type' => 'belongsTo', 'load' => 'lazy')
        ),
        'tstamp'       => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'visible'      => array
        (
            'exclude'   => true,
            'default'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('isBoolean' => true),
            'sql'       => "char(1) NOT NULL default '1'"
        ),

        // facebook
        'eventId'      => array
        (
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'name'         => array
        (
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'description'  => array
        (
            'sql' => "mediumtext NULL"
        ),
        'startTime'    => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'locationName' => array
        (
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'image'        => array
        (
            'inputType' => 'fileTree',
            'sql' => "binary(16) NULL"
        ),
        'ticketUri'    => array
        (
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'lastChanged'  => array
        (
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
    )
);