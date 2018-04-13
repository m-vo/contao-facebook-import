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

namespace Mvo\ContaoFacebookImport\EventListener;

use Contao\Dbafs;
use Contao\Files;
use Contao\FilesModel;
use Contao\Model\Collection;
use Facebook\GraphNodes\GraphNode;
use Mvo\ContaoFacebookImport\Facebook\ImageScraper;
use Mvo\ContaoFacebookImport\Facebook\Tools;
use Mvo\ContaoFacebookImport\Model\FacebookModel;
use Mvo\ContaoFacebookImport\Model\FacebookPostModel;

class ImportFacebookPostsListener extends ImportFacebookDataListener
{
    /**
     * @param integer $pid
     *
     * @return integer
     */
    protected function getLastTimeStamp(int $pid): int
    {
        return FacebookPostModel::getLastTimestamp($pid);
    }

    /**
     * Entry point: Import/update facebook events.
     *
     * @param FacebookModel $node
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function import(FacebookModel $node) : void
    {
        // find existing posts
        $objPosts       = FacebookPostModel::findByPid($node->id);
        $postDictionary = [];
        if (null !== $objPosts) {
            foreach ($objPosts as $objPost) {
                /** @var FacebookPostModel $objPost */
                $postDictionary[$objPost->postId] = $objPost;
            }
        }

        // query facebook for current posts
        $graphEdge = $node->getOpenGraphInstance()->queryEdge(
            'posts',
            [
                'id',
                'created_time',
                'type',
                'caption',
                'link',
                'message',
                'picture',
                'full_picture',
                'object_id',
                'updated_time'
            ],
            ['limit' => $node->numberOfPosts]
        );
        if (null === $graphEdge) {
            return;
        }

        // merge the data
        $uploadDirectory = FilesModel::findById($node->uploadDirectory);
        if(!$uploadDirectory || !$uploadDirectory->path) {
            throw new \InvalidArgumentException('No or invalid upload path.');
        }
        $imageScraper    =  new ImageScraper($uploadDirectory->path, $node->getOpenGraphInstance());

        /** @var GraphNode $graphNode */
        foreach ($graphEdge as $graphNode) {
            $fbId = $graphNode->getField('id', null);
            if ($fbId === null) {
                continue;
            }

            // skip if message is empty or type is 'link' and the message only contains an URL
            $message = $graphNode->getField('message', '');
            if ('' === $message
                || ('link' === $graphNode->getField('type', '')
                    && 1 === preg_match('~^\s*https://\S*\s*?$~', $message))
            ) {
                continue;
            }

            if (array_key_exists($fbId, $postDictionary)) {
                // update existing item
                if ($this->updateRequired($graphNode, $postDictionary[$fbId])) {
                    $this->updatePost($postDictionary[$fbId], $graphNode, $imageScraper);
                }
                unset($postDictionary[$fbId]);

            } else {
                // create new item
                $post = new FacebookPostModel();

                $post->pid    = $node->id;
                $post->postId = $fbId;
                $this->updatePost($post, $graphNode, $imageScraper);
            }
        }

        // remove orphans
        /** @var FacebookPostModel $post */
        foreach ($postDictionary as $post) {
            // todo: generalize with dca's ondelete_callback
            if ($post->image && $file = FilesModel::findByUuid($post->image)) {
                /** @var Collection $objPosts */
                $objPosts = FacebookPostModel::findBy('image', $post->image);
                if (1 === $objPosts->count()) {
                    Files::getInstance()->delete($file->path);
                    Dbafs::deleteResource($file->path);
                }
            }
            $post->delete();
        }
    }

    /**
     * @param GraphNode         $graphNode
     * @param FacebookPostModel $post
     *
     * @return bool
     */
    private function updateRequired(GraphNode $graphNode, FacebookPostModel $post): bool
    {
        return $this->getTime($graphNode, 'updated_time') !== $post->lastChanged;
    }

    /**
     * @param FacebookPostModel $event
     * @param GraphNode         $graphNode
     * @param ImageScraper      $imageScraper
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function updatePost(FacebookPostModel $event, GraphNode $graphNode, ImageScraper $imageScraper): void
    {
        $event->tstamp      = time();
        $event->postTime    = $this->getTime($graphNode, 'created_time');
        $event->message     = Tools::encodeText($graphNode->getField('message', ''));
        $event->image       = $this->getImage($graphNode, $imageScraper);
        $event->lastChanged = $this->getTime($graphNode, 'updated_time');

        $event->save();
    }

    /**
     * @param GraphNode $graphNode
     * @param string    $field
     *
     * @return int
     */
    private function getTime(GraphNode $graphNode, string $field): int
    {
        /** @var \DateTime $date */
        $date = $graphNode->getField($field, null);
        return ($date !== null) ? $date->getTimestamp() : 0;
    }

    /**
     * @param GraphNode    $graphNode
     * @param ImageScraper $imageScraper
     *
     * @return null|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getImage(GraphNode $graphNode, ImageScraper $imageScraper): ?string
    {
        if (null !== $graphNode->getField('picture', null)) {
            $metaData  = [
                'caption' =>
                    [
                        'caption' => $graphNode->getField('caption', ''),
                        'link'    => $graphNode->getField('link', ''),
                    ]
            ];
            $fileModel = null;

            if (null !== $objectId = $graphNode->getField('object_id', null)) {
                $fileModel = $imageScraper->scrapeObject(
                    $objectId,
                    $graphNode->getField('type', ''),
                    serialize($metaData)
                );
            } elseif (null !== $pictureUri = $graphNode->getField('full_picture', null)) {
                $fileModel = $imageScraper->scrapeFile(
                    'p_' . $graphNode->getField('id'),
                    $pictureUri,
                    serialize($metaData)
                );
            }
            return (null !== $fileModel) ? $fileModel->uuid : null;
        }
        return null;
    }
}