<?php

namespace Mvo\ContaoFacebookImport\Facebook;

use Contao\CoreBundle\Monolog\ContaoContext;
use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;
use Facebook\Facebook;
use Facebook\FacebookRequest;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class OpenGraph implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $appId;
    private $appSecret;
    private $accessToken;
    private $pageName;

    /**
     * OpenGraph constructor.
     *
     * @param string $appId
     * @param string $appSecret
     * @param string $accessToken
     * @param string $pageName
     */
    public function __construct($appId, $appSecret, $accessToken, $pageName)
    {
        if (null == $appId || null == $appSecret || null == $accessToken || null == $pageName) {
            throw new \Exception('Invalid configuation');
        }

        $this->appId       = $appId;
        $this->appSecret   = $appSecret;
        $this->accessToken = $accessToken;
        $this->pageName    = $pageName;
    }

    /**
     * @param int   $objectId
     * @param array $fieldNames
     * @param array $params
     */
    public function queryObject(string $objectId, array $fieldNames, array $params = [])
    {
        $response = $this->performRequest($objectId, $fieldNames, $params, true);
        return (null !== $response) ? $response->getDecodedBody() : null;
    }

    /**
     * @param string $entity     the entity to query against (post, events, ...)
     * @param array  $fieldNames field names to query
     * @param array  $params     add custom params if needed
     *
     * @return \Facebook\GraphNodes\GraphEdge
     */
    public function queryEdge(string $entity, array $fieldNames, array $params = [])
    {
        $response = $this->performRequest($entity, $fieldNames, $params);

        try {
            $edge = $response->getGraphEdge();

        } catch (FacebookSDKException $e) {
            // validation failed or other local issues
            self::logError('Facebook SDK returned an error: ' . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            self::logError('Unknown error: ' . $e->getMessage());
            return null;
        }

        return $edge;
    }


    /**
     * @param string $entity
     * @param array  $fieldNames
     * @param array  $params
     * @param bool   $noPagedQuery
     *
     * @return \Facebook\FacebookResponse|null
     */
    private function performRequest(string $entity, array $fieldNames, array $params = [], $noPagedQuery = false)
    {
        $fb          = new Facebook(
            [
                'app_id'                => $this->appId,
                'app_secret'            => $this->appSecret,
                'default_graph_version' => 'v2.9',
            ]
        );
        $accessToken = new AccessToken($this->accessToken);

        // perform request
        $query = ($noPagedQuery == false)
            ?
            sprintf('%s/%s?fields=%s', $this->pageName, $entity, implode(',', $fieldNames))
            :
            sprintf('%s?fields=%s', $entity, implode(',', $fieldNames));

        $request = new FacebookRequest(
            $fb->getApp(),
            $accessToken->getValue(),
            'GET',
            $query,
            $params
        );

        try {
            $response = $fb->getClient()->sendRequest($request);
            // todo: follow paginated entries

        } catch (FacebookResponseException $e) {
            // graph returned an error
            self::logError('Graph returned an error: ' . $e->getMessage());
            return null;

        } catch (FacebookSDKException $e) {
            // validation failed or other local issues
            self::logError('Facebook SDK returned an error: ' . $e->getMessage());
            return null;

        } catch (\Exception $e) {
            self::logError('Unknown error: ' . $e->getMessage());
            return null;
        }

        return $response;
    }

    private function logError($str)
    {
        $logger = $this->container->get('monolog.logger.contao');

        $logger->log(
            LogLevel::ERROR,
            $str,
            ['contao' => new ContaoContext(debug_backtrace()[1]['function'], TL_ERROR)]
        );
    }

}