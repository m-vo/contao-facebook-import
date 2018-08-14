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

namespace Mvo\ContaoFacebookImport\Synchronization;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Facebook\GraphNodes\GraphNode;
use Mvo\ContaoFacebookImport\Entity\FacebookNode;
use Mvo\ContaoFacebookImport\Entity\FacebookPost;
use Mvo\ContaoFacebookImport\GraphApi\GraphApiReaderFactory;

class PostSynchronizer
{
	/** @var ObjectManager */
	private $manager;

	/** @var GraphApiReaderFactory */
	private $graphApiReaderFactory;

	/**
	 * PostSynchronizer constructor.
	 *
	 * @param Registry              $doctrine
	 * @param GraphApiReaderFactory $openGraphParserFactory
	 */
	public function __construct(Registry $doctrine, GraphApiReaderFactory $openGraphParserFactory)
	{
		$this->manager               = $doctrine->getManager();
		$this->graphApiReaderFactory = $openGraphParserFactory;
	}

	/**
	 * Synchronize Facebook posts.
	 *
	 * @param FacebookNode $node
	 *
	 * @return array<int,int,int>
	 * @throws \Mvo\ContaoFacebookImport\GraphApi\RequestQuotaExceededException
	 */
	public function run(FacebookNode $node): array
	{
		$reader = $this->graphApiReaderFactory->getTrackedReader($node);
		if (null === $reader) {
			return [0, 0, 0];
		}

		// query facebook for current posts
		$graphNodes = $reader->getPageNodes(
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
			['limit' => $node->getSynchronizationScope()]
		);

		// load existing posts
		$posts = $this->manager
			->getRepository(FacebookPost::class)
			->findByFacebookNode($node);

		// synchronize
		$postSynchronizer = new Synchronizer(
			function (FacebookPost $localItem) {
				return $localItem->getPostId();
			},
			function (GraphNode $remoteItem) {
				return $remoteItem->getField('id', null);
			}
		);

		[$create, $update, $delete] =
			$postSynchronizer->synchronize(
				$posts,
				$graphNodes,
				function (FacebookPost $post, GraphNode $graphNode) {
					return $post->shouldBeUpdated($graphNode);
				}
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