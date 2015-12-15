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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RenewLeasesCommand extends LoggingCommand
{
    const RENEW_LOG = 'elkarbackupRenew.log';
    protected $_renewLog;

    protected function configure()
    {
        parent::configure();
        $this->setName('tahoe:renew_leases')
              ->setDescription('Renews the lease of every file saved in the Tahoe storage.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => 'RenewLeasesCommand');
        $tahoe = $this->getContainer()->get('Tahoe');
        $this->_renewLog = $tahoe->getRelativeNodePath() . self::RENEW_LOG;
        $tahoeAlias = $tahoe->getBin();

        if (!$tahoe->isReady()) {
            if ($tahoe->isInstalled()){
                $when = $this->_getLastRenewDate();
                if (null!=$when) {
                    $this->err('Warning: Tahoe storage is actually not configured properly or configured at all. Lease renewal not performed since [' . $when . ']', $context);
                } else {
                    $this->warn('Tahoe storage not configured', $context);
                }
            }
            return 1;
        }

        $command = $tahoeAlias . ' deep-check --add-lease elkarbackup: 2>&1';
        $commandOutput  = array();
        $status         = 0;
        exec($command, $commandOutput, $status);
        if (0 != $status) {
            $this->err('Error trying to renew Tahoe leases', $context);
            return $status;
        }
        $this->info('Tahoe leases have been renewed', $context);

        $this->_updateFile($commandOutput);

        return 0;
    }

    protected function _getLastRenewDate()
    {
        if (file_exists($this->_renewLog)) {
            $content = file_get_contents($this->_renewLog);
            $startingTag = 'last-> renew ';
            $date='';
            $i=strpos($content, $startingTag)+strlen($startingTag);
            while ("\n"!=$content[$i]) {
                $date.=$content[$i];
                $i++;
            }

            $rawDate = '';
            for ($i=0;$i<strlen($date);$i++) {
                $rawDate.= $date[$i];
                if ('('==$date[$i+2]) {
                    break;
                }
            }

            $localTimezone = date_default_timezone_get();
            date_default_timezone_set('UTC');
            $realDate = strtotime($rawDate);
            date_default_timezone_set($localTimezone);
            $gmtDate = date("Y-m-d H:i:s", $realDate);

            return $gmtDate;
        }
        return null;
    }

    protected function getNameForLogs()
    {
        return 'RenewLeasesCommand';
    }

    protected function _updateFile($commandOutput)
    {
        $context = array('source' => 'RenewLeasesCommand');

        date_default_timezone_set('UTC');
        $newDate = date("Y-m-d H:i:s", time());
        $startingTag = 'last-> ';
        $closingTag = ' <-';
        $update = $startingTag . 'renew ' . $newDate . " (utc)\n";
        $update = $update . $commandOutput[0] . $closingTag;

        if (file_exists($this->_renewLog) && is_writable($this->_renewLog)) {
            $content = file_get_contents($this->_renewLog);
            $oldLine = '';

            $beginPos=strpos($content, $startingTag)+strlen($startingTag);
            $endPos=strpos($content, $closingTag);
            $stopNewLine = false;
            while ($beginPos<$endPos && $beginPos<strlen($content)) {
                $oldLine = $oldLine . $content[$beginPos];
                if (!$stopNewLine) {
                    if ("\n"===$content[$beginPos]) {
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
                if (file_put_contents($this->_renewLog, $content) > 0) {
                    $this->info('Renew date printed', $context);
                } else {
                    $this->warn('Warning: renew log could not be updated', $context);
                }
            } catch (Exception $e) {
                $this->warn('Warning: renew log could not be updated: ' . $e->getMessage(), $context);
            }
        } else {
            if (file_exists($this->_renewLog)) {
                unlink($this->_renewLog);
            }
            $file = fopen($this->_renewLog, "w");
            fwrite($file, $update . "\n");
            fclose($file);
        }
    }
}
