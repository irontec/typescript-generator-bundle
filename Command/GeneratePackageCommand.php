<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\Command;

use \PHLAK\SemVer\Version as Versions;
use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\{InputArgument, InputInterface};
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use \Symfony\Component\Filesystem\Filesystem;

/**
 * @author Irontec <info@irontec.com>
 * @author ddniel16 <ddniel16>
 * @link https://github.com/irontec
 */
class GeneratePackageCommand extends Command
{

    const PACKAGE_FILE = 'package.json';

    protected static $defaultName = 'typescript:generate:package';

    /**
     * @var ParameterBagInterface
     */
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        parent::__construct(self::$defaultName);
    }

    protected function configure()
    {

        $this->setDescription('Generate or update package.json');

        $this->addArgument('output', InputArgument::REQUIRED, 'Where to generate the interfaces?');
        $this->addArgument('package-name', InputArgument::OPTIONAL, 'what is the name of the package?');
        $this->addArgument('version', InputArgument::OPTIONAL, 'manual version?');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dirOutput   = $input->getArgument('output');
        $packageName = $input->getArgument('package-name');
        $version     = $input->getArgument('version');

        $packageFile = $dirOutput . '/' . self::PACKAGE_FILE;

        $fs = new Filesystem();

        if (file_exists($packageFile) === false) {
            if (is_null($packageName)) {
                throw new \ErrorException('package-name is required to new package.json');
            }

            $fs->dumpFile($packageFile, json_encode($this->getDefaultPackage($packageName)), JSON_UNESCAPED_SLASHES);
            $output->writeln('Created ' . $packageFile);

        } else {

            $content = file_get_contents($packageFile);
            $content = json_decode($content, true);
            if (json_last_error() !== 0) {
                throw new \ErrorException(json_last_error_msg());
            }

            $currentVersion = new Versions((isset($content['version']) ? $content['version'] : '0.0.1'));

            if (in_array($version, array('major', 'minor', 'patch'))) {
                $incre = 'increment' . ucwords(strtolower($version));
                $currentVersion->$incre();
            } elseif (is_null($version) === true) {
                $currentVersion->incrementPatch();
            } else {
                $currentVersion->setVersion($version);
            }

            $content['version'] = $currentVersion->__toString();
            if (is_null($packageName) === false) {
                $content['name'] = $packageName;
            }

            $fs->dumpFile($packageFile, json_encode($content, JSON_UNESCAPED_SLASHES));
            $output->writeln('Updated ' . $packageFile);

        }

        return 0;

    }

    private function getDefaultPackage(string $packageName)
    {
        return array(
            'name' => $packageName,
            'version' => '0.0.1',
            'description' => 'typescript interfaces for ' . $packageName . ' project',
            'types' => 'models.d.ts',
            'keywords' => [],
            'author' => '',
            'license' => 'EUPL'
        );
    }

}
