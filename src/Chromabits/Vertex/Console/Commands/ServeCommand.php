<?php

namespace Chromabits\Vertex\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Class ServeCommand
 *
 * Serve the current application using a Docker container
 *
 * @package SellerLabs\Slapp\Console\Commands
 */
class ServeCommand extends Command
{
    /**
     * Name of the command
     *
     * @var string
     */
    protected $name = 'serve';

    /**
     * Description of the command
     *
     * @var string
     */
    protected $description = 'Server the app using a HHVM server inside a Docker container';

    /**
     * Main docker process
     *
     * @var Process
     */
    protected $docker;

    /**
     * ID of the Docker container
     *
     * @var string
     */
    protected $containerId;

    /**
     * Setup a handler function for SIGINT and SIGKILL
     *
     * This will allow us to kill the docker container once the user
     * stops the server using CTRL+C
     *
     * @param \Symfony\Component\Process\ProcessBuilder $builder
     */
    protected function setupSignalHandlers(ProcessBuilder $builder)
    {
        declare(ticks = 1);

        $signalCallback = function () use ($builder) {
            $this->line("\n<info>Terminating container</info>");

            $builder
                ->setArguments([
                    'kill',
                    $this->containerId
                ])
                ->getProcess()
                ->run();
        };

        pcntl_signal(SIGTERM, $signalCallback);
        pcntl_signal(SIGINT, $signalCallback);
    }

    /**
     * Name of the docker image to use
     *
     * <user>/<repo>:<tag> (Tag is optional)
     *
     * @return string
     */
    public function getImageName()
    {
        // Here we return the name of the docker image we would like to use
        // for the container. Vertex is included as a default, however, a
        // class may extenda customize this method in order to use another
        // image
        return 'eduard44/vertex';
    }

    /**
     * Get run arguments
     *
     * Get arguments to pass to docker run when starting the container
     *
     * @return array
     */
    public function getRunArguments()
    {
        return [
            '--publish=80:80',
            '--volume=' . base_path() . ':/var/www/vertex'
        ];
    }

    /**
     * Start the docker container
     *
     * @param \Symfony\Component\Process\ProcessBuilder $builder
     */
    protected function startContainer(ProcessBuilder $builder)
    {
        $arguments = array_merge(
            ['run', '-d'],
            $this->getRunArguments(),
            [$this->getImageName()]
        );

        $dockerRun= $builder
            ->setArguments($arguments)
            ->setTimeout(null)
            ->getProcess();

        $this->line('<info>Launching container (this might take a bit)...</info>');
        $this->output->writeln('<fg=black>' . $dockerRun->getCommandLine() . '</fg=black>');

        $dockerRun->run();
        $this->containerId = $dockerRun->getOutput();
        $this->containerId = str_replace("\n", '', $this->containerId);
    }

    /**
     * Attach to the STDOUT and STDERR of the container
     *
     * @param \Symfony\Component\Process\ProcessBuilder $builder
     */
    protected function attachToContainer(ProcessBuilder $builder)
    {
        $this->docker = $builder
            ->setArguments([
                'attach',
                $this->containerId
            ])
            ->getProcess();

        $this->line('<info>Attaching to container...</info>');
        $this->output->writeln('<fg=black>' . $this->docker->getCommandLine() . '</fg=black>');

        $this->docker->start(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->output->write('<error>' . $buffer . '</error>');
            } else {
                $this->output->write($buffer);
            }
        });
    }

    /**
     * Handle the command
     */
    public function handle()
    {
        $builder = new ProcessBuilder();
        $builder->setPrefix('docker');

        $this->setupSignalHandlers($builder);

        $this->startContainer($builder);
        $this->attachToContainer($builder);

        while ($this->docker->isRunning()) {
            usleep(100000);
        }
    }
}
