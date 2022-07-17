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

namespace Mvo\ContaoFacebookImport\Synchronization;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Facebook\GraphNodes\GraphNode;
use Mvo\ContaoFacebookImport\Entity\FacebookNode;
use Mvo\ContaoFacebookImport\Entity\FacebookPost;
use Mvo\ContaoFacebookImport\GraphApi\GraphApiReaderFactory;
use Mvo\ContaoFacebookImport\GraphApi\RequestQuotaExceededException;

class PostSynchronizer
{
    /**
     * @var ObjectManager
     */
    private $manager;

    /**
     * @var GraphApiReaderFactory
     */
    private $graphApiReaderFactory;

    /**
     * PostSynchronizer constructor.
     */
    public function __construct(Registry $doctrine, GraphApiReaderFactory $openGraphParserFactory)
    {
        $this->manager = $doctrine->getManager();
        $this->graphApiReaderFactory = $openGraphParserFactory;
    }

    /**
     * Synchronize Facebook posts.
     *
     * @throws RequestQuotaExceededException
     *
     * @return array array<int,int,int>
     */
    public function run(FacebookNode $node): array
    {
        $reader = $this->graphApiReaderFactory->getTrackedReader($node);

        if (null === $reader) {
            return [0, 0, 0];
        }

        // query facebook for current posts
        $graphNodes = $reader->getPageNodes(
            'published_posts',
            [
                'id',
                'created_time',
                'message',
                'picture',
                'full_picture',
                'updated_time',
                'attachments{target{id}, media_type, title, url_unshimmed, url}',
            ],
            ['limit' => $node->getSynchronizationScope()]
        );

        if (null === $graphNodes) {
            return [0, 0, 0];
        }

        // load existing posts
        $posts = $this->manager
            ->getRepository(FacebookPost::class)
            ->findByFacebookNode($node)
        ;

        // synchronize
        $postSynchronizer = new Synchronizer(
            static fn (FacebookPost $localItem) => $localItem->getPostId(),
            static fn (GraphNode $remoteItem) => $remoteItem->getField('id', null)
        );

        [$create, $update, $delete] =
            $postSynchronizer->synchronize(
                $posts,
                $graphNodes,
                static fn (FacebookPost $post, GraphNode $graphNode) => $post->shouldBeUpdated($graphNode)
            );

        // ... create items
        /** @var GraphNode $graphNode */
        foreach ($create as $postId => $graphNode) {
            $this->manager->persist(new FacebookPost((string) $postId, $node, $graphNode));
        }

        // ... update items
        foreach ($update as $localRemotePair) {
            /** @var FacebookPost $post */
            /** @var GraphNode $graphNode */
            [$post, $graphNode] = $localRemotePair;
            $post->updateFromGraphNode($graphNode);

            $this->manager->persist($post);
        }

        // ... delete items
        /** @var FacebookPost $post */
        foreach ($delete as $post) {
            $this->manager->remove($post);
        }

        $this->manager->flush();

        return [\count($create), \count($update), \count($delete)];
    }
}
