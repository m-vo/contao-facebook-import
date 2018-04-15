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

use Contao\FilesModel;
use Facebook\GraphNodes\GraphNode;
use Mvo\ContaoFacebookImport\Facebook\OpenGraphParser;
use Mvo\ContaoFacebookImport\Model\FacebookEventModel;
use Mvo\ContaoFacebookImport\Model\FacebookModel;

class ImportFacebookEventsListener extends ImportFacebookDataListener
{
    /**
     * @param integer $pid
     *
     * @return integer
     */
    protected function getLastTimeStamp(int $pid): int
    {
        return FacebookEventModel::getLastTimestamp($pid);
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
        // find existing events
        $objEvents       = FacebookEventModel::findByPid($node->id);
        $eventDictionary = [];
        if (null !== $objEvents) {
            foreach ($objEvents as $objEvent) {
                /** @var FacebookEventModel $objEvent */
                $eventDictionary[$objEvent->eventId] = $objEvent;
            }
        }

        // query facebook for upcoming events
        $searchSince = strtotime('today midnight');
        $graphEdge   = $parser->queryEdge(
            'events',
            [
                'id',
                'name',
                'description',
                'start_time',
                'place',
                'cover',
                'ticket_uri',
                'updated_time'
            ],
            ['since' => $searchSince]
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

            if (\array_key_exists($fbId, $eventDictionary)) {
                // update existing item
                if ($this->updateRequired($graphNode, $eventDictionary[$fbId])) {
                    $this->updateEvent($parser, $eventDictionary[$fbId], $graphNode, $uploadDirectory->path);
                }
                unset($eventDictionary[$fbId]);

            } else {
                // create new item
                $event = new FacebookEventModel();

                $event->pid     = $node->id;
                $event->eventId = $fbId;
                $this->updateEvent($parser, $event, $graphNode, $uploadDirectory->path);
            }
        }

        // remove orphans
        /** @var FacebookEventModel $event */
        foreach ($eventDictionary as $event) {
            $event->delete();
        }
    }

    /**
     * @param GraphNode          $graphNode
     * @param FacebookEventModel $event
     *
     * @return bool
     */
    private function updateRequired(GraphNode $graphNode, FacebookEventModel $event): bool
    {
        return $this->getTime($graphNode, 'updated_time') !== $event->lastChanged;
    }

    /**
     * @param OpenGraphParser    $parser
     * @param FacebookEventModel $event
     * @param GraphNode          $graphNode
     * @param string             $uploadPath
     */
    private function updateEvent(
        OpenGraphParser $parser,
        FacebookEventModel $event,
        GraphNode $graphNode,
        string $uploadPath
    ): void {
        $event->tstamp       = \time();
        $event->name         = \utf8_encode($graphNode->getField('name', ''));
        $event->description  = \utf8_encode($graphNode->getField('description', ''));
        $event->startTime    = $this->getTime($graphNode, 'start_time');
        $event->locationName = \utf8_encode($this->getLocationName($graphNode));
        $event->image        = $this->getImage($parser, $graphNode, $uploadPath);
        $event->ticketUri    = $graphNode->getField('ticket_uri', '');
        $event->lastChanged  = $this->getTime($graphNode, 'updated_time');

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
     * @param GraphNode $graphNode
     *
     * @return string
     */
    private function getLocationName(GraphNode $graphNode): string
    {
        /** @var GraphNode $place */
        $place = $graphNode->getField('place', null);
        return ($place !== null) ? $place->getField('name', '') : '';
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
        if (null === ($cover = $graphNode->getField('cover', null))
            || null === ($objectId = $cover->getField('id', null))
        ) {
            return null;
        }

        $metaData = serialize(
            [
                'caption' =>
                    [
                        'caption' => $graphNode->getField('name', ''),
                        'link'    => sprintf('https://facebook.com/%s', $graphNode->getField('id', ''))
                    ]
            ]
        );

        // scrape/retrieve image
        $fileModel = $this->imageScraper->scrapePhoto($parser, $objectId, $uploadPath);

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