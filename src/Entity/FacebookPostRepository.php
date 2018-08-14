<?php

namespace Mvo\ContaoFacebookImport\Entity;

use Doctrine\ORM\EntityRepository;

class FacebookPostRepository extends EntityRepository
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
	 * @param array $types
	 *
	 * @return array
	 */
	public function findVisible(int $nodeId, int $limit = 0, array $types = FacebookPost::types): array
	{
		if (0 === \count($types)) {
			return [];
		}

		return $this->findBy(
			[
				'facebookNode' => $nodeId,
				'visible'      => true,
				'type'         => $types
			],
			[
				'postTime' => 'DESC'
			],
			0 !== $limit ? $limit : null
		);
	}
}
