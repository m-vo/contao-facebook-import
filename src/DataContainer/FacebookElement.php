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

namespace Mvo\ContaoFacebookImport\DataContainer;

use Contao\Config;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Frontend;
use Contao\FrontendTemplate;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Mvo\ContaoFacebookImport\Entity\FacebookEvent as FacebookEventEntity;
use Mvo\ContaoFacebookImport\Entity\FacebookImage;
use Mvo\ContaoFacebookImport\Entity\FacebookPost as FacebookPostEntity;
use Mvo\ContaoFacebookImport\Image\ScrapableItemInterface;

class FacebookElement
{
    /** @var Registry */
    private $doctrine;

    /**
     * FacebookPost constructor.
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

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

    public function onGeneratePostLabel(array $row): string
    {
        /** @var FacebookPostEntity $element */
        $element = $this->doctrine
            ->getRepository(FacebookPostEntity::class)
            ->find($row['id']);

        $type = sprintf('<span class="mvo_facebook_post-type">[&thinsp;%s&thinsp;]</span>', $element->getType());
        $text = nl2br(utf8_decode($element->getMessage()));
        if (\in_array($element->getType(), ['link', 'video'], true)) {
            $text = sprintf(
                '%s%s<span class="mvo_facebook_post-link">[&thinsp;%s&thinsp;]</span>',
                $text,
                '' !== $text ? '<br><br>' : '',
                $element->getLink()
            );
        }

        return sprintf(
            '<div class="mvo_facebook_element">%s%s<div class="mvo_facebook_element-content">%s</div>',
            $type,
            $this->getLabelImage($element->getImage()),
            $text
        );
    }

    private function getLabelImage(?FacebookImage $image): string
    {
        if (null === $image) {
            return '';
        }

        if (ScrapableItemInterface::STATE_WAITING === $image->getScrapingState()) {
            Controller::loadLanguageFile('elements');

            return sprintf(
                '<div class="mvo_facebook_element-image-placeholder"><p>%s</p></div>',
                $GLOBALS['TL_LANG']['MSC']['mvo_facebook_imagesWaiting']
            );
        }

        if (ScrapableItemInterface::STATE_SUCCESS === $image->getScrapingState()
            && null !== $file = $image->getFile()) {
            $template = new FrontendTemplate('image');

            Frontend::addImageToTemplate(
                $template,
                [
                    'singleSRC' => $file->path,
                    'alt' => $metaData['caption']['caption'] ?? 'Facebook Post Image',
                    'size' => [150, 150],
                ]
            );

            return sprintf('<div class="mvo_facebook_element-image">%s</div>', $template->parse());
        }

        return '';
    }
}
