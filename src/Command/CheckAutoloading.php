<?php

/**
 * This file is part of phpcq/autoload-validation.
 *
 * (c) 2014 Christian Schiffler, Tristan Lins
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    phpcq/autoload-validation
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Tristan Lins <tristan@lins.io>
 * @copyright  2014-2016 Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @license    https://github.com/phpcq/autoload-validation/blob/master/LICENSE MIT
 * @link       https://github.com/phpcq/autoload-validation
 * @filesource
 */

namespace PhpCodeQuality\AutoloadValidation\Command;

use PhpCodeQuality\AutoloadValidation\AllLoadingAutoLoader;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AutoloadValidatorFactory;
use PhpCodeQuality\AutoloadValidation\ClassMapGenerator;
use PhpCodeQuality\AutoloadValidation\Report\Destination\DestinationInterface;
use PhpCodeQuality\AutoloadValidation\Report\Destination\PsrLogDestination;
use PhpCodeQuality\AutoloadValidation\Report\Report;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class to check the auto loading information from a composer.json.
 *
 * @package PhpCodeQuality\AutoloadValidation\Command
 */
class CheckAutoloading extends Command
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('phpcq:check-autoloading')
            ->setDescription('Check that the composer.json autoloading keys are correct.')
            ->addArgument(
                'root-dir',
                InputArgument::OPTIONAL,
                'The directory where the composer.json is located at.',
                '.'
            )
            ->addOption(
                'strict',
                's',
                InputOption::VALUE_NONE,
                'Perform strict validations. This converts discovered warnings to errors'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDir = realpath($input->getArgument('root-dir')) ?: getcwd();
        $logger  = new ConsoleLogger($output);

        if (!file_exists($composerJson = $rootDir . '/composer.json')) {
            $logger->error(
                '<error>File not found, can not analyze: {file}</error> ',
                array('file' => $composerJson)
            );
            return 1;
        }

        $destinations   = array();
        $destinations[] = new PsrLogDestination($logger);

        $report   = $this->prepareReport($input, $logger);
        $composer = json_decode(file_get_contents($composerJson), true);
        $factory  = new AutoloadValidatorFactory($rootDir, new ClassMapGenerator(), $report);
        $test     = new AutoloadValidator($factory->createFromComposerJson($composer), $report);
        $test->validate();
        if ($report->hasError()) {
            $logger->error('<error>Testing loaders found errors</error> ');
        }

        $loadCycle = new AllLoadingAutoLoader($test->getLoader(), $test->getClassMap(), $logger);

        return $loadCycle->run() ? 0 : 1;
    }

    /**
     * Prepare the report.
     *
     * @param InputInterface  $input  The input to read options from.
     *
     * @param LoggerInterface $logger The logger to use.
     *
     * @return Report
     */
    private function prepareReport(InputInterface $input, LoggerInterface $logger)
    {
        $reportMap = array();
        if ($input->getOption('strict')) {
            $reportMap = array(
                DestinationInterface::SEVERITY_ERROR   => DestinationInterface::SEVERITY_ERROR,
                DestinationInterface::SEVERITY_WARNING => DestinationInterface::SEVERITY_ERROR,
                DestinationInterface::SEVERITY_INFO    => DestinationInterface::SEVERITY_ERROR,
            );
        }

        $destinations   = array();
        $destinations[] = new PsrLogDestination($logger);

        $report = new Report($destinations, $reportMap);

        return $report;
    }
}
