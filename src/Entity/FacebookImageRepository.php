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

namespace Mvo\ContaoFacebookImport\Entity;

use Doctrine\ORM\EntityRepository;
use Mvo\ContaoFacebookImport\Image\ScrapableItemInterface;

class FacebookImageRepository extends EntityRepository
{
    /**
     * @return array<FacebookElement>
     */
    public function findByWaitingToBeScraped(): array
    {
        return $this->findBy(
            [
                'scrapingState' => ScrapableItemInterface::STATE_WAITING,
            ],
            [
                'timestamp' => 'DESC',
            ]
        );
    }
}
