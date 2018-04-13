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

use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Mvo\ContaoFacebookImport\Facebook\ImageScraper;
use Mvo\ContaoFacebookImport\Facebook\OpenGraphParser;
use Mvo\ContaoFacebookImport\Facebook\OpenGraphParserFactory;
use Mvo\ContaoFacebookImport\Model\FacebookModel;

abstract class ImportFacebookDataListener implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /** @var OpenGraphParserFactory */
    private $openGraphParserFactory;

    /** @var ImageScraper */
    protected $imageScraper;

    /**
     * ImportFacebookDataListener constructor.
     *
     * @param OpenGraphParserFactory $openGraphParserFactory
     * @param ImageScraper           $imageScraper
     */
    public function __construct(OpenGraphParserFactory $openGraphParserFactory, ImageScraper $imageScraper)
    {
        $this->openGraphParserFactory = $openGraphParserFactory;
        $this->imageScraper           = $imageScraper;
    }

    /**
     * Actually perform the import for the given node.
     *
     * @param FacebookModel   $node
     * @param OpenGraphParser $parser
     */
    abstract protected function import(FacebookModel $node, OpenGraphParser $parser): void;

    /**
     * Get the most recent timestamp of an entry that belongs to the node with id $pid.
     *
     * @param integer $pid
     *
     * @return integer
     */
    abstract protected function getLastTimeStamp(int $pid): int;


    /**
     * Trigger import for a certain node.
     *
     * @param integer $id
     * @param bool    $forceImport
     *
     * @throws \InvalidArgumentException
     */
    public function onImport(int $id, bool $forceImport = false): void
    {
        $this->framework->initialize();

        // get node
        $node = FacebookModel::findById($id);
        if (!$node) {
            throw new \InvalidArgumentException('Requested node does not exist.');
        }

        // skip nodes where importing is disabled or reimporting not necessary
        if (!$forceImport && (!$node->importEnabled || !$this->shouldReImport($node))) {
            return;
        }

        // initialize parser
        $parser = $this->openGraphParserFactory->getParser($node);
        if (null === $parser) {
            return;
        }

        // import
        $this->import($node, $parser);
    }

    /**
     * @param FacebookModel $node
     *
     * @return bool Returns true if the present data exceeds the minimum cache time.
     */
    private function shouldReImport(FacebookModel $node): bool
    {
        $diff = time() - $this->getLastTimeStamp($node->id);
        return $diff >= $node->minimumCacheTime;
    }

    /**
     * Trigger import for all nodes
     *
     * @param bool $forceImport
     * @throws \InvalidArgumentException
     */
    public function onImportAll(bool $forceImport = false): void
    {
        $nodes = FacebookModel::findAll();
        /** @var FacebookModel $node */
        foreach ($nodes as $node) {
            $this->onImport((int)$node->id, $forceImport);
        }
    }
}