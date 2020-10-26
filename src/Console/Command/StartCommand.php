<?php

namespace PandaLeague\MockServer\Console\Command;

use PandaLeague\MockServer\Server\ServerFactory;
use Ratchet\Server\IoServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'start';

    protected function configure()
    {
        $this->setDescription('Starts a new socket server')
            ->addArgument('host', InputArgument::REQUIRED, 'Host the new server will be listening on')
            ->addArgument('port', InputArgument::REQUIRED, 'Port the server will be listening on')
            ->addArgument('server', InputArgument::REQUIRED, 'FQDN of server we will be using')
            ->addArgument('storage', InputArgument::REQUIRED, 'Storage driver to use')
            ->addArgument('storage-params', InputArgument::REQUIRED, 'Storage parameters to use')
            ->addArgument('connection-id', InputArgument::REQUIRED, 'UUID for this server');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $storage       = $input->getArgument('storage');
        $storageParams = $input->getArgument('storage-params');
        $host          = $input->getArgument('host');
        $port          = $input->getArgument('port');
        $server        = $input->getArgument('server');
        $connectionId  = $input->getArgument('connection-id');

        $storage = new $storage(json_decode($storageParams, true));
        $server = ServerFactory::create($server, $connectionId, $storage);

        $ioServer = IoServer::factory(
            $server,
            $port,
            $host
        );
        $output->writeln('Server Started');
        $ioServer->run();

        return Command::SUCCESS;
    }
}
