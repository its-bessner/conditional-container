<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A command to trigger cache invalidation by regular expression from the command line.
 *
 * @author Christian Stocker <chregu@liip.ch>
 * @author David Buchmann <mail@davidbu.ch>
 */
#[AsCommand(name: 'fos:httpcache:invalidate:regex')]
class InvalidateRegexCommand extends BaseInvalidateCommand
{
    protected function configure(): void
    {
        $this
            ->setName('fos:httpcache:invalidate:regex')
            ->setDescription('Invalidate everything matching a regular expression on all configured caching proxies')
            ->addArgument(
                'regex',
                InputArgument::REQUIRED,
                'Regular expression for the paths to match.'
            )
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command invalidates all cached content matching a regular expression on the configured caching proxies.

Example:

    <info>php %command.full_name% "/some.*/path" </info>

or clear the whole cache

    <info>php %command.full_name% .</info>
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $regex = $input->getArgument('regex');

        $this->getCacheManager()->invalidateRegex($regex);

        return 0;
    }
}
