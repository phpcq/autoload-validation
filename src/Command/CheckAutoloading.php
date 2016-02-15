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
                'disable-legacy-hacks',
                'd',
                InputOption::VALUE_NONE,
                'Path this to disable the now deprecated auto loader hacks of Version 1.0 to probe for Contao classes.'
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

        $enumLoader = new EnumeratingClassLoader();
        $this->prepareLoader($enumLoader, $test);
        $this->prepareComposerFallbackLoader($enumLoader, $rootDir, $composer);
        if (!$input->getOption('disable-legacy-hacks')) {
            $this->prepareLegacyHacks($enumLoader, $logger);
        }

        $loadCycle = new AllLoadingAutoLoader($enumLoader, $test->getClassMap(), $logger);

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
     * Create a class loader that contains the legacy hack and add it to the enum loader.
     *
     * @param EnumeratingClassLoader $enumLoader The enum loader to add to.
     *
     * @param LoggerInterface        $logger     The logger to pass warnings to.
     *
     * @return void
     */
    private function prepareLegacyHacks(EnumeratingClassLoader $enumLoader, LoggerInterface $logger)
    {
        // Add Contao hack.
        $enumLoader->add(function ($class) use ($logger) {
            if (substr($class, 0, 7) !== 'Contao\\') {
                spl_autoload_call('Contao\\' . $class);
                if (class_exists('Contao\\' . $class, false) && !class_exists($class, false)) {
                    class_alias('Contao\\' . $class, $class);

                    $logger->warning(
                        'Loaded class {class} as {alias} from deprecated Contao hack. ' .
                        'Please specify a custom loader hack if you want to keep this class loaded.',
                        array('class' => 'Contao\\' . $class, 'alias' => $class)
                    );

                    return true;
                }
            }

            return null;
        }, 'contao.hack');
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
