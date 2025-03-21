<?php

declare(strict_types=1);

/*
 * This file is part of the CMS-IG SEAL project.
 *
 * (c) Alexander Schranz <alexander@sulu.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CmsIg\Seal\Integration\Symfony\Command;

use CmsIg\Seal\EngineRegistry;
use CmsIg\Seal\Reindex\ReindexConfig;
use CmsIg\Seal\Reindex\ReindexProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @experimental
 */
#[AsCommand(name: 'cmsig:seal:reindex', description: 'Reindex configured search indexes.')]
final class ReindexCommand extends Command
{
    /**
     * @param iterable<ReindexProviderInterface> $reindexProviders
     */
    public function __construct(
        private readonly EngineRegistry $engineRegistry,
        private readonly iterable $reindexProviders,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('engine', null, InputOption::VALUE_REQUIRED, 'The name of the engine to create the schema for.');
        $this->addOption('index', null, InputOption::VALUE_REQUIRED, 'The name of the index to create the schema for.');
        $this->addOption('drop', null, InputOption::VALUE_NONE, 'Drop the index before reindexing.');
        $this->addOption('bulk-size', null, InputOption::VALUE_REQUIRED, 'The bulk size for reindexing, defaults to 100.', 100);
        $this->addOption('datetime-boundary', null, InputOption::VALUE_REQUIRED, 'Do a partial update and limit to only documents that have been changed since a given datetime object.');
        $this->addOption('identifiers', null, InputOption::VALUE_REQUIRED, 'Do a partial update and limit to only a comma-separated list of identifiers.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ui = new SymfonyStyle($input, $output);
        /** @var string|null $engineName */
        $engineName = $input->getOption('engine');
        /** @var string|null $indexName */
        $indexName = $input->getOption('index');
        /** @var bool $drop */
        $drop = $input->getOption('drop');
        /** @var int $bulkSize */
        $bulkSize = $input->getOption('bulk-size');
        /** @var \DateTimeImmutable|null $dateTimeBoundary */
        $dateTimeBoundary = $input->getOption('datetime-boundary') ? new \DateTimeImmutable((string) $input->getOption('datetime-boundary')) : null; // @phpstan-ignore-line
        /** @var array<string> $identifiers */
        $identifiers = \array_filter(\explode(',', (string) $input->getOption('identifiers'))); // @phpstan-ignore-line

        $reindexConfig = ReindexConfig::create()
            ->withIndex($indexName)
            ->withBulkSize($bulkSize)
            ->withDropIndex($drop)
            ->withDateTimeBoundary($dateTimeBoundary)
            ->withIdentifiers($identifiers);

        foreach ($this->engineRegistry->getEngines() as $name => $engine) {
            if ($engineName && $engineName !== $name) {
                continue;
            }

            $ui->section('Engine: ' . $name);

            $progressBar = $ui->createProgressBar();

            $engine->reindex(
                $this->reindexProviders,
                $reindexConfig,
                function (string $index, int $count, int|null $total) use ($progressBar) {
                    if (null !== $total) {
                        $progressBar->setMaxSteps($total);
                    }

                    $progressBar->setMessage($index);
                    $progressBar->setProgress($count);
                },
            );

            $progressBar->finish();

            $ui->writeln('');
            $ui->writeln('');
        }

        $ui->success('Search indexes reindexed.');

        return Command::SUCCESS;
    }
}
