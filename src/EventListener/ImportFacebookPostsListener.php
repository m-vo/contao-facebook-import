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
use Mvo\ContaoFacebookImport\Facebook\OpenGraphParser;
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
     * @param FacebookModel   $node
     * @param OpenGraphParser $parser
     *
     * @throws \InvalidArgumentException
     */
    protected function import(FacebookModel $node, OpenGraphParser $parser): void
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
        $graphEdge = $parser->queryEdge(
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
        if (!$uploadDirectory || !$uploadDirectory->path) {
            throw new \InvalidArgumentException('No or invalid upload path.');
        }

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

            if (\array_key_exists($fbId, $postDictionary)) {
                // update existing item
                if ($this->updateRequired($graphNode, $postDictionary[$fbId])) {
                    $this->updatePost($parser, $postDictionary[$fbId], $graphNode, $uploadDirectory->path);
                }
                unset($postDictionary[$fbId]);

            } else {
                // create new item
                $post = new FacebookPostModel();

                $post->pid    = $node->id;
                $post->postId = $fbId;
                $this->updatePost($parser, $post, $graphNode, $uploadDirectory->path);
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
     * @param OpenGraphParser   $parser
     * @param FacebookPostModel $event
     * @param GraphNode         $graphNode
     * @param string            $uploadPath
     */
    private function updatePost(
        OpenGraphParser $parser,
        FacebookPostModel $event,
        GraphNode $graphNode,
        string $uploadPath
    ): void {
        $event->tstamp      = \time();
        $event->postTime    = $this->getTime($graphNode, 'created_time');
        $event->message     = \utf8_encode($graphNode->getField('message', ''));
        $event->image       = $this->getImage($parser, $graphNode, $uploadPath);
        $event->lastChanged = $this->getTime($graphNode, 'updated_time');
        $event->link        = $graphNode->getField('link', sprintf('https://facebook.com/%s', $event->postId));

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
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
     * @param OpenGraphParser $parser
     * @param GraphNode       $graphNode
     * @param string          $uploadPath
     *
     * @return null|string
     */
    private function getImage(OpenGraphParser $parser, GraphNode $graphNode, string $uploadPath): ?string
    {
        if (null === $graphNode->getField('picture', null)) {
            return null;
        }

        $metaData = serialize(
            [
                'caption' =>
                    [
                        'caption' => $graphNode->getField('caption', ''),
                        'link'    => $graphNode->getField('link', ''),
                    ]
            ]
        );

        // scrape/retrieve image
        $fileModel = null;
        if (null !== $objectId = $graphNode->getField('object_id', null)) {
            $fileModel = $this->imageScraper->scrapeObject(
                $parser,
                $graphNode->getField('type', ''),
                $objectId,
                $uploadPath
            );
        } elseif (null !== $pictureUri = $graphNode->getField('full_picture', null)) {
            $fileModel = $this->imageScraper->scrapeFile(
                'p_' . $graphNode->getField('id'),
                $pictureUri,
                $uploadPath
            );
        }

        // update meta data
        if (null !== $fileModel && $metaData !== $fileModel->meta) {
            $fileModel->name = $objectId;
            $fileModel->meta = $metaData;

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $fileModel->save();
        }

        return (null !== $fileModel) ? $fileModel->uuid : null;
    }
}