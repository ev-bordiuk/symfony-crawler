<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;

#[AsCommand(
    name: 'crawl',
    description: 'Walks through page and calculates all <img> tags',
)]
class CrawlCommand extends Command
{
  protected const VALID_URL_PATTERN = "/^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$/";

  protected $targetUrl = null;
  protected $domain    = null;


  protected function configure(): void
  {
    $this->addArgument('url', InputArgument::REQUIRED, 'Specify target url');
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    $this->setInputVariables($input);
    
    if (!$this->targetUrl) {
      $io->error('Please, provide correct url address.');
      return Command::INVALID;
    }
    
    try {
      $this->perform();
    
      $io->success(sprintf('Task completed, please proceed to %s to see results'), );

      return Command::SUCCESS;
    } catch (\Exception $e) {

      $io->error($e->getMessage());
      return Command::FAILURE;
    }
  }

  protected function setInputVariables($input)
  {
    $this->targetUrl = $this->extractUrl($input->getArgument('url'));
    $this->domain    = parse_url($this->targetUrl, PHP_URL_HOST);
  }

  /**
   * 
   */
  protected function perform()
  {
    $html = file_get_contents($this->targetUrl);

    $crawler = new Crawler($html, null, $this->targetUrl);
    $occuranciesCount = 0;

    foreach ($crawler->filter('body img')->images() as $image) {
      if ($this->belongsToDomain($image)) {
        $occuranciesCount++;
      }
    }

    print_r($occuranciesCount);
    // $crawler
    //   ->filter('body img')
    //   ->reduce(function (Crawler $node, $i) {
    //     // filters every other node
    //     print_r($node->nodeName);
    // });
  }

  /**
   * Extract url from string
   * 
   * @param string
   * 
   * @return string|null
   */
  protected function extractUrl($url): string|null
  {
    preg_match_all(CrawlCommand::VALID_URL_PATTERN, trim($url), $matches);

    return count($matches[0]) ? $matches[0][0] : null;
  }

  /**
   * Check if image hosts on the same domain
   * We do not check relative pathes, because they all become absolute
   * 
   * @param Symfony\Component\DomCrawler\Image $image
   * 
   * @return bool
   */
  protected function belongsToDomain($image): bool
  {
    return strstr($image->getUri(), $this->domain);
  }
}
