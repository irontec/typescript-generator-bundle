<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\Command;

use Irontec\TypeScriptGeneratorBundle\Package\Package;
use PHLAK\SemVer\Version as Versions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;


/**
 * @author Irontec <info@irontec.com>
 * @author ddniel16 <ddniel16>
 * @link https://github.com/irontec
 */
class GeneratePackageCommand extends Command
{
    private string $projectDir;

    public function __construct(private ParameterBagInterface $params)
    {
        /** @var string $projectDir */
        $this->projectDir = $this->params->get('kernel.project_dir');

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('typescript:generate:package');
        $this->setDescription('Generate or update package.json');

        $this->addArgument('output', InputArgument::REQUIRED, 'Where to generate the interfaces?');
        $this->addArgument('package-name', InputArgument::OPTIONAL, 'What is the name of the package?', 'default');
        $this->addArgument('version', InputArgument::OPTIONAL, 'Manual version?', 'current');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $dirOutput */
        $dirOutput = $input->getArgument('output');

        /** @var string $packageName */
        $packageName = $input->getArgument('package-name');

        /** @var string $version */
        $version = $input->getArgument('version');

        $packageFilename = "{$this->projectDir}/{$dirOutput}/" . Package::PACKAGE_FILENAME;

        if (false === file_exists($packageFilename)) {

            if ('default' === $packageName) {
                $output->writeln('Argument `package-name` is required for new packages and it can not be `default`.');

                return Command::INVALID;
            }

            $this->writePackageToFile($packageFilename, new Package($packageName, $version));
            $output->writeln(sprintf('Created %s', $packageFilename));

        } else {
            $package = Package::createFromJson($packageFilename);

            if ('default' !== $packageName) {
                $package->setName($packageName);
            }

            $nextVersion = $this->getPackageNextVersion($package, $version);
            $package->setVersion($nextVersion);

            $this->writePackageToFile($packageFilename, $package);
            $output->writeln(sprintf('Updated %s', $packageFilename));
        }

        return Command::SUCCESS;
    }

    private function getPackageNextVersion(Package $package, string $inputVersion): string
    {
        $currentVersion = new Versions($package->getVersion() ?? Package::DEFAULT_VERSION);

        if ('current' === $inputVersion) {
            $currentVersion->incrementPatch();
        } elseif (in_array($inputVersion, ['major', 'minor', 'patch'])) {
            $incrementMethod = 'increment' . ucwords(strtolower($inputVersion));
            $currentVersion->$incrementMethod();
        } else {
            $currentVersion->setVersion($inputVersion);
        }

        return (string) $currentVersion;
    }

    private function writePackageToFile(string $filename, Package $package): void
    {
        $fs = new Filesystem();

        $fs->dumpFile($filename, (string) $package);
    }
}
