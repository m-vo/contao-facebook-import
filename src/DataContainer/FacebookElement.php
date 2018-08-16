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

namespace Mvo\ContaoFacebookImport\DataContainer;

use Contao\Config;
use Contao\DataContainer;
use Contao\Frontend;
use Doctrine\Bundle\DoctrineBundle\Registry;
use FrontendTemplate;
use Mvo\ContaoFacebookImport\Entity\FacebookEvent as FacebookEventEntity;
use \Mvo\ContaoFacebookImport\Entity\FacebookPost as FacebookPostEntity;
use Mvo\ContaoFacebookImport\Entity\FacebookImage;
use Mvo\ContaoFacebookImport\Image\ScrapableItemInterface;

class FacebookElement
{
	/** @var Registry */
	private $doctrine;


	/**
	 * FacebookPost constructor.
	 *
	 * @param Registry $doctrine
	 */
	public function __construct(Registry $doctrine)
	{
		$this->doctrine = $doctrine;
	}

	/**
	 * @param DataContainer $dc
	 */
	public function onDeleteEvent(DataContainer $dc): void
	{
		/** @var FacebookEventEntity $element */
		$element = $this->doctrine
			->getRepository(FacebookEventEntity::class)
			->find($dc->id);

		if (null !== $element) {
			$manager = $this->doctrine->getManager();
			$manager->remove($element);
			$manager->flush();
		}
	}

	/**
	 * @param DataContainer $dc
	 */
	public function onDeletePost(DataContainer $dc): void
	{
		/** @var FacebookPostEntity $element */
		$element = $this->doctrine
			->getRepository(FacebookPostEntity::class)
			->find($dc->id);

		if (null !== $element) {
			$manager = $this->doctrine->getManager();
			$manager->remove($element);
			$manager->flush();
		}

	}

	/**
	 * @param array $row
	 *
	 * @return string
	 */
	public function onGenerateEventLabel(array $row): string
	{
		/** @var FacebookEventEntity $element */
		$element = $this->doctrine
			->getRepository(FacebookEventEntity::class)
			->find($row['id']);

		return sprintf(
			'<div class="mvo_facebook_element">%s<div class="mvo_facebook_element-content"><h2>%s</h2><h3>%s</h3>%s</div>',
			$this->getLabelImage($element->getImage()),
			utf8_decode($element->getName()),
			date(Config::get('datimFormat'), $element->getStartTime()),
			nl2br(utf8_decode($element->getDescription()))
		);
	}

	/**
	 * @param array $row
	 *
	 * @return string
	 */
	public function onGeneratePostLabel(array $row): string
	{
		/** @var FacebookPostEntity $element */
		$element = $this->doctrine
			->getRepository(FacebookPostEntity::class)
			->find($row['id']);

		$type = sprintf('<span class="mvo_facebook_post_type">[&thinsp;%s&thinsp;]</span>', $element->getType());

		return sprintf(
			'<div class="mvo_facebook_element">%s%s<div class="mvo_facebook_element-content">%s</div>',
			$type,
			$this->getLabelImage($element->getImage()),
			nl2br(utf8_decode($element->getMessage()))
		);
	}

	/**
	 * @param FacebookImage|null $image
	 *
	 * @return string
	 */
	private function getLabelImage(?FacebookImage $image): string
	{
		if (null === $image) {
			return '';
		}

		if (ScrapableItemInterface::STATE_SUCCESS === $image->getScrapingState()
			&& null !== $file = $image->getFile()) {

			$template = new FrontendTemplate('image');

			Frontend::addImageToTemplate(
				$template,
				[
					'singleSRC' => $file->path,
					'alt'       => $metaData['caption']['caption'] ?? 'Facebook Post Image',
					'size'      => [150, 150]
				]
			);

			return sprintf('<div class="mvo_facebook_element-image">%s</div>', $template->parse());
		}

		return '';
	}
}