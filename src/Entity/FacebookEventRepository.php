<?php

namespace Mvo\ContaoFacebookImport\Entity;

use Doctrine\ORM\EntityRepository;

class FacebookEventRepository extends EntityRepository
{
	/**
	 * @param FacebookNode $node
	 *
	 * @return FacebookElement[]
	 */
	public function findByFacebookNode(FacebookNode $node): array
	{
		return $this->findBy(
			['facebookNode' => $node->getId()]
		);
	}

	/**
	 * @param int   $nodeId
	 * @param int   $limit
	 *
	 * @return array
	 */
	public function findVisible(int $nodeId, int $limit = 0): array
	{
		return $this->findBy(
			[
				'facebookNode' => $nodeId,
				'visible'      => true
			],
			[
				'startTime' => 'ASC'
			],
			0 !== $limit ? $limit : null
		);
	}
}
