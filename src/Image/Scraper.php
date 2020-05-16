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

use Contao\Config;
use Contao\CoreBundle\Image\ImageFactory;
use Contao\Dbafs;
use Contao\FilesModel;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Mvo\ContaoFacebookImport\GraphApi\GraphApiReader;
use Mvo\ContaoFacebookImport\GraphApi\RequestQuotaExceededException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Scraper implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /** @var ImageFactory */
    private $imageFactory;

    /**
     * Scraper constructor.
     */
    public function __construct(ImageFactory $imageFactory)
    {
        $this->imageFactory = $imageFactory;
    }

    /**
     * @param GuzzleException|\Exception|string $error
     *
     * @throws RequestQuotaExceededException
     */
    public function scrape(
        ScrapingInformation $info,
        string $destinationDirectory,
        GraphApiReader $reader,
        &$error = null
    ): ?FilesModel {
        // get source uri
        $sourceUri = $this->getSourceUri($info, $reader);

        if (null === $sourceUri) {
            $error = new \InvalidArgumentException('Insufficient scraping information provided.');

            return null;
        }

        // issue file transfer
        $destinationPath = sprintf('%s/%s.jpg', $destinationDirectory, $info->identifier);

        $absoluteDestinationPath = sprintf(
            '%s/%s',
            $this->container->getParameter('kernel.project_dir'),
            $destinationPath
        );
        if (!$this->downloadFile($sourceUri, $absoluteDestinationPath, $error)) {
            return null;
        }

        // make sure facebook didn't deliver a single pixel image (in any dimension)
        try {
            $imageSize = $this->imageFactory->create($absoluteDestinationPath)->getDimensions()->getSize();
            if (1 === $imageSize->getHeight() || 1 === $imageSize->getWidth()) {
                $this->deleteFileIfExisting($absoluteDestinationPath);

                $error = 'Ignoring single pixel image.';

                return null;
            }
        } catch (\Exception $e) {
            $error = $e;

            return null;
        }

        // add to dbafs
        try {
            $file = Dbafs::addResource($destinationPath);
        } catch (\Exception $e) {
            $this->deleteFileIfExisting($absoluteDestinationPath);

            $error = $e;

            return null;
        }

        // add meta data
        $file->meta = serialize(['caption' => $info->metaData]);
        $file->save();

        return $file;
    }

    /**
     * @throws RequestQuotaExceededException
     */
    private function getSourceUri(ScrapingInformation $info, GraphApiReader $reader): ?string
    {
        if (ScrapingInformation::TYPE_URL === $info->type) {
            return $info->fallbackUrl;
        }

        if (ScrapingInformation::TYPE_RESCRAPE_AS_EVENT === $info->type) {
            // we need to query the graph again to get the image set information
            $node = $reader->getSingleNode($info->objectId, ['id', 'name', 'cover']);
            if (null === $node) {
                return $info->fallbackUrl;
            }

            return $this->getSourceUri(ScrapingInformation::fromEventNode($node), $reader);
        }

        if (ScrapingInformation::TYPE_IMAGE_SET === $info->type) {
            if ($info->objectId === $info->identifier) {
                $object = $reader->getSingleNode($info->identifier, ['images']);

                if (null === $object) {
                    return $info->fallbackUrl;
                }

                $imageSet = $object->getField('images', null);
            } else {
                $object = $reader->getSingleNode($info->identifier, ['attachments{subattachments}']);
                if (null === $object) {
                    return $info->fallbackUrl;
                }

                $attachments = $object->getField('attachments', null);
                if (null === $attachments) {
                    return $info->fallbackUrl;
                }

                $imageSet = $attachments->asArray()[0]['subattachments'][0]['media'] ?? null;
            }

            if (null === $imageSet) {
                return $info->fallbackUrl;
            }

            return $this->getBiggestPossibleImageSource($imageSet) ?? $info->fallbackUrl;
        }

        throw new \InvalidArgumentException('Illegal scraping information provided.');
    }

    private function getBiggestPossibleImageSource(iterable $imageSet): ?string
    {
        $widthLimit = Config::get('gdMaxImgWidth');
        $heightLimit = Config::get('gdMaxImgHeight');

        $maxHeight = 0;
        $source = null;

        foreach ($imageSet as $item) {
            $height = $item['height'] ?? 0;
            $width = $item['width'] ?? 0;

            if ($height > $maxHeight && $height <= $heightLimit && $width <= $widthLimit && isset($item['source'])) {
                $maxHeight = $height;
                $source = $item['source'];
            }
        }

        return $source;
    }

    /**
     * Download a file.
     *
     * @param GuzzleException| null $error
     */
    private function downloadFile(string $sourceUri, string $destinationPath, GuzzleException &$error = null): bool
    {
        $client = new Client(
            [
                'defaults' => [
                    'headers' => ['User-Agent' => 'Contao Web Scraper'],
                ],
            ]
        );

        $this->deleteFileIfExisting($destinationPath);

        try {
            // synchronous download
            $client->send(
                new Request('get', $sourceUri),
                [
                    'sink' => $destinationPath,
                ]
            );
        } catch (GuzzleException $e) {
            $this->deleteFileIfExisting($destinationPath);

            $error = $e;

            return false;
        }

        return true;
    }

    /**
     * Make sure a file does not exist at the given path.
     */
    private function deleteFileIfExisting(string $fullPath): void
    {
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
