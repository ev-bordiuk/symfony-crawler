<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command\Command;

class CrawlCommandTest extends KernelTestCase
{
  private $commandTest;

  protected function setUp(): void
  {
    $kernel = self::bootKernel();
    $application = new Application($kernel);
    $command = $application->find('crawl');
    $this->commandTester = new CommandTester($command);
  }

  public function testSuccessForCorrectUrl(): void
  {
      $this->commandTester->execute(['url' => 'https://google.com']);

      $this->commandTester->assertCommandIsSuccessful();
  }

  public function testInvalidForIncorrectUrl(): void
  {
    $this->commandTester->execute(['url' => 'https:google.com']);

    $this->assertEquals(Command::INVALID, $this->commandTester->getStatusCode());
  }
}
