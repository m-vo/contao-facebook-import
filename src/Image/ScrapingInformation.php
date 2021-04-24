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

namespace Mvo\ContaoFacebookImport\Image;

use Contao\StringUtil;
use Facebook\GraphNodes\GraphCoverPhoto;
use Facebook\GraphNodes\GraphNode;

class ScrapingInformation
{
    public const TYPE_URL = 0;
    public const TYPE_IMAGE_SET = 1;
    public const TYPE_RESCRAPE_AS_EVENT = 2;

    /**
     * @var string
     */
    public $identifier;

    /**
     * @var string|null
     */
    public $fallbackUrl;

    /**
     * @var int
     */
    public $type;

    /**
     * @var string|null
     */
    public $objectId;

    /**
     * @var array
     */
    public $metaData;

    /**
     * ScrapingInformation constructor.
     */
    public function __construct(string $identifier, ?string $fallbackUrl, int $type, ?string $objectId, array $metaData)
    {
        $this->identifier = $identifier;
        $this->fallbackUrl = $fallbackUrl;
        $this->type = $type;
        $this->objectId = $objectId;
        $this->metaData = $metaData;
    }

    /**
     * @return ScrapingInformation|null
     */
    public static function fromPostNode(GraphNode $graphNode): ?self
    {
        // only accept nodes with picture information
        $url = $graphNode->getField('full_picture', null);

        if (null === $url) {
            return null;
        }

        // set attributes by type
        $objectId = null;
        $type = self::TYPE_URL;

        $attachments = $graphNode->getField('attachments', [])[0] ?? [];

        switch ($attachments['media_type'] ?? '') {
            case 'photo':
            case 'album':
                $objectId = $attachments['target']['id'] ?? null;
                $type = self::TYPE_IMAGE_SET;
                break;

            case 'event':
                $objectId = $attachments['target']['id'] ?? null;
                $type = self::TYPE_RESCRAPE_AS_EVENT;
                break;

            case 'link':
                break;

            default:
                return null;
        }

        // unique identifier
        $identifier = $graphNode->getField('object_id', null) ?? $graphNode->getField('id', null);

        if (null === $identifier) {
            return null;
        }

        // meta information
        $metaData = [
            'caption' => $graphNode->getField('caption', ''),
            'link' => $graphNode->getField('link', null) ??
                         sprintf('https://facebook.com/%s', $graphNode->getField('id', '')),
        ];

        return new self(
            $identifier,
            $url,
            $type,
            $objectId,
            $metaData
        );
    }

    /**
     * @return ScrapingInformation|null
     */
    public static function fromEventNode(GraphNode $graphNode): ?self
    {
        // only accept nodes with cover information
        /** @var GraphCoverPhoto|null $cover */
        $cover = $graphNode->getField('cover', null);

        if (null === $cover) {
            return null;
        }

        // get image set id
        $objectId = $cover->getField('id', null);

        if (null === $objectId) {
            return null;
        }

        // meta information
        $metaData = [
            'caption' => $graphNode->getField('name', ''),
            'link' => sprintf('https://facebook.com/%s', $graphNode->getField('id', '')),
        ];

        return new self(
            $objectId,
            $cover->getField('source', null),
            self::TYPE_IMAGE_SET,
            $objectId,
            $metaData
        );
    }

    /**
     * @param ScrapingInformation $item
     *
     * @return string
     */
    public static function serialize(?self $item): ?string
    {
        if (null === $item) {
            return null;
        }

        return serialize(
            [
                'identifier' => $item->identifier,
                'fallbackUrl' => $item->fallbackUrl,
                'type' => $item->type,
                'objectId' => $item->objectId,
                'metaData' => $item->metaData,
            ]
        );
    }

    /**
     * @return ScrapingInformation
     */
    public static function deserialize(string $string): ?self
    {
        $data = StringUtil::deserialize($string);

        if (null === $data) {
            return null;
        }

        return new self(
            $data['identifier'],
            $data['fallbackUrl'],
            $data['type'],
            $data['objectId'],
            $data['metaData']
        );
    }
}
