<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Result;

class CrawlWorker
{
    private const INVALID_EXTENSIONS = '/\.js|\.ts|\.gz|\.xml|\.pdf|#/';

    private $domain;
    private $cssSelector = 'body img';
    private $processedUrls = [];
    private $requestOptions = [];
    private $pageMax;
    private $deep;

    /**
     * @inheritdoc
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient
    ) {
    }

    /**
     * Start crawling
     */
    public function perform(string $targetUrl, array $options = []): void
    {
        $this->pageMax = $options['pages'] ?? null;
        $this->domain = parse_url($targetUrl, PHP_URL_HOST);

        if (array_key_exists('timeout', $options)) {
            $this->requestOptions['timeout'] = $options['timeout'];
        }

        if (array_key_exists('deep', $options)) {
            $this->deep = $options['deep'];
        }
        
        $this->dispatchUrl($targetUrl, $this->deep);
    }

    /**
     * Dispatch chain of actions - handle single page and start walking through child links
     */
    private function dispatchUrl(string $url, mixed $deep = null)
    {
        $crawler = $this->crawlUrl($url);

        if ($this->pagesMaxExceeded()) {
            return;
        }

        if ($this->deep === null) {
            return $this->proceedDeeper($crawler);
        }

        if ($deep <= 0) {
            return;
        }

        return $this->proceedDeeper($crawler, $deep - 1);
    }

    /**
     * Handle single page content
     */
    private function crawlUrl(string $url): Crawler
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('crawling');
        $crawler = $this->buildCrawler($url);
        $occuranciesCount = $this->handlePage($crawler, $url);
        $event = $stopwatch->stop('crawling');
        $this->saveResult($url, $occuranciesCount, $event);

        return $crawler;
    }

    /**
     * Makes page's calculations
     */
    private function handlePage(Crawler $crawler, string $url): int
    {
        $occuranciesCount = 0;

        foreach ($crawler->filter($this->cssSelector)->images() as $image) {
            if ($this->belongsToDomain($image->getUri())) {
                $occuranciesCount++;
            }
        }
        
        $this->processedUrls[] = $url;

        return $occuranciesCount;
    }

    /**
     * Follow for every child link
     */
    private function proceedDeeper(Crawler $crawler, mixed $deep = null): void
    {
        foreach ($this->fetchInternalLinks($crawler) as $link) {
            $this->dispatchUrl($link, $deep);
        }
    }

    /**
     * Fetch related link or break execution
     */
    private function fetchInternalLinks(Crawler $crawler): \Generator
    {
        $selector = 'body a';

        foreach ($crawler->filter($selector)->links() as $link) {
            if ($this->pagesMaxExceeded()) {
                break;    
            }

            $url = $link->getUri();
            
            if ($this->skipLink($url)) {
                continue;
            }

            yield $url;
        }
    }

    /**
     * Check if pages maximum exceeded
     */
    private function pagesMaxExceeded(): bool
    {
        if ($this->pageMax !== null) {
            return $this->pageMax <= count($this->processedUrls);
        }

        return false;
    }

    /**
     * Save Result record
     */
    private function saveResult(
        string $url,
        int $occuranciesCount,
        \Symfony\Component\Stopwatch\StopwatchEvent $event
    ): void {
        $result = new Result();
        $result->setUrl($url);
        $result->setImagesTotal($occuranciesCount);
        $result->setTimeSpent($event->getDuration()); // miliseconds
        $this->entityManager->persist($result);
        $this->entityManager->flush();
    }

    /**
     * Visit url and get html content
     */
    private function getHtml(string $url, array $options = []): string
    {
        $options = array_merge($this->requestOptions, $options);
        $response = $this->httpClient->request('GET', $url, $options);

        if ($response->getStatusCode() === 200) {
            return $response->getContent();
        }

        return '';
    }

    /**
     * Check if links are valid and leads to the same domain
     */
    private function belongsToDomain(string $url): bool
    {
        return preg_match("/(\/\/|\.)$this->domain/", $url);
    }

    /**
     * Build Crawler instance
     */
    private function buildCrawler(string $url): Crawler
    {
        return new Crawler($this->getHtml($url), null, $url);
    }

    /**
     * Verify if url is already parsed
     */
    private function parsed(string $url): bool
    {
        $url = rtrim($url, '/');

        return in_array($url, $this->processedUrls) ||
               in_array($url . '/', $this->processedUrls);
    }

    private function skipLink(string $url)
    {
        return $this->parsed($url)
               || !preg_match("/(\/\/)$this->domain/", $url)
               || preg_match(CrawlWorker::INVALID_EXTENSIONS, $url);
    }
}
