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
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Mvo\ContaoFacebookImport\Entity\FacebookEvent;
use Mvo\ContaoFacebookImport\String\Tools;

/**
 * @property int  mvo_facebook_node
 * @property int  mvo_facebook_number_of_elements
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
			self::loadLanguageFile('elements');

			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->title    = 'Facebook Events';
			$objTemplate->wildcard = sprintf(
				$GLOBALS['TL_LANG']['MSC']['mvo_facebook_eventListDisplay'],
				($this->mvo_facebook_number_of_elements
				 > 0) ? $this->mvo_facebook_number_of_elements : $GLOBALS['TL_LANG']['MSC']['mvo_facebook_allAvailable']
			);

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
		$doctrine = self::getContainer()->get('doctrine');

		// retrieve events
		$events = $doctrine
			->getRepository(FacebookEvent::class)
			->findVisible(
				(int) $this->mvo_facebook_node,
				(int) $this->mvo_facebook_number_of_elements
			);

		// compile events
		$compiledEvents = [];
		foreach ($events as $event) {
			$compiledEvents[] = $this->compileEvent($event);
		}

		$this->Template = new FrontendTemplate($this->strTemplate);
		$this->Template->setData($this->arrData);

		$this->Template->events    = $compiledEvents;
		$this->Template->hasEvents = 0 !== \count($compiledEvents);

		if (!$this->Template->hasEvents) {
			self::loadLanguageFile('elements');
			$this->Template->empty = $GLOBALS['TL_LANG']['MSC']['mvo_facebook_emptyEventList'];
		}
	}

	/**
	 * @param FacebookEvent $event
	 *
	 * @return array
	 */
	private function compileEvent(FacebookEvent $event): array
	{
		// base data
		$compiledEvent = [
			'id'           => $event->getId(),
			'eventId'      => $event->getEventId(),
			'lastChanged'  => $event->getLastChanged(),
			'name'         => Tools::formatText($event->getName()),
			'description'  => Tools::formatText($event->getDescription()),
			'locationName' => Tools::formatText($event->getLocationName()),
			'ticketUri'    => $event->getTicketUri(),
			'time'         => $event->getStartTime(),
			'datetime'     => date(Config::get('datimFormat'), (int) $event->getStartTime()),
			'href'         => sprintf('https://facebook.com/%s', $event->getEventId()),
		];

		// image
		if (null !== ($image = $event->getImage())
			&& null !== ($file = $image->getFile())) {

			$metaData = StringUtil::deserialize($file->meta, true);

			$imageTemplate = new FrontendTemplate('image');
			self::addImageToTemplate(
				$imageTemplate,
				[
					'singleSRC' => $file->path,
					'alt'       => $metaData['caption']['caption'] ?? 'Facebook Event Image',
					'size'      => StringUtil::deserialize($this->size),
					'fullsize'  => $this->fullsize
				]
			);
			$compiledEvent['image']    = $imageTemplate->parse();
			$compiledEvent['hasImage'] = true;
		} else {
			$compiledEvent['hasImage'] = false;
		}

		return $compiledEvent;
	}
}