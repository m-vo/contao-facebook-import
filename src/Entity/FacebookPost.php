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
 * @ORM\Entity(repositoryClass="FacebookPostRepository")
 * @ORM\Table(name="tl_mvo_facebook_post")
 */
class FacebookPost extends FacebookElement
{
    /** @var array List of known types */
    public const types = ['status', 'link', 'photo', 'video', 'event'];
    /**
     * @ORM\Column(name="fb_post_id", type="string", options={"default": ""})
     */
    protected $postId;

    /**
     * @var int
     *
     * @ORM\Column(name="post_time", type="integer", options={"unsigned": true, "default": 0})
     */
    protected $postTime;

    /**
     * @var string
     *
     * @ORM\Column(type="text", options={"default": ""})
     */
    protected $message;

    /**
     * @var string
     *
     * @ORM\Column(type="string", options={"default": ""})
     */
    protected $link;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16, options={"default": ""})
     */
    protected $type;

    /**
     * FacebookPost constructor.
     *
     * @param string       $postId
     * @param FacebookNode $node
     * @param GraphNode    $graphNode
     */
    public function __construct(string $postId, FacebookNode $node, GraphNode $graphNode)
    {
        $this->postId = $postId;
        $this->facebookNode = $node;

        $this->updateFromGraphNode($graphNode);
    }

    /**
     * @return string
     */
    public function getPostId(): string
    {
        return $this->postId;
    }

    /**
     * @param GraphNode $graphNode
     */
    public function updateFromGraphNode(GraphNode $graphNode): void
    {
        $attachments = $graphNode->getField('attachments', [])[0] ?? [];
        $message = $graphNode->getField('message') ?? $attachments['title'] ?? '';

        $this->postTime = $this->extractTimeFromGraphNode($graphNode, 'created_time');
        $this->message = utf8_encode($message);
        // Note: According to https://developers.facebook.com/docs/graph-api/changelog/version3.3
        //       the old `link` field should have become `attachments{url_unshimmed}`. The API
        //       however currently does not return any links under this endpoint, so we're using
        //       `url` as a fallback.
        $this->link = $attachments['url_unshimmed'] ?? self::unshimUrl($attachments['url'] ?? '') ?? '';
        $this->type = $attachments['media_type'] ?? 'status';

        $this->updateImage(ScrapingInformation::fromPostNode($graphNode));

        parent::updateFromGraphNode($graphNode);
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getPostTime(): int
    {
        return $this->postTime;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * Try to unshim a Facebook URL that follows a certain pattern.
     *
     * @param string $url
     *
     * @return string
     */
    private static function unshimUrl(string $url): string
    {
        $matches = [];
        if (1 !== preg_match('%https?://l\.facebook\.com/l.php\?u=(.*)&h=.*$%', $url, $matches)) {
            return $url;
        }

        return urldecode($matches[1]);
    }
}
