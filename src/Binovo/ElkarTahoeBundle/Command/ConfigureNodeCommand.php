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
            ->setDescription('Configures the Tahoe client node.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => 'ConfigureTahoeNodeCommand');

        $tahoeAlias = 'tahoe'; //tahoe binary path

        //Node creation
        if(!is_dir('.tahoe/')) {
            $command = $tahoeAlias . ' create-client 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->err('Error creating Tahoe node: ' . implode("\n", $commandOutput));
                return $status;
            }
            $this->info($commandOutput[0]);     
        }

        //Set: tahoe - not ready (remove file)
        $readyFile = '.tahoe/imReady.txt';
        if(file_exists($readyFile)) unlink($readyFile);

        //Node configuration
        $nodeConfigFile = '.tahoe/tahoe.cfg';

        $attr = array();
        // [node]
        if ( !($attr['nickname'] = $input->getArgument('nname')) ) $attr['nickname'] = 'elkarbackup_node';
        // [client]
        $attr['introducer.furl'] = $input->getArgument('i.furl');

        if ( !($attr['shares.needed'] = $input->getArgument('s.K')) ) $attr['shares.needed'] = 3;
        if ( !($attr['shares.happy'] = $input->getArgument('s.H')) ) $attr['shares.happy'] = 7;
        if ( !($attr['shares.total'] = $input->getArgument('s.N')) ) $attr['shares.total'] = 10;

        if(file_exists($nodeConfigFile)) {
            if(is_writeable($nodeConfigFile) ) {
                try {
                    $content = file_get_contents($nodeConfigFile);
                    $keys = array_keys($attr);
                    foreach ($keys as $key) {
                        $oldLine = '';
                        $newLine = $key . ' = ' . $attr[$key];

                        $i=strpos($content, ($key . ' =') );
                        if('introducer.furl' == $key) {
                            $j=$i+strlen('introducer.furl = ');
                            $oldFurl = ''; 
                        }                     
                        if('#' == $content[$i-1]) $i--;                      
                        for(;$i<strlen($content);$i++) {
                            if("\n" == $content[$i]) break;
                            if('introducer.furl' == $key and $i == $j) {
                                $oldFurl = $oldFurl . $content[$i];
                                $j++;
                            }
                            $oldLine = $oldLine . $content[$i];
                        }
                        if('introducer.furl' == $key) $oldFurlLine = $oldLine;
                        $content = str_replace($oldLine, $newLine, $content);
                    }

                    if(file_put_contents($nodeConfigFile, $content) > 0) {
                        $this->info('Node configuration set');
                    } else {
                        $commandOutput = 'Error while writing file';
                        $this->err('Error configuring tahoe node: ' . $commandOutput);
                        return 1;
                    }
                } catch(Exception $e) {
                    $commandOutput = 'Error : '.$e;
                    $this->err('Error configuring tahoe node: ' . $commandOutput);
                    return 1;
                }
            } else {
                $commandOutput = 'No permission to write on the file';
                $this->err('Error configuring tahoe node: ' . $commandOutput);
                return 1;
            }
        } else {
            $commandOutput = 'File not found. Make sure the client node exists and the path is correct.';
            $this->err('Error configuring tahoe node:  ' . $commandOutput);
            return 1;
        }

        //Manage aliases
        if($oldFurl != $attr['introducer.furl']) {

            $aliasesFile = '.tahoe/private/aliases';
            if(file_exists($aliasesFile))
            {
                date_default_timezone_set("UTC");
                $newName = date("Y-m-d_H:i:s", time());
                $newName = 'old_aliases_' . $newName . 'Z'; //zulu time (utc)
                if(is_writeable($nodeConfigFile))
                    file_put_contents($aliasesFile, 'introducer.furl = ' . $oldFurl . "\n", FILE_APPEND);
                $command = 'mv ' . $aliasesFile . ' .tahoe/private/' . $newName . ' 2>&1';
                $commandOutput  = array();
                $status         = 0;
                exec($command, $commandOutput, $status);
                if (0 != $status) {
                    $this->err('Error saving old aliases: ' . implode("\n", $commandOutput));
                    return $status;
                }
                $this->info('Old aliases saved in ~/.tahoe/private/' . $newName);
                //New file will be automatically created when a new alias is created
            }
        }

        //Launch daemon - Node connexion
        $command = $tahoeAlias . ' restart 2>&1'; //works even if it was not running
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);   
        if (0 != $status) {
            $errStart = count($commandOutput)-2;
            $this->err('Error starting Tahoe node: ' . $commandOutput[$errStart]);
            return $status;
        }

        //Create elkarbackup directory in the tahoe grid
        $command = $tahoeAlias . ' create-alias elkarbackup:';
        $commandOutput  = array();
        $status = 0;
        exec($command, $commandOutput, $status);
        if (0 == $status)
            $this->info('New alias created [ elkarbackup: ]');
        
        //Check if tahoe is ready to work
        $command        = $tahoeAlias . ' ls elkarbackup:';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->err("Error connecting to the tahoe grid. Tahoe storage not ready to work.\nMake sure the configuration parameters are right, if so it might be a network (grid) issue.");
            return $status;
        }
        //Set: tahoe - ready (create file)
        $file = fopen($readyFile, 'w');
        fclose($file);
  
        return 0;
    }

    protected function getNameForLogs()
    {
        return 'ConfigureTahoeNodeCommand';
    }
}
