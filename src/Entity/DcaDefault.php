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

/**
 * @ORM\MappedSuperclass()
 * @ORM\HasLifecycleCallbacks()
 */
abstract class DcaDefault
{
    /**
     * @ORM\Column(type="integer", options={"unsigned": true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="tstamp", type="integer", options={"unsigned": true, "default": 0})
     */
    protected $timestamp;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Update the entry's timestamp.
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function touch(): void
    {
        $this->timestamp = time();
    }
}
