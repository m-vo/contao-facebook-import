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

	/** @var array List of known types */
	public const types = ['status', 'link', 'photo', 'video', 'event'];

	/**
	 * FacebookPost constructor.
	 *
	 * @param string       $postId
	 * @param FacebookNode $node
	 * @param GraphNode    $graphNode
	 */
	public function __construct(string $postId, FacebookNode $node, GraphNode $graphNode)
	{
		$this->postId       = $postId;
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
		$message = $graphNode->getField('message', null) ??
				   $graphNode->getField('caption', '');

		$this->postTime = $this->extractTimeFromGraphNode($graphNode, 'created_time');
		$this->message  = utf8_encode($message);
		$this->link     = $graphNode->getField('link', '');
		$this->type     = $graphNode->getField('type', '');

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

}