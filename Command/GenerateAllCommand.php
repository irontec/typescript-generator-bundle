<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{ArrayInput, InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @author Irontec <info@irontec.com>
 * @author ddniel16 <ddniel16>
 * @link https://github.com/irontec
 */
class GenerateAllCommand extends Command
{
    public function __construct(private ParameterBagInterface $params)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        /** @var string @projectDir */
        $projectDir = $this->params->get('kernel.project_dir');

        $this->setName('typescript:generate:all');
        $this->setDescription('Execute all commands');
        $this->setHelp('bin/console typescript:generate:all interfaces src/Entity');

        $this->addArgument('output', InputArgument::REQUIRED, 'Where to generate the interfaces?');
        $this->addArgument('entities-dir', InputArgument::OPTIONAL, 'Where are the entities?', "{$projectDir}/src/Entity/");
        $this->addArgument('package-name', InputArgument::OPTIONAL, 'What is the name of the package?');
        $this->addArgument('version', InputArgument::OPTIONAL, 'Manual version?');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commandInterface = $this->getApplication()?->find('typescript:generate:interfaces');
        $commandPackage   = $this->getApplication()?->find('typescript:generate:package');

        if (null === $commandInterface || null === $commandPackage) {
            return Command::FAILURE;
        }

        $dirOutput = $input->getArgument('output');
        $dirEntity = $input->getArgument('entities-dir');
        $packageName = $input->getArgument('package-name');
        $version     = $input->getArgument('version');

        $argumentsInterface = [
            'output' => $dirOutput,
            'entities-dir' => $dirEntity
        ];

        $argumentsPackage = [
            'output' => $dirOutput,
            'package-name' => $packageName,
            'version' => $version
        ];

        $status = $commandPackage->run(new ArrayInput($argumentsPackage), $output);

        if (Command::SUCCESS !== $status) {
            return Command::INVALID;
        }

        $status = $commandInterface->run(new ArrayInput($argumentsInterface), $output);

        if (Command::SUCCESS !== $status) {
            return Command::INVALID;
        }

        return Command::SUCCESS;
    }
}
