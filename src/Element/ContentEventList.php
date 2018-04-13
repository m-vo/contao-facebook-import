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

namespace Mvo\ContaoFacebookImport\Element;


use Contao\BackendTemplate;
use Contao\Config;
use Contao\ContentElement;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Mvo\ContaoFacebookImport\Facebook\Tools;
use Mvo\ContaoFacebookImport\Model\FacebookEventModel;

/**
 * @property int  mvo_facebook_node
 * @property bool fullsize
 */
class ContentEventList extends ContentElement
{
    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'ce_mvo_facebook_event_list';

    /**
     * Parse the template
     *
     * @return string Parsed element
     */
    public function generate(): string
    {
        if (TL_MODE === 'BE') {
            $objTemplate        = new BackendTemplate('be_wildcard');
            $objTemplate->title = 'Facebook Events';
            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Compile the content element
     *
     * @return void
     */
    protected function compile(): void
    {
        $this->Template = new FrontendTemplate($this->strTemplate);
        $this->Template->setData($this->arrData);

        // get events
        $objEvents = FacebookEventModel::findBy(
            ['pid = ? AND visible = ?'],
            [$this->mvo_facebook_node, true],
            ['order' => 'startTime']
        );

        $arrEvents = [];
        if (null !== $objEvents) {
            $i     = 0;
            $total = $objEvents->count();

            /** @var FacebookEventModel $event */
            foreach ($objEvents as $event) {
                // base data
                $arrEvent = [
                    'eventId'      => $event->eventId,
                    'name'         => Tools::formatText($event->name),
                    'description'  => Tools::formatText($event->description),
                    'locationName' => Tools::formatText($event->locationName),
                    'time'         => $event->startTime,
                    'datetime'     => date(Config::get('datimFormat'), $event->startTime),
                    'href'         => sprintf('https://facebook.com/%s', $event->eventId),
                ];

                // css enumeration
                $arrEvent['class'] = ((1 === $i % 2) ? ' even' : ' odd') .
                                     ((0 === $i) ? ' first' : '') .
                                     (($total - 1 === $i) ? ' last' : '');
                $i++;

                // image
                if (null !== $event->image
                    && null !== $objFile = FilesModel::findByUuid($event->image)
                ) {
                    $objImageTemplate = new FrontendTemplate('image');

                    $arrMeta = deserialize($objFile->meta, true);
                    $strAlt  = (\array_key_exists('caption', $arrMeta)
                                && \is_array($arrMeta['caption'])
                                && \array_key_exists('caption', $arrMeta['caption']))
                               && '' !== $arrMeta['caption']['caption']
                        ? $arrMeta['caption']['caption'] : 'Facebook Post Image';

                    static::addImageToTemplate(
                        $objImageTemplate,
                        [
                            'singleSRC' => $objFile->path,
                            'alt'       => $strAlt,
                            'size'      => deserialize($this->size),
                            'fullsize'  => $this->fullsize
                        ]
                    );
                    $arrEvent['image']    = $objImageTemplate->parse();
                    $arrEvent['hasImage'] = true;
                } else {
                    $arrEvent['hasImage'] = false;
                }

                $arrEvents[] = $arrEvent;
            }
        }

        $this->Template->events    = $arrEvents;
        $this->Template->hasEvents = 0 !== \count($arrEvents);

        if (!$this->Template->hasEvents) {
            self::loadLanguageFile('templates');
            $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['mvo_facebook_emptyEventList'];
        }
    }
}