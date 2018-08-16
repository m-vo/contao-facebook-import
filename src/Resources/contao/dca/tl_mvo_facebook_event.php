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
					[
						'mvo_contao_facebook.datacontainer.facebook_element',
						'onDeleteEvent'
					]
				],
			],

		// List
		'list'   =>
			[
				'sorting'           =>
					[
						'mode'        => 1,
						'fields'      => ['start_time'],
						'flag'        => 8,
						'panelLayout' => 'limit'
					],
				'label'             =>
					[
						'fields'         => [''],
						'label_callback' => [
							'mvo_contao_facebook.datacontainer.facebook_element',
							'onGenerateEventLabel'
						]
					],
				'global_operations' => [
					'all'    =>
						[
							'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
							'href'       => 'act=select',
							'class'      => 'header_edit_all',
							'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
						],
					'import' =>
						[
							'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook_event']['import'],
							'href'  => 'key=synchronizeEvents',
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
											'value' => false,
											'icon'  => 'invisible.svg'
										],
										[
											'value' => true,
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
				'id'            => [],
				'pid'           =>
					[
						'foreignKey' => 'tl_mvo_facebook.description',
						'relation'   => ['type' => 'belongsTo', 'load' => 'lazy']
					],
				'tstamp'        => [],
				'visible'       =>
					[
						'inputType' => 'checkbox',
						'eval'      => ['isBoolean' => true],
					],
				'fb_event_id'   => [],
				'start_time'    => [],
				'location_name' => [],
				'last_changed'  => [],
			]
	];