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

/**
 * @ORM\Entity(repositoryClass="FacebookNodeRepository")
 * @ORM\Table(name="tl_mvo_facebook")
 */
class FacebookNode extends DcaDefault
{
	/**
	 * @ORM\Column(type="string", options={"default": ""})
	 */
	protected $description;

	/**
	 * @ORM\Column(name="fb_app_id", type="string", options={"default": ""})
	 */
	protected $fbAppId;

	/**
	 * @ORM\Column(name="fb_app_secret", type="string", options={"default": ""})
	 */
	protected $fbAppSecret;

	/**
	 * @ORM\Column(name="fb_access_token", type="string", options={"default": ""})
	 */
	protected $fbAccessToken;

	/**
	 * @ORM\Column(name="fb_page_name", type="string", options={"default": ""})
	 */
	protected $fbPageName;

	/**
	 * @ORM\Column(name="number_of_posts", type="integer", options={"unsigned": true, "default": 100})
	 */
	protected $numberOfPosts;

	/**
	 * @ORM\Column(name="import_enabled", type="boolean", options={"default": false})
	 */
	protected $importEnabled;

	/**
	 * @ORM\Column(name="upload_directory", type="binary_string", length=16, options={"fixed": true}, nullable=true)
	 */
	protected $uploadDirectory;

	/**
	 * @var array
	 *
	 * @ORM\Column(name="request_quota_log", type="simple_array", nullable=true)
	 */
	protected $requestQuotaLog = [];


	/**
	 * @return array
	 */
	public function getFacebookApiCredentials(): array
	{
		return [
			'appId'       => $this->fbAppId,
			'appSecret'   => $this->fbAppSecret,
			'accessToken' => $this->fbAccessToken,
			'pageName'    => $this->fbPageName
		];
	}

	/**
	 * @return int
	 */
	public function getSynchronizationScope(): int
	{
		return $this->numberOfPosts;
	}

	/** @noinspection ReturnTypeCanBeDeclaredInspection
	 * Can't set \Contao\FilesModel as long as Contao\FilesModel extends from
	 * \Model (global namespace) which doesn't exist at cache creation time and
	 * would break.
	 */
	/**
	 * @return \Contao\FilesModel|null
	 */
	public function getUploadDirectory()
	{
		return \Contao\FilesModel::findByUuid($this->uploadDirectory);
	}

	/**
	 * Add request to available quota.
	 */
	public function addRequest(): void
	{
		if(null === $this->requestQuotaLog) {
			$this->requestQuotaLog = [];
		}
		$this->requestQuotaLog[] = time();
	}

	/**
	 * @param int $requestWindowLength in seconds
	 * @param int $allowedRequestCount
	 *
	 * @return bool
	 */
	public function hasRequestQuotaAvailable(int $requestWindowLength, int $allowedRequestCount): bool
	{
		if(null === $this->requestQuotaLog) {
			return $allowedRequestCount > 0;
		}

		$threshold = time() - $requestWindowLength;

		// tally elements in window
		$requestCount = 0;
		for ($i = \count($this->requestQuotaLog) - 1; $i >= 0; $i--) {
			if ($this->requestQuotaLog[$i] < $threshold) {
				break;
			}
			$requestCount++;
		}

		return $requestCount < $allowedRequestCount;
	}

	/**
	 * @param int $requestWindowLength in seconds
	 */
	public function purgeQuotaLog(int $requestWindowLength): void
	{
		if(null === $this->requestQuotaLog) {
			return;
		}

		$threshold = time() - $requestWindowLength;

		// remove elements
		foreach ($this->requestQuotaLog as $index => $value) {
			if ($value < $threshold) {
				unset($this->requestQuotaLog[$index]);
				continue;
			}

			return;
		}
	}
}