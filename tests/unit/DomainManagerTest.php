<?php

namespace Voonne\TestDomains;

use Codeception\Test\Unit;
use Doctrine\ORM\EntityManagerInterface;
use Mockery;
use Mockery\MockInterface;
use UnitTester;
use Voonne\Domains\DomainManager;
use Voonne\Domains\DuplicateEntryException;
use Voonne\Domains\InvalidArgumentException;
use Voonne\Voonne\Model\Entities\Domain;
use Voonne\Voonne\Model\Entities\DomainLanguage;
use Voonne\Voonne\Model\Entities\Language;
use Voonne\Voonne\Model\Repositories\DomainRepository;
use Voonne\Voonne\Model\Repositories\LanguageRepository;


class DomainManagerTest extends Unit
{

	/**
	 * @var UnitTester
	 */
	protected $tester;

	/**
	 * @var MockInterface
	 */
	private $entityManager;

	/**
	 * @var MockInterface
	 */
	private $domainRepository;

	/**
	 * @var MockInterface
	 */
	private $languageRepository;

	/**
	 * @var DomainManager
	 */
	private $domainManager;


	protected function _before()
	{
		$this->entityManager = Mockery::mock(EntityManagerInterface::class);
		$this->domainRepository = Mockery::mock(DomainRepository::class);
		$this->languageRepository = Mockery::mock(LanguageRepository::class);

		$this->domainManager = new DomainManager($this->entityManager, $this->domainRepository, $this->languageRepository);
	}


	protected function _after()
	{
		Mockery::mock();
	}


	public function testAddDomain()
	{
		$this->domainManager->addDomain('example1.com');
		$this->domainManager->addDomain('example2.com');
	}


	public function testAddDomainDuplicateEntry()
	{
		$this->domainManager->addDomain('example1.com');

		$this->expectException(DuplicateEntryException::class);

		$this->domainManager->addDomain('example1.com');
	}


	public function testAddDomainLanguage()
	{
		$this->domainManager->addDomain('example1.com');
		$this->domainManager->addDomainLanguage('example1.com', 'cs');
		$this->domainManager->addDomainLanguage('example1.com', 'en');
	}


	public function testAddDomainLanguageDuplicateEntry()
	{
		$this->domainManager->addDomain('example1.com');
		$this->domainManager->addDomainLanguage('example1.com', 'cs');

		$this->expectException(DuplicateEntryException::class);

		$this->domainManager->addDomainLanguage('example1.com', 'cs');
	}


	public function testAddDomainLanguageDomainNotExist()
	{
		$this->expectException(InvalidArgumentException::class);

		$this->domainManager->addDomainLanguage('example1.com', 'cs');
	}


	public function testAddDomainLanguageInvalidISOCode()
	{
		$this->domainManager->addDomain('example1.com');

		$this->expectException(InvalidArgumentException::class);

		$this->domainManager->addDomainLanguage('example1.com', 'invalid');
	}


	public function testSynchronize()
	{
		$languageCs = Mockery::mock(Language::class);
		$languageEn = Mockery::mock(Language::class);

		$this->domainManager->addDomain('example1.com');
		$this->domainManager->addDomainLanguage('example1.com', 'cs');
		$this->domainManager->addDomainLanguage('example1.com', 'en');

		$this->domainRepository->shouldReceive('findAll')
			->once()
			->withNoArgs()
			->andReturn([]);

		$this->languageRepository->shouldReceive('findOneBy')
			->once()
			->with(['isoCode' => 'cs'])
			->andReturn($languageCs);

		$this->languageRepository->shouldReceive('findOneBy')
			->once()
			->with(['isoCode' => 'en'])
			->andReturn($languageEn);

		$this->entityManager->shouldReceive('persist')
			->once()
			->with(Mockery::type(Domain::class));

		$this->entityManager->shouldReceive('persist')
			->twice()
			->with(Mockery::type(DomainLanguage::class));

		$this->entityManager->shouldReceive('flush')
			->once()
			->withNoArgs();

		$this->domainManager->synchronize();
	}


	public function testNothingToSynchronize()
	{
		$domain = Mockery::mock(Domain::class);

		$domainLanguageCs = Mockery::mock(DomainLanguage::class);
		$domainLanguageEn = Mockery::mock(DomainLanguage::class);

		$languageCs = Mockery::mock(Language::class);
		$languageEn = Mockery::mock(Language::class);

		$this->domainManager->addDomain('example1.com');
		$this->domainManager->addDomainLanguage('example1.com', 'cs');
		$this->domainManager->addDomainLanguage('example1.com', 'en');

		$domain->shouldReceive('getName')
			->once()
			->withNoArgs()
			->andReturn('example1.com');

		$domain->shouldReceive('getDomainLanguages')
			->once()
			->withNoArgs()
			->andReturn([$domainLanguageCs, $domainLanguageEn]);

		$domainLanguageCs->shouldReceive('getLanguage')
			->once()
			->withNoArgs()
			->andReturn($languageCs);

		$languageCs->shouldReceive('getIsoCode')
			->once()
			->withNoArgs()
			->andReturn('cs');

		$domainLanguageEn->shouldReceive('getLanguage')
			->once()
			->withNoArgs()
			->andReturn($languageEn);

		$languageEn->shouldReceive('getIsoCode')
			->once()
			->withNoArgs()
			->andReturn('en');

		$this->domainRepository->shouldReceive('findAll')
			->once()
			->withNoArgs()
			->andReturn([$domain]);

		$this->domainRepository->shouldReceive('findOneBy')
			->once()
			->with(['name' => 'example1.com'])
			->andReturn($domain);

		$this->entityManager->shouldReceive('flush')
			->once()
			->withNoArgs();

		$this->domainManager->synchronize();
	}

}
