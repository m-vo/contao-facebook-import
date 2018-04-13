<?php

namespace Mvo\ContaoFacebookImport\EventListener;

use Contao\Dbafs;
use Contao\Files;
use Contao\FilesModel;
use Contao\Model\Collection;
use Facebook\GraphNodes\GraphNode;
use Mvo\ContaoFacebookImport\Facebook\ImageScraper;
use Mvo\ContaoFacebookImport\Facebook\Tools;
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
     * @param FacebookModel $node
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function import(FacebookModel $node) : void
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
        $graphEdge   = $node->getOpenGraphInstance()->queryEdge(
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

            if (array_key_exists($fbId, $eventDictionary)) {
                // update existing item
                if ($this->updateRequired($graphNode, $eventDictionary[$fbId])) {
                    $this->updateEvent($eventDictionary[$fbId], $graphNode, $imageScraper);
                }
                unset($eventDictionary[$fbId]);

            } else {
                // create new item
                $event = new FacebookEventModel();

                $event->pid    = $node->id;
                $event->eventId = $fbId;
                $this->updateEvent($event, $graphNode, $imageScraper);
            }
        }

        // remove orphans
        /** @var FacebookEventModel $post */
        foreach ($eventDictionary as $event) {
            // todo: generalize with dca's ondelete_callback
            if ($event->image && $file = FilesModel::findByUuid($event->image)) {
                /** @var Collection $objEvents */
                $objEvents = FacebookEventModel::findBy('image', $event->image);
                if (1 === $objEvents->count()) {
                    Files::getInstance()->delete($file->path);
                    Dbafs::deleteResource($file->path);
                }
            }
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
     * @param FacebookEventModel $event
     * @param GraphNode          $graphNode
     * @param ImageScraper       $imageScraper
     *
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function updateEvent(FacebookEventModel $event, GraphNode $graphNode, ImageScraper $imageScraper): void
    {
        $event->tstamp       = time();
        $event->name         = Tools::encodeText($graphNode->getField('name', ''));
        $event->description  = Tools::encodeText($graphNode->getField('description', ''));
        $event->startTime    = $this->getTime($graphNode, 'start_time');
        $event->locationName = Tools::encodeText($this->getLocationName($graphNode));
        $event->image        = $this->getImage($graphNode, $imageScraper);
        $event->ticketUri    = $graphNode->getField('ticket_uri', '');
        $event->lastChanged  = $this->getTime($graphNode, 'updated_time');

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
     * @param GraphNode    $graphNode
     * @param ImageScraper $imageScraper
     *
     * @return null|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getImage(GraphNode $graphNode, ImageScraper $imageScraper): ?string
    {
        if (null !== ($cover = $graphNode->getField('cover', null))
            && null !== ($objectId = $cover->getField('id', null))
        ) {
            $metaData = [
                'caption' =>
                    [
                        'caption' => $graphNode->getField('name', ''),
                        'link'    => sprintf('https://facebook.com/%s', $graphNode->getField('id', ''))
                    ]
            ];

            $fileModel = $imageScraper->scrapeObject(
                $objectId,
                'photo',
                serialize($metaData)
            );
            return (null !== $fileModel) ? $fileModel->uuid : null;
        }

        return null;
    }
}