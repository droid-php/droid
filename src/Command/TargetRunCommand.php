<?php

namespace Droid\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use RuntimeException;
use Droid\Remote\Enabler;
use Droid\Remote\SynchroniserComposer;
use Droid\Remote\SynchroniserPhar;
use Droid\TaskRunner;

class TargetRunCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('target:run')
            ->setDescription(
                'Run a target'
            )
            ->addArgument(
                'target',
                InputArgument::OPTIONAL,
                'Target to run'
            )
        ;
    }

    private $target;

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $target = $input->getArgument('target');
        if (!$target) {
            $target = 'default';
            if ($this->target) {
                $target = $this->target;
            }
        }

        $localComposerFiles = null;
        $localDroidBinary = null;
        try {
            $localComposerFiles = $this->locateLocalComposerFiles();
        } catch (RuntimeException $eComposer) {
            try {
                $localDroidBinary = $this->locateLocalDroidBinary();
            } catch (RuntimeException $ePhar) {
                $output->writeln(sprintf(
                    '<comment>Unable to run remote commands: %s; %s.</comment>',
                    $eComposer->getMessage(),
                    $ePhar->getMessage()
                ));
            }
        }
        $enabler = new Enabler(
            $localComposerFiles
            ? new SynchroniserComposer($localComposerFiles)
            : new SynchroniserPhar($localDroidBinary)
        );

        $project = $this->getApplication()->getProject();
        $runner = new TaskRunner($this->getApplication(), $output, $enabler);

        $output->writeln("<info>Droid: Running target `$target`</info>");

        $res = $runner->runTarget($project, $target);

        $output->writeln("Result: " . $res);
        $output->writeln('--------------------------------------------');
    }

    protected function locateLocalDroidBinary()
    {
        $candidatePath = getcwd() . DIRECTORY_SEPARATOR
            . $this->getApplication()->getDroidBinaryFilename()
        ;

        if (!file_exists($candidatePath)) {
            throw new RuntimeException(sprintf(
                'Unable to find the droid binary. Tried: "%s"',
                $candidatePath
            ));
        }
        $fh = fopen($candidatePath, 'rb');
        if ($fh === false) {
            throw new \RuntimeException(sprintf(
                'Unable to open the droid binary file. Please check read permissions for %s.',
                $candidatePath
            ));
        }
        fclose($fh);

        return $candidatePath;
    }

    protected function locateLocalComposerFiles()
    {
        $candidatePath = $this->getApplication()->getBasePath();
        $composerJson = $candidatePath . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($composerJson)) {
            throw new RuntimeException(sprintf(
                'Unable to find composer.json. Tried: "%s"',
                $composerJson
            ));
        }
        $fh = fopen($composerJson, 'rb');
        if ($fh === false) {
            throw new \RuntimeException(sprintf(
                'Unable to open composer.json. Please check read permissions for %s.',
                $composerJson
            ));
        }
        fclose($fh);

        return $candidatePath;
    }
}
