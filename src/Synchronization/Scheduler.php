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

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Mvo\ContaoFacebookImport\Entity\FacebookImage;
use Mvo\ContaoFacebookImport\Entity\FacebookNode;
use Mvo\ContaoFacebookImport\GraphApi\RequestQuotaExceededException;
use Mvo\ContaoFacebookImport\Image\ScraperAgent;
use Mvo\ContaoFacebookImport\Task\TaskRunner;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class Scheduler implements ContainerAwareInterface, FrameworkAwareInterface
{
    use ContainerAwareTrait;

    use FrameworkAwareTrait;

    /** @var Registry */
    private $doctrine;

    /** @var ScraperAgent */
    private $imageScraperAgent;

    /** @var PostSynchronizer */
    private $postSynchronizer;

    /** @var EventSynchronizer */
    private $eventSynchronizer;

    /** @var LoggerInterface */
    private $logger;

    /**
     * SynchronizationRequestListener constructor.
     */
    public function __construct(
        Registry $doctrine,
        ScraperAgent $imageScraperAgent,
        PostSynchronizer $postSynchronizer,
        EventSynchronizer $eventSynchronizer,
        LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->imageScraperAgent = $imageScraperAgent;
        $this->postSynchronizer = $postSynchronizer;
        $this->eventSynchronizer = $eventSynchronizer;
        $this->logger = $logger;
    }

    /**
     * Execute all tasks.
     */
    public function run(?int $nodeId = null): void
    {
        set_time_limit(0);
        $this->framework->initialize();

        $nodes = $this->doctrine
            ->getRepository(FacebookNode::class)
            ->findEnabled($nodeId);

        // priority 1: scrape images
        if (!$this->scrapeImages()) {
            // priority 2: synchronize posts and events
            $this->synchronizePostsAndEvents($nodes);
        }

        // purge quota logs
        $windowLength = $this->container->getParameter('mvo_contao_facebook_import.request_window_per_node');

        foreach ($nodes as $node) {
            $node->purgeQuotaLog($windowLength);
        }
        $this->doctrine->getManager()->flush();
    }

    public function synchronizePosts(FacebookNode $node): void
    {
        try {
            [$numCreated, $numUpdated, $numDeleted] = $this->postSynchronizer->run($node);
        } catch (RequestQuotaExceededException $e) {
            $this->log(
                sprintf(
                    'Facebook Event Synchronizer (Node ID%s): Request quota exceeded.',
                    $node->getId()
                )
            );

            return;
        }

        if (0 === $numCreated + $numUpdated + $numDeleted) {
            return;
        }

        $this->log(
            sprintf(
                'Facebook Post Synchronizer (Node ID%s): %d created / %d updated / %d deleted.',
                $node->getId(),
                $numCreated,
                $numUpdated,
                $numDeleted
            )
        );
    }

    public function synchronizeEvents(FacebookNode $node): void
    {
        try {
            [$numCreated, $numUpdated, $numDeleted] = $this->eventSynchronizer->run($node);
        } catch (RequestQuotaExceededException $e) {
            $this->log(
                sprintf(
                    'Facebook Event Synchronizer (Node ID%s): Request quota exceeded.',
                    $node->getId()
                )
            );

            return;
        }

        if (0 === $numCreated + $numUpdated + $numDeleted) {
            return;
        }

        $this->log(
            sprintf(
                'Facebook Event Synchronizer (Node ID%s): %d created / %d updated / %d deleted.',
                $node->getId(),
                $numCreated,
                $numUpdated,
                $numDeleted
            )
        );
    }

    private function scrapeImages(): bool
    {
        $maxExecutionTime = $this->container->getParameter('mvo_contao_facebook_import.max_execution_time');

        $elements = $this->doctrine
            ->getRepository(FacebookImage::class)
            ->findByWaitingToBeScraped();

        $numTotal = \count($elements);
        if (0 === $numTotal) {
            return false;
        }

        shuffle($elements);

        $numScraped = $this->imageScraperAgent->execute($elements, $maxExecutionTime);

        $this->log(
            sprintf(
                'Facebook Image Scraper: %d/%d images have been processed in this run.',
                $numScraped,
                $numTotal
            )
        );

        return true;
    }

    /**
     * @param FacebookNode[] $nodes
     */
    private function synchronizePostsAndEvents(array $nodes): void
    {
        $maxExecutionTime = $this->container->getParameter('mvo_contao_facebook_import.max_execution_time');

        $tasks = [
            function ($node) {
                $this->synchronizePosts($node);

                return true;
            },
            function ($node) {
                $this->synchronizeEvents($node);

                return true;
            },
        ];

        shuffle($nodes);
        shuffle($tasks);

        $taskRunner = new TaskRunner($maxExecutionTime);

        $taskRunner
            ->executeTimed($nodes, $tasks[0])
            ->executeTimed($nodes, $tasks[1]);
    }

    private function log(string $message)
    {
        $this->logger->info(
            $message,
            ['contao' => new ContaoContext(__METHOD__, ContaoContext::CRON)]
        );
    }
}
