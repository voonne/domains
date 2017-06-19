<?php

/**
 * This file is part of the Voonne platform (http://www.voonne.org)
 *
 * Copyright (c) 2016 Jan Lavička (mail@janlavicka.name)
 *
 * For the full copyright and license information, please view the file licence.md that was distributed with this source code.
 */

namespace Voonne\Domains;

use Doctrine\ORM\EntityManagerInterface;
use Nette\SmartObject;
use Voonne\Voonne\Model\Entities\Domain;
use Voonne\Voonne\Model\Entities\DomainLanguage;
use Voonne\Voonne\Model\Repositories\DomainRepository;
use Voonne\Voonne\Model\Repositories\LanguageRepository;


class DomainManager
{

	use SmartObject;

	/**
	 * @var EntityManagerInterface
	 */
	private $entityManager;

	/**
	 * @var DomainRepository
	 */
	private $domainRepository;

	/**
	 * @var LanguageRepository
	 */
	private $languageRepository;

	/**
	 * @var array
	 */
	private $domains = [];

	private $isoCodes = [
		'ab', 'aa', 'af', 'ak', 'sq',
		'am', 'ar', 'an', 'hy', 'as',
		'av', 'ae', 'ay', 'az', 'bm',
		'ba', 'eu', 'be', 'bn', 'bh',
		'bi', 'bs', 'br', 'bg', 'my',
		'ca', 'ch', 'ce', 'ny', 'zh',
		'cv', 'kw', 'co', 'cr', 'hr',
		'cs', 'da', 'dv', 'nl', 'dz',
		'en', 'eo', 'et', 'ee', 'fo',
		'fj', 'fi', 'fr', 'ff', 'gl',
		'ka', 'de', 'el', 'gn', 'gu',
		'ht', 'ha', 'he', 'hz', 'hi',
		'ho', 'hu', 'ia', 'id', 'ie',
		'ga', 'ig', 'ik', 'io', 'is',
		'it', 'iu', 'ja', 'jv', 'kl',
		'kn', 'kr', 'ks', 'kk', 'km',
		'ki', 'rw', 'ky', 'kv', 'kg',
		'ko', 'ku', 'kj', 'la', 'lb',
		'lg', 'li', 'ln', 'lo', 'lt',
		'lu', 'lv', 'gv', 'mk', 'mg',
		'ms', 'ml', 'mt', 'mi', 'mr',
		'mh', 'mn', 'na', 'nv', 'nd',
		'ne', 'ng', 'nb', 'nn', 'no',
		'ii', 'nr', 'oc', 'oj', 'cu',
		'om', 'or', 'os', 'pa', 'pi',
		'fa', 'pl', 'ps', 'pt', 'qu',
		'rm', 'rn', 'rc', 'ro', 'ru',
		'sa', 'sc', 'sd', 'se', 'sm',
		'sg', 'sr', 'gd', 'sn', 'si',
		'sk', 'sl', 'so', 'st', 'es',
		'su', 'sw', 'ss', 'sv', 'ta',
		'te', 'tg', 'th', 'ti', 'bo',
		'tk', 'tl', 'tn', 'to', 'tr',
		'ts', 'tt', 'tw', 'ty', 'ug',
		'uk', 'ur', 'uz', 've', 'vi',
		'vo', 'wa', 'cy', 'wo', 'fy',
		'xh', 'yi', 'yo', 'za', 'zu'
	];


	public function __construct(
		EntityManagerInterface $entityManager,
		DomainRepository $domainRepository,
		LanguageRepository $languageRepository
	) {
		$this->entityManager = $entityManager;
		$this->domainRepository = $domainRepository;
		$this->languageRepository = $languageRepository;
	}


	/**
	 * Adds domain into system.
	 *
	 * @param string $domain
	 *
	 * @throws DuplicateEntryException
	 */
	public function addDomain($domain)
	{
		if (isset($this->domains[$domain])) {
			throw new DuplicateEntryException("Domain '$domain' has been already registered.");
		}

		$this->domains[$domain] = [];
	}


	/**
	 * Added language into domain.
	 *
	 * @param $domain
	 * @param $language
	 *
	 * @throws DuplicateEntryException
	 * @throws InvalidArgumentException
	 */
	public function addDomainLanguage($domain, $language)
	{
		if (!isset($this->domains[$domain])) {
			throw new InvalidArgumentException("Domain '$domain' has not been registered.");
		}

		if (!in_array($language, $this->isoCodes)) {
			throw new InvalidArgumentException("Language '$language' is not valid ISO code.");
		}

		if (in_array($language, $this->domains[$domain])) {
			throw new DuplicateEntryException("Language '$language' for domain '$domain' has been already registered.");
		}

		$this->domains[$domain][] = $language;
	}


	/**
	 * Synchronize domains between system and database.
	 */
	public function synchronize()
	{
		$domains = [];

		foreach ($this->domainRepository->findAll() as $domain) {
			/** @var Domain $domain */
			$domains[$domain->getName()] = [];

			foreach ($domain->getDomainLanguages() as $domainLanguage) {
				/** @var DomainLanguage $domainLanguage */
				$domains[$domain->getName()][] = $domainLanguage->getLanguage()->getIsoCode();
			}
		}

		foreach ($this->domains as $domainName => $languages) {
			if (!isset($domains[$domainName])) {
				$domain = new Domain($domainName);

				$this->entityManager->persist($domain);
			} else {
				$domain = $this->domainRepository->findOneBy(['name' => $domainName]);
			}

			foreach ($languages as $languageName) {
				if (!isset($domains[$domainName]) || !in_array($languageName, $domains[$domainName])) {
					$domainLanguage = new DomainLanguage(
						$domain,
						$this->languageRepository->findOneBy(['isoCode' => $languageName])
					);

					$this->entityManager->persist($domainLanguage);
				}
			}
		}

		$this->entityManager->flush();
	}

}
