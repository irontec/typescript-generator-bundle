<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\Command;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\{ArrayInput, InputArgument, InputInterface};
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @author Irontec <info@irontec.com>
 * @author ddniel16 <ddniel16>
 * @link https://github.com/irontec
 */
class GenerateAllCommand extends Command
{

    protected static $defaultName = 'typescript:generate:all';

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

        $this->setDescription('Execute all commands');
        $this->setHelp('bin/console typescript:generate:all interfaces src/Entity');

        $this->addArgument('output', InputArgument::REQUIRED, 'Where to generate the interfaces?');
        $this->addArgument('entities-dir', InputArgument::OPTIONAL, 'Where are the entities?', $this->params->get('kernel.project_dir') . '/src/Entity/');
        $this->addArgument('package-name', InputArgument::OPTIONAL, 'what is the name of the package?');
        $this->addArgument('version', InputArgument::OPTIONAL, 'manual version?');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $commandInterface = $this->getApplication()->find('typescript:generate:interfaces');
        $commandPackage   = $this->getApplication()->find('typescript:generate:package');

        $dirOutput = $input->getArgument('output');
        $dirEntity = $input->getArgument('entities-dir');
        $packageName = $input->getArgument('package-name');
        $version     = $input->getArgument('version');

        $argumentsInterface = array(
            'output' => $dirOutput,
            'entities-dir' => $dirEntity
        );

        $argumentsPackage = array(
            'output' => $dirOutput,
            'package-name' => $packageName,
            'version' => $version
        );

        $commandInterface->run(new ArrayInput($argumentsInterface), $output);
        $commandPackage->run(new ArrayInput($argumentsPackage), $output);

        return 0;

    }

}
