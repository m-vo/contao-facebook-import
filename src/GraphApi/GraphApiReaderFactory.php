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

namespace Mvo\ContaoFacebookImport\GraphApi;

use Contao\CoreBundle\Monolog\ContaoContext;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Facebook\Exceptions\FacebookSDKException;
use Mvo\ContaoFacebookImport\Entity\FacebookNode;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class GraphApiReaderFactory implements ContainerAwareInterface
{
	use ContainerAwareTrait;

	/** @var ObjectManager */
	private $manager;

	/** @var LoggerInterface */
	private $logger;

	/** @var GraphApiReader[] */
	private static $readers = array();

	/**
	 * GraphApiReaderFactory constructor.
	 *
	 * @param Registry        $doctrine
	 * @param LoggerInterface $logger
	 */
	public function __construct(Registry $doctrine, LoggerInterface $logger)
	{
		$this->manager = $doctrine->getManager();
		$this->logger  = $logger;
	}

	/**
	 * @param FacebookNode $node
	 *
	 * @return GraphApiReader|null
	 */
	public function getTrackedReader(FacebookNode $node): ?GraphApiReader
	{
		if (array_key_exists($node->getId(), self::$readers)) {
			return self::$readers[$node->getId()];
		}

		try {

			[$appId, $appSecret, $accessToken, $pageName] = array_values($node->getFacebookApiCredentials());
			$reader = new GraphApiReader(
				$appId,
				$appSecret,
				$accessToken,
				$pageName,
				$this->logger,
				function () use ($node) {
					$this->trackRequest($node);
				}
			);

			self::$readers[$node->getId()] = $reader;

			return $reader;

		} catch (FacebookSDKException $e) {
			$this->logger->error(
				sprintf(
					'Facebook SDK: An error occurred trying to instantiate a GraphAPI reader for app id %s.',
					$appId ?? '(unknown)'
				),
				['exception' => $e, 'contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)]
			);
			return null;
		}
	}

	/**
	 * @param FacebookNode $node
	 *
	 * @return bool
	 * @throws RequestQuotaExceededException
	 */
	private function trackRequest(FacebookNode $node): bool
	{
		if (!$node->hasRequestQuotaAvailable(
			$this->container->getParameter('mvo_contao_facebook_import.request_window_per_node'),
			$this->container->getParameter('mvo_contao_facebook_import.request_limit_per_node')
		)) {
			throw new RequestQuotaExceededException($node);
		}

		$node->addRequest();
		$this->manager->persist($node);

		return true;
	}
}