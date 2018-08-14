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

use Mvo\ContaoFacebookImport\Synchronization\Scheduler;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;


class ContaoCronListener implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/** @var Scheduler */
	private $scheduler;

	/**
	 * SynchronizationRequestListener constructor.
	 *
	 * @param Scheduler $scheduler
	 */
	public function __construct(Scheduler $scheduler)
	{
		$this->scheduler = $scheduler;
	}

	/**
	 * Entry point for background execution by the Contao cron.
	 */
	public function onExecuteByContaoCron(): void
	{
		if ('internal' !== $this->container->getParameter('mvo_contao_facebook_import.trigger_type')) {
			return;
		}

		$this->scheduler->run();
	}
}