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

namespace Mvo\ContaoFacebookImport\GraphApi;

use GuzzleHttp\Client;

class AccessTokenGenerator
{
    /**
     * @param string $appId
     * @param string $appSecret
     * @param string $userToken
     *
     * @return string|null
     */
    public function generateNeverExpiringAccessToken(string $appId, string $appSecret, string $userToken): ?string
    {
        try {
            # see https://stackoverflow.com/questions/17197970/facebook-permanent-page-access-token/43605020#43605020

            // get long-lived token
            $longLivedToken = $this->getProperty(
                'https://graph.facebook.com/v2.9/oauth/access_token?grant_type=fb_exchange_token&' .
                "client_id={$appId}&client_secret={$appSecret}&fb_exchange_token={$userToken}",
                'access_token'
            );

            // get user id
            $userId = $this->getProperty(
                "https://graph.facebook.com/v2.9/me?access_token={$longLivedToken}",
                'id'
            );

            // get final token
            return $this->getProperty(
                "https://graph.facebook.com/v2.9/{$userId}?fields=access_token&access_token={$longLivedToken}",
                'access_token'
            );

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $url
     * @param $property
     *
     * @return string
     * @throws \RuntimeException
     */
    private function getProperty($url, $property): string
    {
        $client   = new Client();
        $response = $client->get($url);

        if (200 === $response->getStatusCode()
            && null !== ($body = $response->getBody())
            && ($contents = $body->getContents())
            && ($properties = json_decode($contents))
            && $properties->$property
        ) {
            return $properties->$property;
        }

        throw new \RuntimeException('could not retrieve property');
    }

}