<?php

namespace Mvo\ContaoFacebookImport\Facebook;

use Contao\Config;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\Dbafs;
use Contao\FilesModel;
use Facebook\GraphNodes\GraphNode;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Log\LogLevel;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ImageScraper implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private $uploadPath;
    private $openGraph;

    /**
     * ImageScraper constructor.
     *
     * @param string    $uploadPath relative to the root
     * @param OpenGraph $openGraph
     */
    public function __construct($uploadPath, OpenGraph $openGraph)
    {
        $this->uploadPath = $uploadPath;
        $this->openGraph  = $openGraph;
    }

    /**
     * @param string $objectId
     * @param string $type
     * @param string $serializedMetaData
     *
     * @return FilesModel|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function scrapeObject(string $objectId, string $type, $serializedMetaData = '')
    {
        $uploadPath = $this->getUploadPath(sprintf('%s.jpg', $objectId));

        // update metadata and return if file already exists
        if (null != $fileModel = FilesModel::findByPath($uploadPath)) {
            if ($serializedMetaData != $fileModel->meta) {
                self::updateMetaData($fileModel, $objectId, $serializedMetaData);
            }

            return $fileModel;
        }

        // try to find best fitting image uri (by scanning the graph)
        $sourceUri = self::getSourceUri($objectId, $type);
        if (null == $sourceUri) {
            return null;
        }

        // scrape it!
        return self::scrape($sourceUri, $uploadPath, $objectId, $serializedMetaData);
    }

    /**
     * @param string $identifier
     * @param string $sourceUri
     * @param string $serializedMetaData
     *
     * @return FilesModel|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function scrapeFile(string $identifier, string $sourceUri, $serializedMetaData = '')
    {
        $uploadPath = $this->getUploadPath(sprintf('%s.jpg', $identifier));

        // update metadata and return if file already exists
        if (null != $fileModel = FilesModel::findByPath($this->getUploadPath($uploadPath))) {
            if ($serializedMetaData != $fileModel->meta) {
                self::updateMetaData($fileModel, $identifier, $serializedMetaData);
            }

            return $fileModel;
        }

        return self::scrape($sourceUri, $uploadPath, $identifier, $serializedMetaData);
    }

    /**
     * @param $sourceUri
     * @param $uploadPath
     * @param $identifier
     * @param $serializedMetaData
     *
     * @return FilesModel|null
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function scrape($sourceUri, $uploadPath, $identifier, $serializedMetaData)
    {
        // download file
        if (!self::downloadFile($sourceUri, $uploadPath)) {
            return null;
        }

        // update db filesystem & add meta data
        $fileModel = Dbafs::addResource($uploadPath);
        if (null == $fileModel) {
            return null;
        }

        self::updateMetaData($fileModel, $identifier, $serializedMetaData);
        return $fileModel;
    }

    /**
     * @param string $objectId
     * @param string $type
     *
     * @return string|null
     */
    private function getSourceUri(string $objectId, string $type)
    {
        // only 'photo' and 'event' supported
        if ('photo' !== $type && 'event' !== $type) {
            return null;
        }

        if ('event' === $type) {
            $cover = $this->openGraph->queryObject($objectId, ['cover']);
            if (null != $cover && is_array($cover) && array_key_exists('cover', $cover)
                && is_array($cover['cover'])
                && array_key_exists('id', $cover['cover'])
            ) {
                $objectId = $cover['cover']['id'];
            } else {
                return null;
            }
        }

        // get available images
        $arrData = $this->openGraph->queryObject($objectId, ['images']);
        if (null == $arrData || !is_array($arrData) || !array_key_exists('images', $arrData)) {
            return null;
        }

        // get source uri of biggest image
        $sourceUri = self::getBiggestImageSource($arrData['images']);
        if ('' == $sourceUri) {
            return null;
        }

        return $sourceUri;
    }

    /**
     * @param array $data
     *
     * @return string
     */
    private function getBiggestImageSource(array $data)
    {
        $widthLimit  = Config::get('gdMaxImgWidth');
        $heightLimit = Config::get('gdMaxImgHeight');

        $maxHeight = 0;
        $source    = '';

        /** @var GraphNode $graphNode */
        foreach ($data as $item) {
            $height = array_key_exists('height', $item) ? $item['height'] : 0;
            $width  = array_key_exists('width', $item) ? $item['width'] : 0;

            if ($height > $maxHeight && $height <= $heightLimit && $width <= $widthLimit) {
                $maxHeight = $height;
                $source    = array_key_exists('source', $item) ? $item['source'] : '';
            }
        }

        return $source;
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    private function getUploadPath(string $fileName)
    {
        //$projectDir = $this->container->getParameter('kernel.project_dir');

        return $this->uploadPath . '/' . $fileName;
    }

    /**
     * @param $uriFrom
     * @param $pathTo
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function downloadFile($uriFrom, $pathTo)
    {
        $client = new Client();

        try {
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
        } catch (Exception $e) {
            self::logError($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @param FilesModel $fileModel
     * @param string     $name
     * @param string     $metaDescription
     */
    private function updateMetaData(FilesModel $fileModel, string $name, string $metaDescription)
    {
        $fileModel->name = $name;
        $fileModel->meta = $metaDescription;
        $fileModel->save();
    }

    /**
     * @param $str
     */
    private function logError($str)
    {
        $logger = $this->container->get('monolog.logger.contao');

        $logger->log(
            LogLevel::ERROR,
            $str,
            array('contao' => new ContaoContext(debug_backtrace()[1]['function'], TL_ERROR))
        );
    }
}