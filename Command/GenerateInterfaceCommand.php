<?php
/**
 * This file is part of the TypeScriptGeneratorBundle.
 */

namespace Irontec\TypeScriptGeneratorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Irontec\TypeScriptGeneratorBundle\ParseTypeScript\Parser as ParseTypeScript;

/**
 * @author Irontec <info@irontec.com>
 * @author ddniel16 <ddniel16>
 * @link https://github.com/irontec
 */
class GenerateInterfaceCommand extends Command
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
        $this->setName('typescript:generate:interfaces');
        $this->setDescription('Generate TypeScript interfaces from Doctrine Entities');
        $this->setHelp('bin/console typescript:generate:interfaces interfaces src/Entity');

        $this->addArgument('output', InputArgument::REQUIRED, 'Where to generate the interfaces?');
        $this->addArgument('entities-dir', InputArgument::OPTIONAL, 'Where are the entities?', "{$this->projectDir}/src/Entity/");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $dirOutput */
        $dirOutput = $input->getArgument('output');

        /** @var string $dirEntity */
        $dirEntity = $input->getArgument('entities-dir');

        $finder = new Finder();
        $finder->in($dirEntity)->name('*.php');

        foreach ($finder as $file) {
            $parser = new ParseTypeScript($file->getPathName());
            $parserOutput = $parser->getOutput();

            if (empty($parserOutput)) {
                continue;
            }

            $targetFile = "{$this->projectDir}/{$dirOutput}/" . str_replace('.php', '.d.ts', $file->getFilename());
            $this->writeToFile($targetFile, $parserOutput);
            $output->writeln(sprintf('Created interface %s', $targetFile));

            $models[] = $parser->getCurrentInterface()->name;
        }

        if (!isset($models)) {
            return Command::SUCCESS;
        }

        $content = array_reduce($models, fn ($content, $model) => sprintf("%sexport * from './%s';%s", $content, $model, PHP_EOL));

        if (!is_string($content)) {
            return Command::SUCCESS;
        }

        $targetFile = $dirOutput . '/models.d.ts';
        $this->writeToFile($targetFile, $content);
        $output->writeln(sprintf('Created %s', $targetFile));

        return Command::SUCCESS;
    }

    private function writeToFile(string $filename, string $content): void
    {
        $fs = new Filesystem();

        $fs->dumpFile($filename, $content);
    }
}
