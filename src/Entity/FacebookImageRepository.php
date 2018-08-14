<?php

namespace Mvo\ContaoFacebookImport\Entity;

use Doctrine\ORM\EntityRepository;
use Mvo\ContaoFacebookImport\Image\ScrapableItemInterface;

class FacebookImageRepository extends EntityRepository
{
	/**
	 * @return FacebookElement[]
	 */
	public function findByWaitingToBeScraped(): array
	{
		return $this->findBy(
			[
				'scrapingState' => ScrapableItemInterface::STATE_WAITING
			],
			[
				'timestamp' => 'DESC'
			]
		);
	}
}
