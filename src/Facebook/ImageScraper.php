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

use Contao\Config;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Dbafs;
use Contao\FilesModel;
use Facebook\GraphNodes\GraphNode;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LoggerInterface;

class ImageScraper
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * ImageScraper constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param OpenGraphParser $parser
     * @param string          $objectId
     * @param string          $uploadPath
     *
     * @return FilesModel|null
     */
    public function scrapePhoto(OpenGraphParser $parser, string $objectId, string $uploadPath): ?FilesModel
    {
        return $this->scrapeObject($parser, 'photo', $objectId, $uploadPath);
    }

    /**
     * @param OpenGraphParser $parser
     * @param string          $objectId
     * @param string          $uploadPath
     *
     * @return FilesModel|null
     */
    public function scrapeEvent(OpenGraphParser $parser, string $objectId, string $uploadPath): ?FilesModel
    {
        return $this->scrapeObject($parser, 'event', $objectId, $uploadPath);
    }

    /**
     * @param OpenGraphParser $parser
     * @param string          $type
     * @param string          $objectId
     * @param string          $uploadPath
     *
     * @return FilesModel|null
     */
    public function scrapeObject(
        OpenGraphParser $parser,
        string $type,
        string $objectId,
        string $uploadPath
    ): ?FilesModel {
        $uploadFilePath = $this->getUploadFilePath($uploadPath, $objectId);

        // return if file already exists
        if (null !== $fileModel = FilesModel::findByPath($uploadFilePath)) {
            return $fileModel;
        }

        // try to find best fitting image uri (by scanning the graph)
        $sourceUri = $this->getSourceUri($parser, $objectId, $type);
        if (null === $sourceUri) {
            return null;
        }

        // scrape it!
        return $this->scrape($sourceUri, $uploadFilePath);
    }

    /**
     * @param string $identifier
     * @param string $sourceUri
     * @param string $uploadPath
     *
     * @return FilesModel|null
     */
    public function scrapeFile(string $identifier, string $sourceUri, string $uploadPath): ?FilesModel
    {
        // return if file already exists
        if (null !== $fileModel = FilesModel::findByPath($this->getUploadFilePath($uploadPath, $identifier))) {
            return $fileModel;
        }

        return $this->scrape($sourceUri, $uploadPath);
    }

    /**
     * @param string $uploadPath
     * @param string $identifier
     *
     * @return string
     */
    private function getUploadFilePath(string $uploadPath, string $identifier): string
    {
        return sprintf('%s/%s.jpg', $uploadPath, $identifier);
    }

    /**
     * @param OpenGraphParser $parser
     * @param string          $objectId
     * @param string          $type
     *
     * @return string|null
     */
    private function getSourceUri(OpenGraphParser $parser, string $objectId, string $type): ?string
    {
        // only 'photo' and 'event' supported
        if ('photo' !== $type && 'event' !== $type) {
            return null;
        }

        if ('event' === $type) {
            $cover = $parser->queryObject($objectId, ['cover']);
            if (null !== $cover && \is_array($cover) && \array_key_exists('cover', $cover)
                && \is_array($cover['cover'])
                && \array_key_exists('id', $cover['cover'])
            ) {
                $objectId = $cover['cover']['id'];
            } else {
                return null;
            }
        }

        // get available images
        $arrData = $parser->queryObject($objectId, ['images']);
        if (null === $arrData || !\is_array($arrData) || !\array_key_exists('images', $arrData)) {
            return null;
        }

        // get source uri of biggest image
        return $this->getBiggestImageSource($arrData['images']);
    }

    /**
     * @param GraphNode[] $data
     *
     * @return string|null
     */
    private function getBiggestImageSource(array $data): ?string
    {
        $widthLimit  = Config::get('gdMaxImgWidth');
        $heightLimit = Config::get('gdMaxImgHeight');

        $maxHeight = 0;
        $source    = null;

        /** @var GraphNode $graphNode */
        foreach ($data as $item) {
            $height = \array_key_exists('height', $item) ? $item['height'] : 0;
            $width  = \array_key_exists('width', $item) ? $item['width'] : 0;

            if ($height > $maxHeight && $height <= $heightLimit && $width <= $widthLimit
                && \array_key_exists('source', $item)) {
                $maxHeight = $height;
                $source    = $item['source'];
            }
        }

        return $source;
    }

    /**
     * @param string $sourceUri
     * @param string $uploadPath
     *
     * @return FilesModel|null
     */
    private function scrape(string $sourceUri, string $uploadPath): ?FilesModel
    {
        try {
            // download file
            $this->downloadFile($sourceUri, $uploadPath);

            // update db filesystem
            return Dbafs::addResource($uploadPath);

        } catch (GuzzleException|\Exception $e) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $this->logger->warning(
                sprintf('Image Scraper: An error occurred trying to integrate the following URI:%s.', $sourceUri),
                ['exception' => $e, 'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
            );
            return null;
        }
    }

    /**
     * @param string $uriFrom
     * @param string $pathTo
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function downloadFile(string $uriFrom, string $pathTo): void
    {
        $client = new Client();

        // remove file if already existing
        if (file_exists($pathTo)) {
            unlink($pathTo);
        }

        // synchronous download
        $client->send(
            new Request('get', $uriFrom),
            [
                'sink' => $pathTo
            ]
        );
    }
}