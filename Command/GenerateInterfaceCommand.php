<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\Command;

use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use \Symfony\Component\Filesystem\Filesystem;
use \Symfony\Component\Finder\Finder;

use \Irontec\TypeScriptGeneratorBundle\ParseTypeScript\Parser as ParseTypeScript;

/**
 * @author Irontec <info@irontec.com>
 * @author ddniel16 <ddniel16>
 * @link https://github.com/irontec
 */
class GenerateInterfaceCommand extends Command
{

    protected static $defaultName = 'typescript:generate:interface';

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

        $this->setDescription('Generate TypeScript interfaces from Doctrine Entities');

        $this->addArgument('output', InputArgument::REQUIRED, 'Where to generate the interfaces?');

        $this->addArgument(
            'entities-dir',
            InputArgument::OPTIONAL,
            'Where are the entities?',
            $this->params->get('kernel.project_dir') . '/src/Entity/'
        );

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $dirOutput = $input->getArgument('output');
        $dirEntity = $input->getArgument('entities-dir');

        $fs = new Filesystem();
        $finder = new Finder();
        $finder->files('*.php')->in($dirEntity);

        foreach ($finder as $file) {

            $parser = new ParseTypeScript($file->getPathName());

            $parserOutput = $parser->getOutput();
            if (empty($parserOutput) === false) {

                $targetFile = $dirOutput . '/' . str_replace( '.php','.ts', $file->getFilename());
                $fs->dumpFile($targetFile, $parserOutput);
                $output->writeln('created interface ' . $targetFile);

            }
        }

        return 0;

    }

}
