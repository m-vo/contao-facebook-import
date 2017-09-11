<?php

namespace Mvo\ContaoFacebookImport\EventListener;

use Contao\Controller;
use Contao\CoreBundle\Framework\FrameworkAwareInterface;
use Contao\CoreBundle\Framework\FrameworkAwareTrait;
use Contao\DC_Table;
use Mvo\ContaoFacebookImport\Model\FacebookModel;


abstract class ImportFacebookDataListener implements FrameworkAwareInterface
{
    use FrameworkAwareTrait;

    /**
     * Actually perform the import for the given node.
     *
     * @param FacebookModel $node
     */
    protected abstract function import(FacebookModel $node);

    /**
     * Get the most recent timestamp of an entry that belongs to the node with id $pid.
     *
     * @param integer $pid
     *
     * @return integer
     */
    protected abstract function getLastTimeStamp($pid): int;


    /**
     * Trigger import for a certain node.
     *
     * @param integer $id
     */
    public function onImport($id)
    {
        $this->framework->initialize();

        // get node
        $node = FacebookModel::findById($id);
        if (!$node) {
            throw new \Exception('Requested node does not exist.');
        }

        // skip nodes where importing is disabled or reimporting not necessary
        if (!$node->importEnabled || !$this->shouldReImport($node)) {
            return;
        }

        // import
        $this->import($node);
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
     * Trigger import without checking cache time / auto import setting for a certain node.
     *
     * @param DC_Table|integer $callerOrId
     */
    public function onForceImport($callerOrId)
    {
        if ($callerOrId instanceof DC_Table) {
            $id = $callerOrId->id;
        } else {
            if (is_numeric($callerOrId)) {
                $id = $callerOrId;
            } else {
                throw new \Exception('Invalid argument. Expect instance of DC_Table or numeric node id.');
            }
        }

        $this->framework->initialize();

        // get node
        $node = FacebookModel::findById($id);
        if (!$node) {
            throw new \Exception('Requested node does not exist.');
        }

        // import
        $this->import($node);

        // if called from within a dca (e.g. global operation) redirect afterwards
        if ($callerOrId instanceof DC_Table) {
            Controller::redirect(Controller::addToUrl(null, true, ['key']));
        }
    }

    /**
     * Trigger import for all nodes
     */
    public function onImportAll()
    {
        $nodes = FacebookModel::findAll();
        /** @var FacebookModel $node */
        foreach ($nodes as $node) {
            $this->onImport($node->id);
        }
    }

    /**
     * Trigger import without checking cache time / auto import setting for all nodes.
     */
    public function onForceImportAll()
    {
        $nodes = FacebookModel::findAll();
        /** @var FacebookModel $node */
        foreach ($nodes as $node) {
            $this->onForceImport($node->id);
        }
    }
}