<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Command;

use Binovo\ElkarBackupBundle\Lib\LoggingCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigureNodeCommand extends LoggingCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('tahoe:config_node')
            ->addArgument('i.furl', InputArgument::REQUIRED, '[client] Introducers furl')
            ->addArgument('s.K', InputArgument::OPTIONAL, '[client] Shares needed(K)')
            ->addArgument('s.H', InputArgument::OPTIONAL, '[client] Shares happy(H)')
            ->addArgument('s.N', InputArgument::OPTIONAL, '[client] Shares total(N)')
            ->addArgument('nname', InputArgument::OPTIONAL, '[node] Nodes nickname')
            ->setDescription('Configures the existent Tahoe client node.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => 'ConfigureTahoeNodeCommand');

        //$clientNodeDir = $this->container->getParameter('tahoe_node_dir');
        $clientNodeDir = '/var/lib/elkarbackup/.tahoe/';
        $nodeConfigFile = $clientNodeDir . 'tahoe.cfg';

        $attr = array();
        // [node]
        if ( !($attr['nickname'] = $input->getArgument('nname')) ) $attr['nickname'] = 'elkarbackup_node';
        // [client]
        $attr['introducer.furl'] = $input->getArgument('i.furl');
        if ( !($attr['shares.needed'] = $input->getArgument('s.K')) ) $attr['shares.needed'] = 3;
        if ( !($attr['shares.happy'] = $input->getArgument('s.H')) ) $attr['shares.happy'] = 7;
        if ( !($attr['shares.total'] = $input->getArgument('s.N')) ) $attr['shares.total'] = 10;

        if(file_exists($nodeConfigFile) === true) {
            if(is_writeable($nodeConfigFile) ) {
                try {
                    $content = file_get_contents($nodeConfigFile);

                    $keys = array_keys($attr);
                    foreach ($keys as $key) {
                        $oldLine = '';
                        $newLine = $key . ' = ' . $attr[$key];

                        $i=strpos($content, ($key . ' =') );
                        if($content[$i-1] == '#') $i--;                      
                        for(;$i<strlen($content);$i++) {
                            if($content[$i] == "\n") break;
                            $oldLine = $oldLine . $content[$i];
                        }

                        $content = str_replace($oldLine, $newLine, $content);
                    }

                    if($out = file_put_contents($nodeConfigFile, $content) > 0) {
                        $this->info('Success on tahoe node configuration');
                        return 0;
                    }
                    else {
                        $commandOutput = 'Error while writing file';
                        $this->err('Error configuring tahoe node: ' . $commandOutput);
                        return 1;
                    }
                }
                catch(Exception $e) {
                    $commandOutput = 'Error : '.$e;
                    $this->err('Error configuring tahoe node: ' . $commandOutput);
                    return 1;
                }
            }
            else {
                $commandOutput = 'No permission to write on the file';
                $this->err('Error configuring tahoe node: ' . $commandOutput);
                return 1;
            }
        }
        else {
            $commandOutput = 'File not found. Make sure the client node exists and the path is correct.';
            $this->err('Error configuring tahoe node:  ' . $commandOutput);
            return 1;
        }
    }

    protected function getNameForLogs()
    {
        return 'ConfigureTahoeNodeCommand';
    }

}
