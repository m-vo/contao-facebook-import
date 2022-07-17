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

namespace Mvo\ContaoFacebookImport\Entity;

use Doctrine\ORM\Mapping as ORM;
use Facebook\GraphNodes\GraphNode;
use Mvo\ContaoFacebookImport\Image\ScrapingInformation;

/**
 * @ORM\Entity(repositoryClass="FacebookEventRepository")
 * @ORM\Table(name="tl_mvo_facebook_event")
 */
class FacebookEvent extends FacebookElement
{
    /**
     * @ORM\Column(name="fb_event_id", type="string", options={"default": ""})
     */
    protected $eventId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": ""})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="text", options={"default": ""})
     */
    protected $description;

    /**
     * @var int
     *
     * @ORM\Column(name="start_time", type="integer", options={"unsigned": true, "default": 0})
     */
    protected $startTime;

    /**
     * @var string
     *
     * @ORM\Column(name="location_name", type="string", options={"default": ""})
     */
    protected $locationName;

    /**
     * @var string
     *
     * @ORM\Column(name="ticket_uri", type="string", options={"default": ""})
     */
    protected $ticketUri;

    /**
     * FacebookEvent constructor.
     */
    public function __construct(string $eventId, FacebookNode $node, GraphNode $graphNode)
    {
        $this->eventId = $eventId;
        $this->facebookNode = $node;

        $this->updateFromGraphNode($graphNode);
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function updateFromGraphNode(GraphNode $graphNode): void
    {
        $this->name = utf8_encode($graphNode->getField('name', ''));
        $this->description = utf8_encode($graphNode->getField('description', ''));
        $this->startTime = $this->extractTimeFromGraphNode($graphNode, 'start_time');
        $this->locationName = utf8_encode($this->extractLocationNameFromGraphNode($graphNode));
        $this->ticketUri = $graphNode->getField('ticket_uri', '');

        $this->updateImage(ScrapingInformation::fromEventNode($graphNode));

        parent::updateFromGraphNode($graphNode);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getLocationName(): string
    {
        return $this->locationName;
    }

    public function getTicketUri(): string
    {
        return $this->ticketUri;
    }

    private function extractLocationNameFromGraphNode(GraphNode $graphNode): string
    {
        /** @var GraphNode|null $place */
        $place = $graphNode->getField('place', null);

        return null !== $place ? $place->getField('name', '') : '';
    }
}
