<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Command;

use \Exception;
use Binovo\ElkarBackupBundle\Lib\LoggingCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
            ->addArgument('storage', InputArgument::OPTIONAL, '[storage] Storage enabled')
            ->setDescription('Configures the Tahoe client node.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => 'ConfigureTahoeNodeCommand');
        $tahoe = $this->getContainer()->get('Tahoe');
        $tahoeAlias = $tahoe->getBin();
        $tahoeNodePath = $tahoe->getRelativeNodePath();
        $wwwdataNodePath = $tahoe->getRelativePointerToNode();

        //Node creation
        if (!is_dir($tahoeNodePath)) {
            $command        = $tahoeAlias . ' create-client --webport=tcp:3456:interface=0.0.0.0 2>&1';
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 != $status) {
                $this->err('Error creating Tahoe node: ' . implode("\n", $commandOutput), $context);
                return $status;
            }
            $this->info($commandOutput[0], $context);
        }

        //Set: tahoe - not ready
        $readyFile = $tahoe->getReadyFile();
        file_put_contents($readyFile, '500');

        //Node configuration
        $nodeConfigFile = $tahoeNodePath . 'tahoe.cfg';

        $attr = array();
                // [node]
        if ( !($attr['nickname'] = $input->getArgument('nname')) ) {
            $attr['nickname'] = 'elkarbackup_node';
        }
                // [client]
        $attr['introducer.furl'] = $input->getArgument('i.furl');

        if ( !($attr['shares.needed'] = $input->getArgument('s.K')) ) {
            $attr['shares.needed'] = 3;
        }
        if ( !($attr['shares.happy'] = $input->getArgument('s.H')) ) {
            $attr['shares.happy'] = 7;
        }
        if ( !($attr['shares.total'] = $input->getArgument('s.N')) ) {
            $attr['shares.total'] = 10;
        }
                //[storage]
                //remember to only replace 1st appearance of 'enabled'
        if ( !($attr['enabled'] = $input->getArgument('storage')) ) {
            $attr['enabled'] = 'false';
        }

        if (file_exists($nodeConfigFile)) {
            if (is_writeable($nodeConfigFile) ) {
                try {
                    $content = file_get_contents($nodeConfigFile);
                    $keys = array_keys($attr);
                    foreach ($keys as $key) {
                        $oldLine = '';
                        $newLine = $key . ' = ' . $attr[$key];

                        $i=strpos($content, ($key . ' =') );
                        if ('introducer.furl' == $key) {
                            $j=$i+strlen('introducer.furl = ');
                            $oldFurl = '';
                        }
                        if ('#' == $content[$i-1]) {
                            $i--;
                        }
                        for (;$i<strlen($content);$i++) {
                            if ("\n" == $content[$i]) {
                                break;
                            }
                            if ('introducer.furl' == $key and $i == $j) {
                                $oldFurl = $oldFurl . $content[$i];
                                $j++;
                            }
                            $oldLine = $oldLine . $content[$i];
                        }
                        if ('introducer.furl' == $key) {
                            $oldFurlLine = $oldLine;
                        }
                        //replace only first appearance
                        $content = implode($newLine, explode($oldLine, $content, 2));
                    }
                    if (file_put_contents($nodeConfigFile, $content) > 0) {
                        $this->info('Node configuration set', $context);
                    } else {
                        $this->err('Error configuring tahoe node: Error while writing file', $context);
                        return 1;
                    }
                } catch (Exception $e) {
                    $this->err('Error configuring tahoe node: ' . $e->getMessage(), $context);
                    return 1;
                }
            } else {
                $this->err('Error configuring tahoe node: No permission to write on the file', $context);
                return 1;
            }
        } else {
            $this->err('Error configuring tahoe node:  File not found. Make sure the client node exists and the path is correct', $context);
            return 1;
        }

        //Manage aliases
        if ($oldFurl != $attr['introducer.furl']) {

            $aliasesDir = $tahoeNodePath . 'private/';
            $aliasesFile = $aliasesDir . 'aliases';
            if (file_exists($aliasesFile)) {
                date_default_timezone_set("UTC");
                $newName = date("Y-m-d_H:i:s", time());
                $newName = 'old_aliases_' . $newName . 'Z'; //zulu time (utc)
                if (is_writeable($nodeConfigFile)) {
                    file_put_contents($aliasesFile, 'introducer.furl = ' . $oldFurl . "\n", FILE_APPEND);
                }
                $command        = 'mv ' . $aliasesFile . ' ' . $aliasesDir . $newName . ' 2>&1';
                $commandOutput  = array();
                $status         = 0;
                exec($command, $commandOutput, $status);
                if (0 != $status) {
                    $this->err('Error saving old aliases: ' . implode("\n", $commandOutput), $context);
                } else {
                    $this->info('Old aliases saved in ' . $tahoeNodePath . 'private/' . $newName, $context);
                }
                //New file will be automatically created when a new alias is created
            }
        }

        //Launch daemon - Node connexion
        $command        = $tahoeAlias . ' restart 2>&1'; //works even if it was not running
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $errStart = count($commandOutput)-2;
            $this->err('Error starting Tahoe node: ' . $commandOutput[$errStart], $context);
            return $status;
        }

        if (!file_exists($wwwdataNodePath . 'private/')) {
            mkdir($wwwdataNodePath . 'private/', 0775, true);
        }
        //Set the access to the node for www-data
        $elkarbackupNodeUrlFile = $tahoeNodePath . 'node.url';
        $wwwdataNodeUrlFile     = $wwwdataNodePath . 'node.url';
        $i = 0;
        do {
            usleep(100000); //wait 0.1 sec
            $i++;
        } while (!file_exists($elkarbackupNodeUrlFile) and $i<30);
        if (file_exists($elkarbackupNodeUrlFile)) {
            $content = file_get_contents($elkarbackupNodeUrlFile);
            file_put_contents($wwwdataNodeUrlFile, $content);
        } else {    //should never happen at this point
            $this->err('Error: node.url file missing. Elkarbackup will not be able to access data stored at tahoe.', $context);
            return 1;
        }

        //Create elkarbackup directory in the tahoe grid (only works the 1st time or when furl changes)
        $command        = $tahoeAlias . ' create-alias elkarbackup:';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 == $status) {
            $this->info('New alias created [ elkarbackup: ]', $context);

            //Set the ready-only URI as elkarbackup: alias for www-data
            $elkarbackupAliasesFile = $tahoeNodePath . 'private/aliases';
            $wwwdataAliasesFile = $wwwdataNodePath . 'private/aliases';
            $alias = 'elkarbackup: ';
            $writecap = '';
            $ending = '';
            $content = file_get_contents($elkarbackupAliasesFile);
            $i=strpos($content, $alias);
            $i+=strlen($alias);
            $colonCount = 0;
            while ("\n"!=$content[$i]) {
                $writecap.=$content[$i];
                if (3==$colonCount) {
                    $ending.=$content[$i];
                } else {
                    if (':'==$content[$i]) {
                        $colonCount++;
                    }
                }
                $i++;
            }
            file_put_contents($readyFile, $writecap);
            $command        = $tahoeAlias . ' debug dump-cap ' . $writecap;
            $commandOutput  = array();
            $status         = 0;
            exec($command, $commandOutput, $status);
            if (0 == $status) {
                $readkey = substr($commandOutput[3], strlen(' readkey: '));
                $readcap = 'URI:DIR2-RO:' . $readkey . ':' . $ending;
                file_put_contents($wwwdataAliasesFile, $alias . ' ' . $readcap . "\n");
            } else {
                $this->err('Error: cannot obtain readcap. Elkarbackup will not be able to access data stored at tahoe.', $context);
            }
        }

        //Check if tahoe is ready to work
        $command        = $tahoeAlias . ' ls elkarbackup:';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->err('Error connecting to the tahoe grid. Tahoe storage not ready to work.', $context);
            return $status;
        }

        //Set: tahoe is ready
        $content = file_get_contents($readyFile);
        if (false==strstr($content, 'URI')){
            file_put_contents($readyFile, '200');
        }

        return 0;
    }

    protected function getNameForLogs()
    {
        return 'ConfigureTahoeNodeCommand';
    }
}
