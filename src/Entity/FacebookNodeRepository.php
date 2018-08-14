<?php

namespace Mvo\ContaoFacebookImport\Entity;

use Doctrine\ORM\EntityRepository;

class FacebookNodeRepository extends EntityRepository
{
	/**
	 * @param int|null $nodeId
	 *
	 * @return FacebookNode[]
	 */
	public function findEnabled(?int $nodeId = null): array
	{
		$criteria = ['importEnabled' => true];

		if (null !== $nodeId) {
			$criteria['id'] = $nodeId;
		}

		return $this->findBy($criteria);
	}
}
