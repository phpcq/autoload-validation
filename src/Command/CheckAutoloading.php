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

use Composer\Autoload\ClassLoader;
use PhpCodeQuality\AutoloadValidation\AllLoadingAutoLoader;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator;
use PhpCodeQuality\AutoloadValidation\AutoloadValidator\AutoloadValidatorFactory;
use PhpCodeQuality\AutoloadValidation\ClassLoader\EnumeratingClassLoader;
use PhpCodeQuality\AutoloadValidation\ClassMapGenerator;
use PhpCodeQuality\AutoloadValidation\Hacks\HackPreparator;
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
     * The exit code map.
     *
     * @var array
     */
    private static $exitCodes = array(
        false => 1,
        true => 0
    );

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('phpcq:check-autoloading')
            ->setDescription(<<<EOF
Check that the composer.json auto loading keys are correct.
This command has support for:
- psr-0 scanning
- psr-4 scanning
- classmap scanning

Note that by default the legacy hacks for Contao auto loader is still on, if you do not want this pass the
<comment>--disable-legacy-hacks (-d)</comment> option. This behaviour will get removed in Version 2.0.

However, a warning will get shown if the Contao auto loader is used without forcing it to be so.
EOF
            )
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
            )
            ->addOption(
                'add-autoloader',
                null,
                (InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY),
                'Path to an auto loader PHP include file to probe when testing class loading.' . "\r\n" .
                'Files passed will get included at the beginning.'  . "\r\n" .
                'This allows to support cumbersome third party auto loaders.'
            )
            ->addOption(
                'disable-legacy-hacks',
                'd',
                InputOption::VALUE_NONE,
                'Path this to disable the now deprecated auto loader hacks of Version 1.0 to probe for Contao classes.'
            );
    }

    /**
     * Execute the tests.
     *
     * @param InputInterface  $input  An InputInterface instance.
     * @param OutputInterface $output An OutputInterface instance.
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($root = $input->getArgument('root-dir')) {
            chdir(realpath($root));
        }

        $rootDir = realpath(getcwd());
        $logger  = new ConsoleLogger($output);

        $composerJson = $rootDir . '/composer.json';
        if (!file_exists($composerJson)) {
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

        $enumLoader = new EnumeratingClassLoader();
        $this->prepareLoader($enumLoader, $test);

        $hacker = new HackPreparator($enumLoader, $logger);
        if ($custom = $input->getOption('add-autoloader')) {
            $hacker->prepareHacks(
                $custom,
                array(
                    $rootDir,
                    dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'hacks'
                )
            );
        }
        $this->prepareComposerFallbackLoader($enumLoader, $rootDir, $composer);
        if (!($input->getOption('disable-legacy-hacks') || $input->getOption('add-autoloader'))) {
            $hacker->prepareLegacyHack();
        }

        $loadCycle = new AllLoadingAutoLoader($enumLoader, $test->getClassMap(), $logger);

        return static::$exitCodes[$loadCycle->run() && !$report->hasError()];
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

    /**
     * Create a class loader that contains the classes found by us and the classes from the real composer installation.
     *
     * @param EnumeratingClassLoader $enumLoader        The enum loader to add to.
     *
     * @param AutoloadValidator      $autoloadValidator The auto loader validator.
     *
     * @return void
     */
    private function prepareLoader(EnumeratingClassLoader $enumLoader, AutoloadValidator $autoloadValidator)
    {
        $loaders = $autoloadValidator->getLoaders();
        foreach ($loaders as $name => $loader) {
            $enumLoader->add($loader, $name);
        }
    }

    /**
     * Prepare the composer fallback loader.
     *
     * @param EnumeratingClassLoader $enumLoader The enum loader to add to.
     *
     * @param string                 $baseDir    The base dir where the composer.json resides.
     *
     * @param array                  $composer   The contents of the composer.json.
     *
     * @return void
     */
    private function prepareComposerFallbackLoader(EnumeratingClassLoader $enumLoader, $baseDir, $composer)
    {
        $vendorDir = $baseDir . DIRECTORY_SEPARATOR . 'vendor';
        if (isset($composer['extra']['vendor-dir'])) {
            $vendorDir = $baseDir . DIRECTORY_SEPARATOR . $composer['extra']['vendor-dir'];
        }

        if (!is_dir($vendorDir)) {
            return;
        }
        $loader = new ClassLoader();

        if ($map = $this->includeIfExists($vendorDir . '/composer/autoload_namespaces.php')) {
            foreach ($map as $namespace => $path) {
                $loader->set($namespace, $path);
            }
        }

        if ($map = $this->includeIfExists($vendorDir . '/composer/autoload_psr4.php')) {
            foreach ($map as $namespace => $path) {
                $loader->setPsr4($namespace, $path);
            }
        }
        if ($classMap = $this->includeIfExists($vendorDir . '/composer/autoload_classmap.php')) {
            $loader->addClassMap($classMap);
        }

        $enumLoader->add(array($loader, 'loadClass'), 'composer.fallback');
    }

    /**
     * Include the given file if it exists and return the result.
     *
     * @param string $file The file name.
     *
     * @return bool|array
     */
    private function includeIfExists($file)
    {
        return file_exists($file) ? include $file : false;
    }
}
