<?php

declare(strict_types=1);

/*
 * Contao Facebook Import Bundle for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2017-2018, Moritz Vondano
 * @license    MIT
 * @link       https://github.com/m-vo/contao-facebook-import
 *
 * @author     Moritz Vondano
 */

namespace Mvo\ContaoFacebookImport\Facebook;

use Contao\CoreBundle\Monolog\ContaoContext;
use Facebook\Exceptions\FacebookSDKException;
use Mvo\ContaoFacebookImport\Model\FacebookModel;
use Psr\Log\LoggerInterface;

class OpenGraphParserFactory
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * OpenGraphParserFactory constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param FacebookModel $node
     *
     * @return OpenGraphParser|null
     */
    public function getParser(FacebookModel $node): ?OpenGraphParser
    {
        try {
            return new OpenGraphParser(
                $node->fbAppId,
                $node->fbAppSecret,
                $node->fbAccessToken,
                $node->fbPageName,
                $this->logger
            );
        } catch (FacebookSDKException $e) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->logger->warning(
                sprintf('Facebook SDK: An error occurred trying to query app id %s.', $node->fbAppId),
                ['exception' => $e, 'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
            return null;
        }
    }
}