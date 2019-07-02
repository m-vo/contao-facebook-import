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
