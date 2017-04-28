<?php

namespace PhpEarth\Swoole\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use PhpEarth\Swoole\Driver\Symfony\Driver;

/**
 * Run Swoole HTTP server.
 *
 * php bin/console server:start --host=[HOST] --port=[PORT] --env=[ENV] --no-debug=[true|false]
 */
class ServerCommand extends Command
{
    private $hasPerformedRequest = false;
    private $driver;

    protected function configure()
    {
        $this
            ->setName('server:start')
            ->setDescription('Start Swoole HTTP Server.')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host for server', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port for server', 9501)
            ->addOption('env', null, InputOption::VALUE_OPTIONAL, 'Environment', 'dev')
            ->addOption('no-debug', null, InputOption::VALUE_NONE, 'Switch debug mode on/off')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $http = new \swoole_http_server($input->getOption('host'), $input->getOption('port'));

        $this->driver = new Driver();

        $debug = ($input->getOption('no-debug')) ? false : (($input->getOption('env') == 'prod') ? false : true);

        $this->driver->boot($input->getOption('env'), $debug);

        $http->on('request', function(\swoole_http_request $request, \swoole_http_response $response) {
            if ($this->hasPerformedRequest) {
                $this->driver->preHandle();
            } else {
                $this->hasPerformedRequest = true;
            }

            $response = $this->driver->handle($request, $response);

            $this->driver->postHandle();
        });

        $output->writeln('Swoole HTTP Server started on '.$input->getOption('host').':'.$input->getOption('port'));
        $http->start();
    }
}
