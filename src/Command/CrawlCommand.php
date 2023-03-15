<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Service\CrawlWorker;

#[AsCommand(
    name: 'crawl',
    description: 'Walks through page and calculates all <img> tags',
)]
class CrawlCommand extends Command
{
    protected const VALID_URL_PATTERN = "/^https?:\\/\\/(?:www\\.)?[-a-zA-Z0-9@:%._\\+~#=]{1,256}\\.[a-zA-Z0-9()]{1,6}\\b(?:[-a-zA-Z0-9()@:%_\\+.~#?&\\/=]*)$/";

    protected $targetUrl;
    protected $options = [];
    protected $io;

    /**
     * @inheritdoc
     */
    public function __construct(protected CrawlWorker $crawlWorker, protected RouterInterface $router)
    {
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->targetUrl = $this->extractUrl($input->getArgument('url'));
        $this->options   = array_filter($input->getOptions(), fn($option) => $option != null);
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->addArgument('url', InputArgument::REQUIRED, 'Specify target url')
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, 'Specify max number of seconds for script execution')
            ->addOption('deep', 'd', InputOption::VALUE_OPTIONAL, 'Specify how deep links should be used')
            ->addOption('pages', 'p', InputOption::VALUE_OPTIONAL, 'Max number of parsed pages');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->targetUrl == null) {
            $this->io->error('Please, provide correct url address.');

            return Command::INVALID;
        }

        try {
            $this->crawlWorker->perform($this->targetUrl, $this->options);
            
            $this->io->success(
                sprintf(
                    'Task completed, please proceed to %s to see results',
                    $this->router->generate('app_results', [], UrlGeneratorInterface::ABSOLUTE_URL)
                )
            );

            return Command::SUCCESS;
        } catch (CrawlException | \Exception $e) {
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Extract url from string
     */
    protected function extractUrl(string $url): string|null
    {
        preg_match_all(CrawlCommand::VALID_URL_PATTERN, trim($url), $matches);

        return count($matches[0]) ? $matches[0][0] : null;
    }
}
