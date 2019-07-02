<?php

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
     * @param int $nodeId
     * @param int $limit
     *
     * @return array
     */
    public function findVisible(int $nodeId, int $limit = 0): array
    {
        return $this->findBy(
            [
                'facebookNode' => $nodeId,
                'visible' => true,
            ],
            [
                'startTime' => 'ASC',
            ],
            0 !== $limit ? $limit : null
        );
    }
}
