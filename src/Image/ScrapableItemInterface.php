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

namespace Mvo\ContaoFacebookImport\Image;

interface ScrapableItemInterface
{
    public const STATE_NONE = 0;
    public const STATE_WAITING = 1;
    public const STATE_SUCCESS = 2;
    public const STATE_ERROR = -1;

    /**
     * Get the stored scraping information.
     *
     * @return ScrapingInformation|null
     */
    public function getScrapingInformation(): ?ScrapingInformation;

    /**
     * Indicate scraping with the current scraping information was successful.
     *
     * @param string $imageUuid the scraped image uuid
     */
    public function setScrapingSuccess(string $imageUuid): void;

    /**
     * Indicate scraping with the current scraping information has failed.
     */
    public function setScrapingError(): void;

    /**
     * Set or reset scraping information for this element.
     *
     * @param ScrapingInformation|null $scrapingInformation optional scraping information to set
     */
    public function resetScrapingState(?ScrapingInformation $scrapingInformation = null): void;
}
