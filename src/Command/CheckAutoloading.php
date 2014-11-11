<?php

/**
 * This file is part of the Contao Community Alliance Build System tools.
 *
 * @copyright 2014 Contao Community Alliance <https://c-c-a.org>
 * @author    Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @package   contao-community-alliance/build-system-tool-autoloading-validation
 * @license   MIT
 * @link      https://c-c-a.org
 */
namespace ContaoCommunityAlliance\BuildSystem\Tool\AutoloadingValidation\Command;

use Composer\Autoload\ClassLoader;
use ContaoCommunityAlliance\BuildSystem\Tool\AutoloadingValidation\ClassMapGenerator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class to check the auto loading information from a composer.json.
 *
 * @package ContaoCommunityAlliance\BuildSystem\Tool\AutoloadingValidation\Command
 */
class CheckAutoloading extends Command
{
    /**
     * The current input interface.
     *
     * @var InputInterface
     */
    protected $input;

    /**
     * The current output interface.
     *
     * @var OutputInterface
     */
    protected $output;

    /**
     * The overall class map.
     *
     * @var string[]
     */
    protected $classMap;

    /**
     * The class loader to which the classes shall get registered.
     *
     * @var ClassLoader
     */
    protected $loader;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('ccabs:tools:check-autoloading')
            ->setDescription('Check that the composer.json autoloading keys are correct.')
            ->addArgument(
                'root-dir',
                InputArgument::OPTIONAL,
                'The directory where the composer.json is located at.',
                '.'
            );
    }

    /**
     * Cut the file extension from the filename and return the result.
     *
     * @param string $file The file name.
     *
     * @return string
     */
    protected function cutExtensionFromFileName($file)
    {
        return preg_replace('/\\.[^.\\s]{2,3}$/', '', $file);
    }

    /**
     * Cut the file extension from the filename and return the result.
     *
     * @param string $file The file name.
     *
     * @return string
     */
    protected function getExtensionFromFileName($file)
    {
        return preg_replace('/^.*(\\.[^.\\s]{2,3})$/', '$1', $file);
    }

    /**
     * Get the namespace name from the full class name.
     *
     * @param string $class The full class name.
     *
     * @return string
     */
    protected function getNameSpaceFromClassName($class)
    {
        $chunks = explode('\\', $class);
        array_pop($chunks);

        return implode('\\', $chunks);
    }

    /**
     * Get the class name from the full class name.
     *
     * @param string $class The full class name.
     *
     * @return string
     */
    protected function getClassFromClassName($class)
    {
        $chunks = explode('\\', $class);

        return array_pop($chunks);
    }

    /**
     * Create a class map.
     *
     * @param string      $subPath   The path.
     *
     * @param string|null $namespace The namespace prefix (optional).
     *
     * @return array
     */
    protected function createClassMap($subPath, $namespace = null)
    {
        $classMap = ClassMapGenerator::createMap($subPath, null, $namespace);

        if (array_intersect($this->classMap, $classMap)) {
            $this->output->writeln(
                sprintf(
                    '<info>The class(es) %s are available via multiple autoloader values.</info>',
                    implode(', ', array_intersect($this->classMap, $classMap))
                )
            );
        }

        $this->classMap = array_merge($this->classMap, $classMap);

        return $classMap;
    }

    /**
     * Check that the auto loading information is correct.
     *
     * @param array  $classMap  The autoload class map.
     *
     * @param string $subPath   The base directory.
     *
     * @param string $namespace The namespace prefix defined for psr-4.
     *
     * @return bool
     */
    public function validateComposerAutoLoadingPsr0ClassMap($classMap, $subPath, $namespace)
    {
        $result = true;
        $nsLen  = strlen($namespace);
        foreach ($classMap as $class => $file) {
            if ($class[0] == '\\') {
                $class = substr($class, 1);
            }
            $classNs = $this->getNameSpaceFromClassName($class);
            $classNm = $this->getClassFromClassName($class);

            if (substr($classNs, 0, $nsLen) !== $namespace) {
                $result = false;

                $this->output->writeln(
                    sprintf(
                        '<error>Class "%s" namespace "%s" does not match expected psr-0 namespace prefix "%s" for ' .
                        'directory "%s"!</error>',
                        $class,
                        $this->getNameSpaceFromClassName($class),
                        $namespace,
                        $subPath
                    )
                );
                continue;
            }

            if ($class === $namespace) {
                $result = false;
                $this->output->writeln(
                    sprintf(
                        '<error>Class "%s" is used as psr-0 namespace prefix "%s" for directory "%s"!</error>',
                        $class,
                        $namespace,
                        $subPath
                    )
                );
                continue;
            }

            $fileNameShould = str_replace(
                '//',
                '/',
                $subPath . '/' . str_replace(
                    '\\',
                    '/',
                    $classNs . '/' . $classNm
                )
            );
            if ($fileNameShould !== $this->cutExtensionFromFileName($file)) {
                $result = false;
                $this->output->writeln(
                    sprintf(
                        '<error>Class "%s" found in file "%s" should reside in file "%s" (psr-0 prefix "%s")</error>',
                        $class,
                        $file,
                        $fileNameShould . $this->getExtensionFromFileName($file),
                        $namespace
                    )
                );
            }
        }

        return $result;
    }

    /**
     * Check that the auto loading information is correct.
     *
     * @param array  $information The autoload information.
     *
     * @param string $baseDir     The base directory.
     *
     * @return bool
     */
    public function validateComposerAutoLoadingPsr0($information, $baseDir)
    {
        $result = true;

        // Scan all directories mentioned and validate the class map against the entries.
        foreach ($information as $namespace => $path) {
            $subPath  = str_replace('//', '/', $baseDir . '/' . $path);
            $classMap = $this->createClassMap($subPath, $namespace);
            $this->loader->add($namespace, $subPath);

            if (!$this->validateComposerAutoLoadingPsr0ClassMap(
                $classMap,
                $subPath,
                $namespace
            )) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Check that the auto loading information is correct.
     *
     * @param array  $classMap  The autoload class map.
     *
     * @param string $subPath   The base directory.
     *
     * @param string $namespace The namespace prefix defined for psr-4.
     *
     * @return bool
     */
    public function validateComposerAutoLoadingPsr4ClassMap($classMap, $subPath, $namespace)
    {
        $result = true;
        if (substr($namespace, -1) == '\\') {
            $namespace = substr($namespace, 0, -1);
        }
        $nsLen = strlen($namespace);
        foreach ($classMap as $class => $file) {
            if ($class[0] == '\\') {
                $class = substr($class, 1);
            }

            if (substr($class, 0, $nsLen) !== $namespace) {
                $result = false;
                $this->output->writeln(
                    sprintf(
                        '<error>Class "%s" namespace "%s" does not match expected psr-4 namespace prefix "%s"!</error>',
                        $class,
                        $this->getNameSpaceFromClassName($class),
                        $namespace,
                        $file
                    )
                );
                continue;
            }

            if ($class === $namespace) {
                $result = false;
                $this->output->writeln(
                    sprintf(
                        '<error>Class "%s" is used as psr-4 namespace prefix "%s" for directory "%s"!</error>',
                        $class,
                        $namespace,
                        $subPath
                    )
                );
                continue;
            }

            $fileNameShould = str_replace(
                '//',
                '/',
                $subPath . '/' . str_replace(
                    '\\',
                    '/',
                    substr(
                        $class,
                        ($nsLen + 1)
                    )
                )
            );
            if ($fileNameShould !== $this->cutExtensionFromFileName($file)) {
                $result = false;
                $this->output->writeln(
                    sprintf(
                        '<error>Class "%s" found in file "%s" should reside in file "%s" (psr-4 prefix "%s")</error>',
                        $class,
                        $file,
                        $fileNameShould . $this->getExtensionFromFileName($file),
                        $namespace
                    )
                );
            }
        }

        return $result;
    }

    /**
     * Check that the auto loading information is correct.
     *
     * @param array    $information The autoload information.
     *
     * @param string   $baseDir     The base directory.
     *
     * @param string[] $messages    The error messages.
     *
     * @return bool
     */
    public function validateComposerAutoLoadingPsr4($information, $baseDir, &$messages)
    {
        $result = true;
        // Scan all directories mentioned and validate the class map against the entries.
        foreach ($information as $namespace => $path) {
            $subPath  = str_replace('//', '/', $baseDir . '/' . $path);
            $classMap = $this->createClassMap($subPath, $namespace);
            $this->loader->addPsr4($namespace, $subPath);

            if (!$this->validateComposerAutoLoadingPsr4ClassMap(
                $classMap,
                $subPath,
                $namespace,
                $messages
            )) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Check that the auto loading information is correct.
     *
     * @param array  $classMap The autoload class map.
     *
     * @param string $subPath  The base directory.
     *
     * @return bool
     */
    public function validateComposerAutoLoadingClassMapClassMap($classMap, $subPath)
    {
        $result = true;
        if (empty($classMap)) {
            $result = false;
            $this->output->writeln(sprintf('<error>No classes found in classmap prefix "%s")</error>', $subPath));
        }

        return $result;
    }

    /**
     * Check that the auto loading information is correct.
     *
     * @param array  $information The autoload information.
     *
     * @param string $baseDir     The base directory.
     *
     * @return bool
     */
    public function validateComposerAutoLoadingClassMap($information, $baseDir)
    {
        $result = true;
        // Scan all directories mentioned and validate the class map against the entries.
        foreach ($information as $path) {
            $subPath  = str_replace('//', '/', $baseDir . '/' . $path);
            $classMap = $this->createClassMap($subPath);
            $this->loader->addClassMap($classMap);

            $this->classMap = array_merge($this->classMap, $classMap);
            if (!$this->validateComposerAutoLoadingClassMapClassMap(
                $classMap,
                $subPath
            )) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Check that the auto loading information is correct.
     *
     * @param array           $information The autoload information.
     *
     * @param string          $baseDir     The base directory.
     *
     * @param OutputInterface $output      The output to use for error messages.
     *
     * @return bool
     *
     * @throws \RuntimeException When an unknown auto loader type is encountered.
     */
    public function validateComposerAutoLoading($information, $baseDir, OutputInterface $output)
    {
        $result   = true;
        $messages = array();
        foreach ($information as $type => $content) {
            switch ($type) {
                case 'psr-0':
                    $result = $this->validateComposerAutoLoadingPsr0($content, $baseDir, $messages) && $result;
                    break;
                case 'psr-4':
                    $result = $this->validateComposerAutoLoadingPsr4($content, $baseDir, $messages) && $result;
                    break;
                case 'classmap':
                    $result = $this->validateComposerAutoLoadingClassMap($content, $baseDir, $messages) && $result;
                    break;
                default:
                    throw new \RuntimeException('Unknown auto loader type ' . $type . ' encountered!');
            }
        }

        foreach ($messages as $message) {
            $output->writeln($message);
        }

        return $result;
    }

    /**
     * Ensure that a composer autoload section is present.
     *
     * @param array $composer The composer json contents.
     *
     * @return bool
     */
    public function hasAutoloadSection($composer)
    {
        if (!(isset($composer['autoload']) && isset($composer['autoload-dev']))) {
            if (OutputInterface::VERBOSITY_VERBOSE <= $this->output->getVerbosity()) {
                $this->output->writeln('<info>No autoload information found, skipping test.</info>');
            }

            return false;
        }

        return true;
    }

    /**
     * Check if the given interface/class/trait is already loaded.
     *
     * @param string $namespacedName The full name of the class/interface/trait.
     *
     * @return bool
     */
    protected function isLoaded($namespacedName)
    {
        return (class_exists($namespacedName, false)
            || interface_exists($namespacedName, false)
            || (function_exists('trait_exists') && trait_exists($namespacedName, false)));
    }

    /**
     * Check that the auto loading information is correct.
     *
     * @return bool
     */
    public function tryLoadAllClasses()
    {
        $result = true;
        // Try hacking via Contao autoloader.
        spl_autoload_register(function ($class) {
            if (substr($class, 0, 7) !== 'Contao\\') {
                spl_autoload_call('Contao\\' . $class);

                if (class_exists('Contao\\' . $class, false) && !class_exists($class, false)) {
                    class_alias('Contao\\' . $class, $class);
                }
            }
        });

        // Now try to autoload all classes.
        foreach ($this->classMap as $class => $file) {
            if (!$this->isLoaded($class)) {
                try {
                    if (!$this->loader->loadClass($class)) {
                        $this->output->writeln(
                            sprintf(
                                '<error>The autoloader could not load %s (should be found from file %s).</error>',
                                $class,
                                $file
                            )
                        );
                        $result = false;
                    }
                } catch (\ErrorException $exception) {
                    $this->output->writeln(
                        sprintf(
                            '<error>ERROR loading class %s: "%s".</error>',
                            $class,
                            $exception->getMessage()
                        )
                    );
                    $result = false;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input    = $input;
        $this->output   = $output;
        $this->classMap = array();
        $this->loader   = new ClassLoader();

        $rootDir  = realpath($input->getArgument('root-dir'));
        $composer = json_decode(file_get_contents($rootDir . '/composer.json'), true);

        if (!$this->hasAutoloadSection($composer)) {
            return 0;
        }

        if (isset($composer['autoload'])) {
            if (!$this->validateComposerAutoLoading($composer['autoload'], $rootDir, $output)) {
                $output->writeln('<error>The autoload information in composer.json is incorrect!</error>');
                return 1;
            }
        }

        if (isset($composer['autoload-dev'])) {
            if (!$this->validateComposerAutoLoading($composer['autoload-dev'], $rootDir, $output)) {
                $output->writeln('<error>The autoload-dev information in composer.json is incorrect!</error>');
                return 1;
            }
        }

        if (!$this->tryLoadAllClasses()) {
            $output->writeln('<error>The autoloading of all classes failed!</error>');

            return 1;
        }

        if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
            $output->writeln('<info>The autoloader information in composer.json is correct.</info>');
        }

        return 0;
    }
}
