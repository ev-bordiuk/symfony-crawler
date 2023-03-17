<?php

namespace App\Service\CrawlWorker;

class LinkManager
{
    public function __construct(private $queue = [], private $processedUrls = [])
    {
    }

    /**
     * Add link to queue
     */
    public function append(string $url, int $depth)
    {
        $this->queue[] = new Link($url, 0);
    }

    /**
     * Fetch next url to proceed
     */
     public function next(): \Generator
    {
        while ($entry = $this->nextEntry()) {
            yield $entry;
        }
    }

    /**
     * Add link to processed list
     */
    public function addProcessed(string $url): void
    {
        $this->processedUrls[] = $url;
    }

    /**
     * Processed list size
     */
    public function processedSize(): int
    {
        return count($this->processedUrls);
    }

    /**
     * Verify if link already processed
     */
    public function processed(string $url): bool
    {
        $url = rtrim($url, '/');

        return in_array($url, $this->processedUrls) ||
               in_array($url . '/', $this->processedUrls);
    }

    /**
     * Extract first link in queue
     */
    private function nextEntry(): Link|null
    {
        return array_shift($this->queue);
    }
}
