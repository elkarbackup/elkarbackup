<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarTahoeBundle\Command;

use Binovo\ElkarBackupBundle\Lib\LoggingCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCodeCommand extends LoggingCommand
{
    protected function configure()
    {
        parent::configure();
        $this->setName('tahoe:update_code')
             ->setDescription('Update the returning code after the node configuration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $context = array('source' => $this->getNameForLogs());
        $tahoe = $this->getContainer()->get('Tahoe');
        $readyFile = $tahoe->getReadyFile();
        if (file_exists($readyFile)) {
            $content = file_get_contents($readyFile);
            if ($content!=false) {
                $key = $content[0] . $content[1] . $content[2];
                switch ($key) {
                    case '000':
                        //keep going
                    case '100':
                        //keep going
                    case '101':
                        break;
                    case '500':
                        file_put_contents($readyFile, '101');
                        break;
                    case 'URI':
                        //keep going
                    case '200':
                        file_put_contents($readyFile, '100');
                        break;
                    default:
                        //should never happen
                        file_put_contents($readyFile, '101');
                        break;
                }
                $this->info('Code updated', $context);
            } else {
                $this->info('Warning: cannot read the file', $context);
                file_put_contents($readyFile, '101');
            }
        } else {
            return 1;
        }
        return 0;
    }

    protected function getNameForLogs()
    {
        return 'UpdateTahoeCodeCommand';
    }
}