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
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Facebook\FacebookRequest;
use Facebook\FacebookResponse;
use Facebook\GraphNodes\GraphEdge;
use Psr\Log\LoggerInterface;

class OpenGraphParser
{
    /** @var string */
    private $pageName;

    /** @var Facebook */
    private $facebook;

    /** @var AccessToken */
    private $accessToken;

    /** @var LoggerInterface */
    private $logger;

    /**
     * OpenGraph constructor.
     *
     * @param string          $appId
     * @param string          $appSecret
     * @param string          $accessToken
     * @param string          $pageName
     *
     * @param LoggerInterface $logger
     *
     * @throws FacebookSDKException
     */
    public function __construct(
        string $appId,
        string $appSecret,
        string $accessToken,
        string $pageName,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;

        $this->pageName    = $pageName;
        $this->facebook    = new Facebook(
            [
                'app_id'                => $appId,
                'app_secret'            => $appSecret,
                'default_graph_version' => 'v2.10',
            ]
        );
        $this->accessToken = new AccessToken($accessToken);
    }

    /**
     * @param string $objectId
     * @param array  $fieldNames
     * @param array  $params
     *
     * @return array|null
     */
    public function queryObject(string $objectId, array $fieldNames, array $params = []): ?array
    {
        try {
            $response = $this->performRequest($objectId, $fieldNames, $params, true);
            return (null !== $response) ? $response->getDecodedBody() : null;

        } catch (FacebookSDKException $e) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->logger->warning(
                sprintf('Facebook SDK: An error occurred querying object %s.', $objectId),
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
     * @return \Facebook\GraphNodes\GraphEdge|null
     */
    public function queryEdge(string $entity, array $fieldNames, array $params = []): ?GraphEdge
    {
        try {
            return $this
                ->performRequest($entity, $fieldNames, $params)
                ->getGraphEdge();

        } catch (FacebookSDKException $e) {
            $this->logger->warning(
                sprintf('Facebook SDK: An error occurred querying edge of entity %s.', $entity),
                ['exception' => $e, 'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );

            return null;
        }
    }

    /**
     * @param string $entity
     * @param array  $fieldNames
     * @param array  $params
     * @param bool   $noPagedQuery
     *
     * @return \Facebook\FacebookResponse
     * @throws \Facebook\Exceptions\FacebookSDKException
     */
    private function performRequest(
        string $entity,
        array $fieldNames,
        array $params = [],
        bool $noPagedQuery = false
    ): FacebookResponse {

        // perform request
        $query = $noPagedQuery
            ? sprintf('%s?fields=%s', $entity, implode(',', $fieldNames))
            : sprintf('%s/%s?fields=%s', $this->pageName, $entity, implode(',', $fieldNames));

        $request = new FacebookRequest(
            $this->facebook->getApp(),
            $this->accessToken->getValue(),
            'GET',
            $query,
            $params
        );

        return $this->facebook->getClient()->sendRequest($request);
    }
}