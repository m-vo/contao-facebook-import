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

namespace Mvo\ContaoFacebookImport\Image;

use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\FilesModel;
use Doctrine\Bundle\DoctrineBundle\Registry;
use GuzzleHttp\Exception\ClientException;
use Mvo\ContaoFacebookImport\Entity\FacebookImage;
use Mvo\ContaoFacebookImport\GraphApi\GraphApiReaderFactory;
use Mvo\ContaoFacebookImport\GraphApi\RequestQuotaExceededException;
use Mvo\ContaoFacebookImport\Task\TaskRunner;
use Psr\Log\LoggerInterface;

class ScraperAgent
{
	/** @var Registry */
	private $doctrine;

	/** @var Scraper */
	private $scraper;

	/** @var GraphApiReaderFactory */
	private $graphApiReaderFactory;

	/** @var LoggerInterface */
	private $logger;


	/**
	 * ScraperAgent constructor.
	 *
	 * @param Registry              $doctrine
	 * @param Scraper               $scraper
	 * @param GraphApiReaderFactory $openGraphParserFactory
	 * @param LoggerInterface       $logger
	 */
	public function __construct(
		Registry $doctrine,
		Scraper $scraper,
		GraphApiReaderFactory $openGraphParserFactory,
		LoggerInterface $logger
	) {
		$this->doctrine              = $doctrine;
		$this->scraper               = $scraper;
		$this->graphApiReaderFactory = $openGraphParserFactory;
		$this->logger                = $logger;
	}


	/**
	 * Batch scrape a list of requested images until execution time is reached.
	 *
	 * @param FacebookImage[] $images           that implement ScrapableItem
	 * @param int             $maxExecutionTime Allowed time in seconds.
	 *
	 * @return int Number of items processed.
	 */
	public function execute(array $images, int $maxExecutionTime): int
	{
		$taskRunner = new TaskRunner($maxExecutionTime);

		$excludedIssuers = [];

		$task = function (FacebookImage $image) use ($excludedIssuers) {
			$issuer = $image->getIssuerNode();

			if (isset($excludedIssuers[$issuer->getId()])) {
				return false;
			}
			try {
				return $this->scrape($image);
			} catch (RequestQuotaExceededException $e) {
				$excludedIssuers[] = $issuer;
				return false;
			}
		};

		try {
			$taskRunner
				->executeTimed(
					$images,
					$task
				);
		} catch (\Exception $e) {
			$this->logError(
				$taskRunner->getLastProcessedPayload(),
				$e->getMessage(),
				ContaoContext::ERROR
			);
		}

		$this->doctrine->getManager()->flush();

		return $taskRunner->getNumProcessedPayloads();
	}

	/**
	 * @param FacebookImage $image
	 *
	 * @return bool
	 * @throws RequestQuotaExceededException
	 */
	private function scrape(FacebookImage $image): bool
	{
		// get facebook node
		$node = $image->getIssuerNode();

		// get reader
		$reader = $this->graphApiReaderFactory->getTrackedReader($node);
		if (null === $reader) {
			throw new \RuntimeException('No GraphAPI reader available. Aborting.', $node->getId());
		}

		// get destination path
		$uploadDirectory = $node->getUploadDirectory();
		if (null === $uploadDirectory) {
			throw new \RuntimeException('No upload directory specified. Aborting.', $node->getId());
		}
		$destinationPath = $uploadDirectory->path;

		// get scraping information
		$scrapingInformation = $image->getScrapingInformation();
		if (null === $scrapingInformation) {

			$this->logError(
				$image,
				'Scraping information missing or corrupt. Skipping.',
				ContaoContext::ERROR
			);

			$image->setScrapingError();
			return false;
		}

		// run
		$imageFile = $this->scraper->scrape($scrapingInformation, $destinationPath, $reader, $error);

		if (null !== $error) {
			// render a user friendly error message

			if (\is_string($error)) {
				$this->logError(
					$image,
					sprintf(
						'%s Skipping.',
						$error
					),
					ContaoContext::CRON
				);
			} elseif (\get_class($error) === ClientException::class) {
				$this->logError(
					$image,
					'Could not download image. Skipping.',
					ContaoContext::CRON
				);
			} else {
				$this->logError(
					$image,
					sprintf(
						'Unknown error when processing image information: `%s` Skipping.',
						$error instanceof \Exception ? $error->getMessage() : '-'
					),
					ContaoContext::ERROR
				);
			}

			$image->setScrapingError();
			return false;
		}

		/** @noinspection NullPointerExceptionInspection */
		$image->setScrapingSuccess($imageFile->uuid);
		return true;
	}

	/**
	 * @param FacebookImage $image
	 * @param string        $message
	 * @param string        $errorLevel
	 */
	private function logError(FacebookImage $image, string $message, string $errorLevel): void
	{
		$message = sprintf(
			'Image Scraper: %s Source: ID%s [Facebook Node ID%s]',
			$message,
			$image->getId(),
			$image->getIssuerNode()->getId()
		);

		$context = ['contao' => new ContaoContext(__METHOD__, $errorLevel)];

		if (ContaoContext::ERROR === $errorLevel) {
			$this->logger->error($message, $context);
		} else {
			$this->logger->warning($message, $context);
		}
	}
}