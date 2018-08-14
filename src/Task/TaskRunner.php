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

namespace Mvo\ContaoFacebookImport\Task;

use Symfony\Component\Stopwatch\Stopwatch;

class TaskRunner
{
	/** @var int */
	private $maxExecutionTime;

	/** @var Stopwatch */
	private $stopwatch;

	/** @var int */
	private $estimatedExecutionTime = 0;

	/** @var int */
	private $processingCount = 0;

	/** @var mixed */
	private $lastProcessedPayload;

	/**
	 * TaskRunner constructor.
	 *
	 * @param int $maxExecutionTime in seconds
	 */
	public function __construct(int $maxExecutionTime)
	{
		$this->maxExecutionTime = 1000 * $maxExecutionTime;

		$this->stopwatch = new Stopwatch();
	}

	/**
	 * @param iterable $payloadList
	 * @param callable $executionCallback
	 * @param array    $params
	 *
	 * @return TaskRunner
	 */
	public function executeTimed(iterable $payloadList, callable $executionCallback, array $params = []): TaskRunner
	{
		if ($this->maxExecutionTime <= 0) {
			return $this;
		}

		$params = array_merge(
			[
				'lastElementBias'             => 2,
				'ignoreUnsuccessfulTasks'     => true,
				'resetEstimatedExecutionTime' => false
			],
			$params
		);

		$timerEvent = $this->stopwatch->start(md5(uniqid('', true)), 'FB_task-runner');

		if ($params['resetEstimatedExecutionTime']) {
			$this->estimatedExecutionTime = 0;
		}

		try {
			foreach ($payloadList as $payload) {
				// start measurement
				$startTime = $timerEvent->getDuration();

				// execute task
				$this->lastProcessedPayload = $payload;
				$success = true === $executionCallback($payload);

				// profile and predict execution time
				/** @noinspection DisconnectedForeachInstructionInspection for time measurement */
				$timerEvent->lap();

				$executionTime = $timerEvent->getDuration() - $startTime;

				if (0 !== $this->estimatedExecutionTime) {
					$this->estimatedExecutionTime =
						$params['ignoreUnsuccessfulTasks'] && !$success
							?
							max($this->estimatedExecutionTime, $executionTime)
							:
							($params['lastElementBias'] * $this->estimatedExecutionTime + $executionTime)
							/ ($params['lastElementBias'] + 1);
				} else {
					$this->estimatedExecutionTime = $executionTime;
				}

				// stop if max execution time would be exceeded
				if ($timerEvent->getDuration() + $this->estimatedExecutionTime >= $this->maxExecutionTime) {
					break;
				}
			}
		}
		finally {
			$timerEvent->stop();

			$this->processingCount  += \count($timerEvent->getPeriods()) - 1;
			$this->maxExecutionTime -= $timerEvent->getDuration();

			return $this;
		}
	}

	/**
	 * @return int
	 */
	public function getNumProcessedPayloads(): int
	{
		return $this->processingCount;
	}

	/**
	 * @return mixed
	 */
	public function getLastProcessedPayload() : mixed
	{
		return $this->lastProcessedPayload;
	}
}