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

use Contao\CoreBundle\Monolog\ContaoContext;
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\GraphNodes\GraphNode;
use Psr\Log\LoggerInterface;

class GraphApiReader
{
    /** @var string */
    public const GRAPH_API_VERSION = 'v7.0';

    /** @var string */
    private $pageName;

    /** @var Facebook */
    private $facebook;

    /** @var AccessToken */
    private $accessToken;

    /** @var LoggerInterface */
    private $logger;

    /** @var callable */
    private $trackRequestQuotaCallback;

    /**
     * GraphApiReader constructor.
     *
     * @throws FacebookSDKException
     */
    public function __construct(
        string $appId,
        string $appSecret,
        string $accessToken,
        string $pageName,
        LoggerInterface $logger,
        callable $trackRequestQuotaCallback
    ) {
        $this->pageName = $pageName;
        $this->facebook = new Facebook(
            [
                'app_id' => $appId,
                'app_secret' => $appSecret,
            ]
        );
        $this->accessToken = new AccessToken($accessToken);

        $this->logger = $logger;
        $this->trackRequestQuotaCallback = $trackRequestQuotaCallback;
    }

    /**
     * @throws RequestQuotaExceededException
     */
    public function getSingleNode(string $objectId, array $fieldNames, array $params = []): ?GraphNode
    {
        try {
            // query graph
            return $this->performRequest($objectId, $fieldNames, $params)->getGraphNode();
        } catch (FacebookSDKException $e) {
            $this->logger->warning(
                sprintf('Facebook SDK: An error occurred querying edge of object %s.', $objectId),
                ['exception' => $e, 'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );

            return null;
        }
    }

    /**
     * @param string $entity     the entity to query against (post, events, ...)
     * @param array  $fieldNames field names to query
     * @param array  $params     add custom params if needed
     *
     * @throws RequestQuotaExceededException
     *
     * @return \Facebook\GraphNodes\GraphNode[]|null
     */
    public function getPageNodes(string $entity, array $fieldNames, array $params = []): ?array
    {
        $requiredElements = $params['limit'] ?? 0;
        $limitThreshold = 100;

        // if requested limit exceeds a threshold, cut it to prevent facebook throwing an
        // OAuthException ("The 'limit' parameter should not exceed _value")
        if (isset($params['limit']) && $params['limit'] > $limitThreshold) {
            $params['limit'] = $limitThreshold;
        }

        $nodes = [];
        $endpoint = sprintf('%s/%s', $this->pageName, $entity);

        // execute paginated requests
        try {
            do {
                // query graph and add nodes
                $edge = $this->performRequest($endpoint, $fieldNames, $params)->getGraphEdge();
                foreach ($edge as $node) {
                    $nodes[] = $node;
                }

                // try to find more nodes by following pagination
                $numElements = \count($nodes);
                $params['after'] = $edge->getNextCursor();
                if (0 !== $requiredElements) {
                    $params['limit'] = min($requiredElements - $numElements, $limitThreshold);
                }
            } while (0 !== $requiredElements
                     && $numElements < $requiredElements
                     && null !== $params['after']);

            return $nodes;
        } catch (FacebookSDKException $e) {
            $this->logger->warning(
                sprintf(
                    'Facebook SDK: An error occurred querying edge of entity %s: %s [Code %s]',
                    $entity,
                    $e->getMessage(),
                    $e->getCode()
                ),
                ['exception' => $e, 'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );

            return null;
        }
    }

    /** @noinspection PhpDocRedundantThrowsInspection because callback throws */

    /**
     * @throws \Facebook\Exceptions\FacebookSDKException
     * @throws RequestQuotaExceededException
     */
    private function performRequest(
        string $endpoint,
        array $fieldNames,
        array $params = []
    ): FacebookResponse {
        // check request quota
        if (null !== $this->trackRequestQuotaCallback) {
            ($this->trackRequestQuotaCallback)();
        }

        // perform request
        $query = sprintf('%s?fields=%s', $endpoint, implode(',', $fieldNames));

        $request = new FacebookRequest(
            $this->facebook->getApp(),
            $this->accessToken->getValue(),
            'GET',
            $query,
            $params,
            null,
            self::GRAPH_API_VERSION
        );

        return $this->facebook->getClient()->sendRequest($request);
    }
}
