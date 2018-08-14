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

namespace Mvo\ContaoFacebookImport\Synchronization;

class Synchronizer
{
	/** @var \callable */
	private $getRemoteIdentifier;

	/** @var \callable */
	private $getIdentifier;

	/**
	 * Synchronizer constructor.
	 *
	 * @param callable $getRemoteIdentifier Callable will get called with a local item and expects the remote
	 *                                      identifier stored within that item (= extract remote id).
	 * @param callable $getIdentifier       Callable will get called with a remote item and expects the identifier of
	 *                                      that item (= extract id).
	 */
	public function __construct(callable $getRemoteIdentifier, callable $getIdentifier)
	{
		$this->getRemoteIdentifier = $getRemoteIdentifier;
		$this->getIdentifier       = $getIdentifier;
	}

	/**
	 * @param iterable<L>   $localItems       List of existing local items (= target).
	 * @param iterable<R>   $remoteItems      List of remote items (= source).
	 * @param callable|null $isUpdateRequired Optional function to determine if an update is required. Gets called with
	 *                                        a local and remote item and expects a boolean return value.
	 *
	 * @return array<array<R>,array<array<L,R>>,array<L>> Resulting lists for items to be created (= only R) / updated
	 *                                                    (= on both sides) / deleted (= only L).
	 */
	public function synchronize(iterable $localItems, iterable $remoteItems, callable $isUpdateRequired = null): array
	{
		$create = [];
		$update = [];
		$delete = [];

		// create temporary lookup dictionary
		$localItemsDictionary = [];
		foreach ($localItems as $localItem) {
			$remoteIdentifier = ($this->getRemoteIdentifier)($localItem);
			if (null === $remoteIdentifier) {
				// mark invalid item for deletion
				$delete[] = $localItem;
			}

			$localItemsDictionary[$remoteIdentifier] = $localItem;
		}

		// match items for creation and update
		foreach ($remoteItems as $remoteItem) {
			$id = ($this->getIdentifier)($remoteItem);
			if (null === $id) {
				// skip unidentifiable item
				continue;
			}

			if (!array_key_exists($id, $localItemsDictionary)) {
				$create[$id] = $remoteItem;
			} else {
				if (null === $isUpdateRequired && $isUpdateRequired($localItemsDictionary[$id], $remoteItem)) {
					$update[$id] = [$localItemsDictionary[$id], $remoteItem];
				}
				unset($localItemsDictionary[$id]);
			}
		}

		// mark orphans for deletion
		foreach ($localItemsDictionary as $localItem) {
			$delete[] = $localItem;
		}

		return [$create, $update, $delete];
	}
}