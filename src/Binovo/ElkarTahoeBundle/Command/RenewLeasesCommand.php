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

class RenewLeasesCommand extends LoggingCommand
{
    const LAST_RENEW_FILE = '.tahoe/elkarbackupRenew.txt';

    protected function configure()
    {
        parent::configure();
        $this->setName('tahoe:renew_leases')
              ->setDescription('Renews the lease of every file saved in the Tahoe storage.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => 'RenewLeasesCommand');
        $tahoeAlias = 'tahoe';

        if(!$this->_isTahoeReady()) {
            $when = $this->_getLastRenewDate();
            if(null==$when) $when = 'never';
            $this->err('Warning: tahoe storage is not properly configurated. Lease renewal not performed since [' . $when . ']' );
            return 1;
        }

        //TODO: check if the time between the last renew and now is higher than the tahoe renew frequency (params)

        $command = $tahoeAlias . ' deep-check --add-lease elkarbackup: 2>&1';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $hideURI = $commandOutput[0];
            //TODO: hide uri on error
                //use shell_exec?
                //or depending on status dont show the error -> ez
            /*$j=strpos($hideURI, '/uri/' );
            for(;$i<strlen(hideURI);$i++)*/
            $this->err('Error ' . $status . ': trying to renew tahoe leases: ' . $hideURI);
            return $status;
        }

        $this->_updateFile($commandOutput);

        $this->info('Tahoe leases have been renewed');
        return 0;        
    }


    protected function getNameForLogs()
    {
        return 'RenewLeasesCommand';
    }


    protected function _getLastRenewDate() {

        if(file_exists(self::LAST_RENEW_FILE)) {
            $content = file_get_contents(self::LAST_RENEW_FILE);
            $startingTag = 'last-> renew ';
            $date='';
            $i=strpos($content, $startingTag)+strlen($startingTag);
            while("\n"!=$content[$i]) {
                $date.=$content[$i];
                $i++;
            }
            
            if(true) { //convert utc to local time
                $rawDate = '';
                for($i=0;$i<strlen($date);$i++) {
                    $rawDate.= $date[$i];
                    if('('==$date[$i+2]) break;
                }

                $localTimezone = date_default_timezone_get();
                date_default_timezone_set('UTC');
                $realDate = strtotime($rawDate);
                date_default_timezone_set($localTimezone);
                $gmtDate = date("Y-m-d H:i:s", $realDate);

                return $gmtDate;
            }
            return $date;
        }
        return null;
    }


    protected function _isTahoeReady() {

        return file_exists('.tahoe/imReady.txt');
    }


    protected function _updateFile($commandOutput) {

        date_default_timezone_set('UTC');
        $newDate = date("Y-m-d H:i:s", time());
        $startingTag = 'last-> ';
        $closingTag = ' <-';
        $update = $startingTag . 'renew ' . $newDate . " (utc)\n";
        $update = $update . $commandOutput[0] . $closingTag;

        if(file_exists(self::LAST_RENEW_FILE) && is_writable(self::LAST_RENEW_FILE)) {
            $content = file_get_contents(self::LAST_RENEW_FILE);
            $oldLine = '';

            $beginPos=strpos($content, $startingTag)+strlen($startingTag);
            $endPos=strpos($content, $closingTag);
            $stopNewLine = false;
            while($beginPos<$endPos && $i<strlen($content)) {
                $oldLine = $oldLine . $content[$beginPos];
                if(!$stopNewLine) {
                    if("\n"===$content[$beginPos]) {
                        $stopNewLine=true;
                        $newLine = $oldLine;
                    }
                }
                $beginPos++;
            }
            $oldLine = $startingTag . $oldLine. $closingTag;
            $newLine = $newLine . $update;

            $content = str_replace($oldLine, $newLine, $content);
            try {
                if(file_put_contents(self::LAST_RENEW_FILE, $content) > 0) {
                    $this->info('Node configuration set');
                } else {
                    $this->warn('Warning renew log coud not be updated');
                }
            } catch (Exception $e) {
                $this->warn('Warning renew log coud not be updated: ' . $e->getMessage());
            }
        } else {
            if( file_exists(self::LAST_RENEW_FILE) ) unlink(self::LAST_RENEW_FILE);
            $file = fopen(self::LAST_RENEW_FILE, "w");
            fwrite($file, $update . "\n");
            fclose($file);
        }

    }

}
