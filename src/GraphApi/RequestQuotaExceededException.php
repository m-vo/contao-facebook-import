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

namespace Mvo\ContaoFacebookImport\GraphApi;

use Mvo\ContaoFacebookImport\Entity\FacebookNode;

class RequestQuotaExceededException extends \Exception
{
    /** @var FacebookNode */
    private $facebookNode;

    public function __construct(FacebookNode $node)
    {
        $this->facebookNode = $node;

        parent::__construct(sprintf('Quota exceeded for Facebook Node ID%s.', $node->getId()));
    }

    public function getFacebookNode(): FacebookNode
    {
        return $this->facebookNode;
    }
}
