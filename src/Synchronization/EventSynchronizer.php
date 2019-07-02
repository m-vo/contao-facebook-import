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
use Mvo\ContaoFacebookImport\Entity\FacebookEvent;
use Mvo\ContaoFacebookImport\Entity\FacebookNode;
use Mvo\ContaoFacebookImport\GraphApi\GraphApiReaderFactory;

class EventSynchronizer
{
    /** @var ObjectManager */
    private $manager;

    /** @var GraphApiReaderFactory */
    private $graphApiReaderFactory;

    /**
     * EventSynchronizer constructor.
     *
     * @param Registry              $doctrine
     * @param GraphApiReaderFactory $openGraphParserFactory
     */
    public function __construct(Registry $doctrine, GraphApiReaderFactory $openGraphParserFactory)
    {
        $this->manager = $doctrine->getManager();
        $this->graphApiReaderFactory = $openGraphParserFactory;
    }

    /**
     * Synchronize Facebook events.
     *
     * @param FacebookNode $node
     *
     * @throws \Mvo\ContaoFacebookImport\GraphApi\RequestQuotaExceededException
     *
     * @return array<int,int,int>
     */
    public function run(FacebookNode $node): array
    {
        $reader = $this->graphApiReaderFactory->getTrackedReader($node);
        if (null === $reader) {
            return [0, 0, 0];
        }

        // query facebook for upcoming events
        $graphNodes = $reader->getPageNodes(
            'events',
            [
                'id',
                'name',
                'description',
                'start_time',
                'place',
                'cover',
                'ticket_uri',
                'updated_time',
            ],
            ['since' => strtotime('today midnight')]
        );
        if (null === $graphNodes) {
            return [0, 0, 0];
        }

        // load existing events
        $events = $this->manager
            ->getRepository(FacebookEvent::class)
            ->findByFacebookNode($node);

        // synchronize
        $eventSynchronizer = new Synchronizer(
            function (FacebookEvent $localItem) {
                return $localItem->getEventId();
            },
            function (GraphNode $remoteItem) {
                return $remoteItem->getField('id', null);
            }
        );

        [$create, $update, $delete] =
            $eventSynchronizer->synchronize(
                $events,
                $graphNodes,
                function (FacebookEvent $event, GraphNode $graphNode) {
                    return $event->shouldBeUpdated($graphNode);
                }
            );

        // ... create items
        /** @var GraphNode $graphNode */
        foreach ($create as $eventId => $graphNode) {
            $this->manager->persist(new FacebookEvent((string) $eventId, $node, $graphNode));
        }

        // ... update items
        foreach ($update as $localRemotePair) {
            /** @var FacebookEvent $event */
            /** @var GraphNode $graphNode */
            [$event, $graphNode] = $localRemotePair;
            $event->updateFromGraphNode($graphNode);
        }

        // ... delete items
        /** @var FacebookEvent $event */
        foreach ($delete as $event) {
            $this->manager->remove($event);
        }

        $this->manager->flush();

        return [\count($create), \count($update), \count($delete)];
    }
}
