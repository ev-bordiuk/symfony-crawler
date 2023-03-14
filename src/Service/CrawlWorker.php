<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use App\Entity\Result;

class CrawlWorker
{
  private $domain;
  private $cssSelector = 'body img';
  private $urlList = [];
  private $options = [];

  public function __construct(private EntityManagerInterface $entityManager)
  {
  }

  /**
   * 
   */
  public function perform($targetUrl, $options): void
  {
    $this->options = $options;
    $this->domain = parse_url($targetUrl, PHP_URL_HOST);
    $this->addTarget($targetUrl);
    $this->proceed();
  }

  private function proceed()
  {
    foreach ($this->getNextUrl() as $url) {
      $stopwatch = new Stopwatch();
      $stopwatch->start('crawling');
      $occuranciesCount = $this->handleData($url);
      $event = $stopwatch->stop('crawling');

      $this->saveResults($url, $occuranciesCount, $event);
    }
  }

  /**
   * 
   */
  private function addTarget($url):void
  {
    $this->urlList[] = $url;
  }

  /**
   * 
   */
  private function getNextUrl()
  {
    while ($url = current($this->urlList)) {
      next($this->urlList);
      yield $url;
    }
  }

  /**
   * 
   */
  private function handleData($targetUrl): int
  {
    $crawler = new Crawler($this->getHtml($targetUrl), null, $targetUrl);
    $occuranciesCount = 0;

    foreach ($crawler->filter($this->cssSelector)->images() as $image) {
      if ($this->hostedByDomain($image)) {
        $occuranciesCount++;
      }
    }

    return $occuranciesCount;
  }

  /**
   * 
   */
  private function saveResults($targetUrl, $occuranciesCount, $event)
  {
    $result = new Result();
    $result->setUrl($targetUrl);
    $result->setImagesTotal($occuranciesCount);
    $result->setTimeSpent($event->getDuration()); // miliseconds
    $this->entityManager->persist($result);
    $this->entityManager->flush();
  }

  /**
   * Visit url and get html content
   */
  private function getHtml($targetUrl): string
  {
    return file_get_contents($targetUrl);
  }

  /**
   * Check if image hosts on the same domain
   * We do not check relative pathes, because they all become absolute
   * 
   * @param Symfony\Component\DomCrawler\Image $image
   * 
   * @return bool
   */
  protected function hostedByDomain($image): bool
  {
    return strstr($image->getUri(), $this->domain);
  }
}
