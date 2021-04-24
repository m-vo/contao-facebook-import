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

class FacebookPostRepository extends EntityRepository
{
    /**
     * @return array<FacebookElement>
     */
    public function findByFacebookNode(FacebookNode $node): array
    {
        return $this->findBy(
            ['facebookNode' => $node->getId()]
        );
    }

    public function findVisible(int $nodeId, int $limit = 0, array $types = FacebookPost::types): array
    {
        if (0 === \count($types)) {
            return [];
        }

        return $this->findBy(
            [
                'facebookNode' => $nodeId,
                'visible' => true,
                'type' => $types,
            ],
            [
                'postTime' => 'DESC',
            ],
            0 !== $limit ? $limit : null
        );
    }
}
