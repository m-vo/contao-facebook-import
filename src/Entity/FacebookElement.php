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
 * @ORM\MappedSuperclass()
 */
abstract class FacebookElement extends DcaDefault
{
    /**
     * @var FacebookNode
     *
     * @ORM\ManyToOne(targetEntity="Mvo\ContaoFacebookImport\Entity\FacebookNode")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $facebookNode;

    /**
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $visible = true;

    /**
     * @var FacebookImage
     *
     * @ORM\OneToOne(
     *     targetEntity="Mvo\ContaoFacebookImport\Entity\FacebookImage",
     *     cascade={"persist"},
     *     orphanRemoval=true,
     *     fetch="EAGER"
     *	 )
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="SET NULL")
     */
    protected $image;

    /**
     * @var int
     *
     * @ORM\Column(name="last_changed", type="integer", options={"unsigned": true, "default": 0})
     */
    protected $lastChanged;

    /**
     * Get the associated facebook node for this entity.
     *
     * @return FacebookNode
     */
    public function getFacebookNode(): FacebookNode
    {
        return $this->facebookNode;
    }

    /**
     * @return FacebookImage|null
     */
    public function getImage(): ?FacebookImage
    {
        return $this->image;
    }

    /**
     * Returns true if this entity is older than a given graph node and should be updated.
     *
     * @param GraphNode $graphNode
     *
     * @return bool
     */
    public function shouldBeUpdated(GraphNode $graphNode): bool
    {
        return $this->extractTimeFromGraphNode($graphNode, 'updated_time') !== $this->lastChanged;
    }

    /**
     * @param GraphNode $graphNode
     */
    public function updateFromGraphNode(GraphNode $graphNode): void
    {
        $this->lastChanged = $this->extractTimeFromGraphNode($graphNode, 'updated_time');
    }

    public function updateImage(?ScrapingInformation $scrapingInformation): void
    {
        if (null === $scrapingInformation) {
            $this->image = null;

            return;
        }

        if (null === $this->image) {
            $this->image = new FacebookImage($this->getFacebookNode());
        }
        $this->image->updateScrapingInformation($scrapingInformation);
    }

    /**
     * @return int
     */
    public function getLastChanged(): int
    {
        return $this->lastChanged;
    }

    /**
     * @param GraphNode $graphNode
     * @param string    $field
     *
     * @return int
     */
    protected function extractTimeFromGraphNode(GraphNode $graphNode, string $field): int
    {
        /** @var \DateTime $date */
        $date = $graphNode->getField($field, null);

        return (null !== $date) ? $date->getTimestamp() : 0;
    }
}
