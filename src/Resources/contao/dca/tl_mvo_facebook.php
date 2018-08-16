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

$GLOBALS['TL_DCA']['tl_mvo_facebook'] =
	[
		// Config
		'config'      =>
			[
				'dataContainer'     => 'Table',
				'ctable'            => ['tl_mvo_facebook_event', 'tl_mvo_facebook_post'],
				'switchToEdit'      => true,
				'enableVersioning'  => true,
				'onsubmit_callback' => [
					[
						'mvo_contao_facebook.datacontainer.facebook_node',
						'onGenerateAccessToken'
					]
				],
				'ondelete_callback' => [
					[
						'mvo_contao_facebook.datacontainer.facebook_node',
						'onDelete'
					]
				]
			],

		// List
		'list'        =>
			[
				'sorting'           =>
					[
						'mode'        => 2,
						'fields'      => ['description'],
						'flag'        => 1,
						'panelLayout' => 'sort,search,limit'
					],
				'label'             =>
					[
						'fields' => ['description'],
						'format' => '%s',
					],
				'global_operations' => [],
				'operations'        =>
					[
						'edit'   =>
							[
								'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['edit'],
								'href'  => 'act=edit',
								'icon'  => 'edit.svg'
							],
						'events' =>
							[
								'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['events'],
								'href'  => 'table=tl_mvo_facebook_event',
								'icon'  => 'bundles/mvocontaofacebookimport/img/events.svg'
							],
						'posts'  =>
							[
								'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['posts'],
								'href'  => 'table=tl_mvo_facebook_post',
								'icon'  => 'bundles/mvocontaofacebookimport/img/posts.svg'
							],
						'toggle' =>
							[
								'label'                => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['toggle'],
								'attributes'           => 'onclick="Backend.getScrollOffset();"',
								'haste_ajax_operation' => [
									'field'   => 'import_enabled',
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
								'label' => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['delete'],
								'href'  => 'act=delete',
								'icon'  => 'delete.svg'
							]
					]
			],

		// Select
		'select'      =>
			[
				'buttons_callback' => []
			],

		// Edit
		'edit'        =>
			[
				'buttons_callback' => []
			],

		// Palettes
		'palettes'    =>
			[
				'__selector__' => [],
				'default'      => '{basic_legend},description,fb_page_name;' .
								  '{api_legend},fb_app_id,fb_app_secret,fb_access_token,convert_access_token;' .
								  '{import_legend},import_enabled,number_of_posts;' .
								  '{media_legend},upload_directory;'
			],

		// Subpalettes
		'subpalettes' =>
			[],

		// Fields
		'fields'      =>
			[
				'id'                   =>
					[
					],
				'tstamp'               =>
					[
					],
				'description'          =>
					[
						'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['description'],
						'exclude'   => true,
						'inputType' => 'text',
						'eval'      => ['mandatory' => true, 'maxlength' => 255],
					],
				'fb_app_id'            =>
					[
						'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_app_id'],
						'exclude'   => true,
						'inputType' => 'text',
						'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
					],
				'fb_app_secret'        =>
					[
						'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_app_secret'],
						'exclude'   => true,
						'inputType' => 'text',
						'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
					],
				'fb_access_token'      =>
					[
						'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_access_token'],
						'exclude'   => true,
						'inputType' => 'text',
						'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
					],
				'convert_access_token' =>
					[
						'label'         => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['convert_access_token'],
						'exclude'       => true,
						'inputType'     => 'checkbox',
						'eval'          => [
							'doNotSaveEmpty' => true,
							'tl_class'       => 'w50 m12'
						],
						'save_callback' => [
							// do not save
							function () {
								return null;
							}
						]
					],
				'fb_page_name'         =>
					[
						'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['fb_page_name'],
						'exclude'   => true,
						'inputType' => 'text',
						'eval'      => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
					],
				'number_of_posts'      =>
					[
						'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['number_of_posts'],
						'exclude'   => true,
						'inputType' => 'text',
						'eval'      => [
							'mandatory' => true,
							'maxlength' => 5,
							'rgxp'      => 'natural',
							'tl_class'  => 'w50'
						],
					],
				'import_enabled'       =>
					[
						'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['import_enabled'],
						'exclude'   => true,
						'default'   => false,
						'inputType' => 'checkbox',
						'eval'      => ['isBoolean' => true],
					],
				'upload_directory'     =>
					[
						'label'     => &$GLOBALS['TL_LANG']['tl_mvo_facebook']['upload_directory'],
						'exclude'   => true,
						'inputType' => 'fileTree',
						'eval'      => ['mandatory' => true, 'fieldType' => 'radio'],
					]
			]
	];