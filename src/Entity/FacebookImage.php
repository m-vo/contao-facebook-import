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

use Contao\File;
use Contao\FilesModel;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Mvo\ContaoFacebookImport\Image\ScrapableItemInterface;
use Mvo\ContaoFacebookImport\Image\ScrapingInformation;

/**
 * @ORM\Entity(repositoryClass="FacebookImageRepository")
 * @ORM\Table(name="tl_mvo_facebook_image")
 * @ORM\HasLifecycleCallbacks()
 */
class FacebookImage extends DcaDefault implements ScrapableItemInterface
{
    /**
     * @ORM\Column(name="uuid", type="binary_string", length=16, options={"fixed": true}, nullable=true)
     */
    protected $uuid;

    /**
     * @ORM\Column(type="binary_string", length=65535, nullable=true)
     */
    protected $scrapingInformation;

    /**
     * @ORM\Column(type="smallint", length=1, options={"default": 0})
     */
    protected $scrapingState = ScrapableItemInterface::STATE_NONE;

    /**
     * @var FacebookNode
     *
     * @ORM\ManyToOne(targetEntity="Mvo\ContaoFacebookImport\Entity\FacebookNode")
     * @ORM\JoinColumn(name="issuer", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $issuer;

    /**
     * FacebookImage constructor.
     */
    public function __construct(FacebookNode $issuer)
    {
        $this->issuer = $issuer;
    }

    /**
     * Get the stored scraping information.
     */
    public function getScrapingInformation(): ?ScrapingInformation
    {
        return ScrapingInformation::deserialize($this->scrapingInformation);
    }

    /**
     * Indicate scraping with the current scraping information was successful.
     *
     * @param string $imageUuid the scraped image uuid
     */
    public function setScrapingSuccess(string $imageUuid): void
    {
        $this->uuid = $imageUuid;
        $this->scrapingState = ScrapableItemInterface::STATE_SUCCESS;
    }

    /**
     * Indicate scraping with the current scraping information has failed.
     */
    public function setScrapingError(): void
    {
        $this->uuid = null;
        $this->scrapingState = ScrapableItemInterface::STATE_ERROR;
    }

    /**
     * Set or reset scraping information for this element.
     *
     * @param ScrapingInformation|null $scrapingInformation optional scraping information to set
     */
    public function resetScrapingState(?ScrapingInformation $scrapingInformation = null): void
    {
        $state = null === $scrapingInformation ?
            ScrapableItemInterface::STATE_NONE : ScrapableItemInterface::STATE_WAITING;

        $this->uuid = null;
        $this->scrapingState = $state;
        $this->scrapingInformation = ScrapingInformation::serialize($scrapingInformation);
    }

    /**
     * Return true if the given scraping information matches with the stored one.
     */
    public function matchScrapingInformation(?ScrapingInformation $scrapingInformation): bool
    {
        if (null === $scrapingInformation) {
            return null === $this->scrapingInformation;
        }

        return ScrapingInformation::serialize($scrapingInformation) === $this->scrapingInformation;
    }

    public function updateScrapingInformation(ScrapingInformation $scrapingInformation): void
    {
        if (ScrapingInformation::serialize($scrapingInformation) !== $this->scrapingInformation) {
            $this->resetScrapingState($scrapingInformation);
        }
    }

    public function getIssuerNode(): FacebookNode
    {
        return $this->issuer;
    }

    /** @noinspection ReturnTypeCanBeDeclaredInspection
     * Can't set \Contao\FilesModel as long as Contao\FilesModel extends from
     * \Model (global namespace) which doesn't exist at cache creation time and
     * would break.
     */

    /**
     * @return FilesModel|null
     */
    public function getFile()
    {
        return FilesModel::findByUuid($this->uuid);
    }

    public function getScrapingState(): int
    {
        return $this->scrapingState;
    }

    /**
     * @ORM\PreRemove()
     */
    public function removeLinkedResourceFromFilesystem(LifecycleEventArgs $args): void
    {
        if (null === $this->uuid) {
            return;
        }

        // check if the same uuid is still used by another element
        /** @noinspection SelfClassReferencingInspection */
        if (
            $args->getEntityManager()
                ->getRepository(self::class)
                ->count(['uuid' => $this->uuid]) > 1
        ) {
            return;
        }

        // if not, remove file from filesystem as well
        if (null !== $file = FilesModel::findByUuid($this->uuid)) {
            try {
                (new File($file->path))->delete();
            } catch (\Exception $e) {
                // ignore
            }
        }
    }
}
