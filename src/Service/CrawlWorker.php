<?php

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Result;
use App\Service\CrawlWorker\LinkManager;

class CrawlWorker
{
    private const INVALID_EXTENSIONS = '/\.js|\.ts|\.gz|\.xml|\.pdf|#/';

    private $domain;
    private $cssSelector = 'body img';
    private $timeout;
    private $pageLimit;
    private $depth;

    /**
     * @inheritdoc
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient,
        private LinkManager $linkManager
    ) {
    }

    /**
     * Start crawling
     */
    public function perform(string $entryUrl, array $options = []): void
    {
        $this->pageLimit = $options['limit'] ?? null;
        $this->domain = parse_url($entryUrl, PHP_URL_HOST);

        if (array_key_exists('timeout', $options)) {
            $this->timeout = $options['timeout'];
        }

        if (array_key_exists('depth', $options)) {
            $this->depth = $options['depth'];
        }
        
        $this->linkManager->append($entryUrl, 0);
        $this->start();
    }

    /**
     * Begin all crawlings
     */
    private function start(): void
    {
        foreach ($this->linkManager->next() as $link) {
            if ($this->pagesLimitExceeded()) {
                break;
            }

            if ($this->linkManager->processed($link->url)) {
                continue;
            }

            $crawler = $this->crawlUrl($link->url);

            if ($this->depth === null || $this->depth > $link->depth) {
                $this->appendInternalLinks($crawler, $link->depth + 1);
            }
        }
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

        $this->linkManager->addProcessed($url);

        return $occuranciesCount;
    }

    /**
     * Append child links to queue
     */
    private function appendInternalLinks(Crawler $crawler, int $nextDepth): void
    {
        $selector = 'body a';

        foreach ($crawler->filter($selector)->links() as $link) {
            $url = $link->getUri();
            
            if ($this->skipLink($url)) {
                continue;
            }

            $this->linkManager->append($url, $nextDepth);
        }
    }

    /**
     * Check if pages maximum exceeded
     */
    private function pagesLimitExceeded(): bool
    {
        if ($this->pageLimit !== null) {
            return $this->pageLimit <= $this->linkManager->processedSize();
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
        if ($this->timeout !== null) {
            $options = array_merge($options, ['timeout' => $this->timeout]);
        }
        
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
     * Check if link already scrapped or is external
     */
    private function skipLink(string $url): bool
    {
        return $this->linkManager->processed($url)
               || !preg_match("/(\/\/)$this->domain/", $url)
               || preg_match(CrawlWorker::INVALID_EXTENSIONS, $url);
    }
}
