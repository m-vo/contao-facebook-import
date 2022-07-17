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

namespace Mvo\ContaoFacebookImport\Element;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\ContentElement;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Mvo\ContaoFacebookImport\Entity\FacebookPost;
use Mvo\ContaoFacebookImport\String\Tools;

class ContentPostList extends ContentElement
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'ce_mvo_facebook_post_list';

    /**
     * Parse the template.
     *
     * @return string Parsed element
     */
    public function generate(): string
    {
        if (TL_MODE === 'BE') {
            self::loadLanguageFile('elements');

            $objTemplate = new BackendTemplate('be_wildcard');

            $types = implode(', ', StringUtil::deserialize($this->mvo_facebook_allowed_post_types, true));

            $objTemplate->title = 'Facebook Posts';
            $objTemplate->wildcard = sprintf(
                $GLOBALS['TL_LANG']['MSC']['mvo_facebook_postListDisplay'],
                $this->mvo_facebook_number_of_elements
                 > 0 ? $this->mvo_facebook_number_of_elements : $GLOBALS['TL_LANG']['MSC']['mvo_facebook_allAvailable'],
                '' !== $types ? $types : '-'
            );

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Compile the content element.
     */
    protected function compile(): void
    {
        $doctrine = self::getContainer()->get('doctrine');

        // retrieve posts
        $posts = $doctrine
            ->getRepository(FacebookPost::class)
            ->findVisible(
                (int) $this->mvo_facebook_node,
                (int) $this->mvo_facebook_number_of_elements,
                StringUtil::deserialize($this->mvo_facebook_allowed_post_types, true)
            )
        ;

        // compile posts
        $compiledPosts = [];

        foreach ($posts as $post) {
            $compiledPosts[] = $this->compilePost($post);
        }

        $this->Template = new FrontendTemplate($this->strTemplate);
        $this->Template->setData($this->arrData);

        $this->Template->posts = $compiledPosts;
        $this->Template->hasPosts = 0 !== \count($compiledPosts);

        if (!$this->Template->hasPosts) {
            self::loadLanguageFile('elements');
            $this->Template->empty = $GLOBALS['TL_LANG']['MSC']['mvo_facebook_emptyPostList'];
        }
    }

    private function compilePost(FacebookPost $post): array
    {
        // base data
        $compiledPost = [
            'id' => $post->getId(),
            'postId' => $post->getPostId(),
            'lastChanged' => $post->getLastChanged(),
            'type' => $post->getType(),
            'message' => Tools::formatText($post->getMessage()),
            'link' => $post->getLink(),
            'time' => $post->getPostTime(),
            'datetime' => date(Config::get('datimFormat'), $post->getPostTime()),
            'href' => sprintf('https://facebook.com/%s', $post->getPostId()),
        ];

        // image
        if (
            null !== ($image = $post->getImage())
            && null !== ($file = $image->getFile())
        ) {
            $metaData = StringUtil::deserialize($file->meta, true);

            $imageTemplate = new FrontendTemplate('image');
            self::addImageToTemplate(
                $imageTemplate,
                [
                    'singleSRC' => $file->path,
                    'alt' => $metaData['caption']['caption'] ?? 'Facebook Post Image',
                    'size' => StringUtil::deserialize($this->size),
                    'fullsize' => $this->fullsize,
                ]
            );
            $compiledPost['image'] = $imageTemplate->parse();
            $compiledPost['hasImage'] = true;
        } else {
            $compiledPost['hasImage'] = false;
        }

        $compiledPost['getExcerpt'] = static fn (int $words, int $wordOffset = 0) => Tools::shortenText($compiledPost['message'], $words, $wordOffset);

        return $compiledPost;
    }
}
