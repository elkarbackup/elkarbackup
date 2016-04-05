<?php
/**
 * @copyright 2012,2013 Binovo it Human Project, S.L.
 * @license http://www.opensource.org/licenses/bsd-license.php New-BSD
 */

namespace Binovo\ElkarBackupBundle\Logger;

use Monolog\Processor\WebProcessor;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Injects url/method and remote IP of the current web request in all records
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class WebUserLoggerProcessor extends WebProcessor implements ContainerAwareInterface
{
    private $container;
    /**
     * @param  array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $record = parent::__invoke($record);
        $record['extra'] = array_merge(
            $record['extra'],
            array(
                'user'       => null,
                'user_email' => '',
                'user_id'    => '',
                'user_name'  => '',
                )
            );
        $user = null;
        $token = $this->container->get('security.context')->getToken();
        if ($token) {
            $user = $token->getUser();
        }

        if ($user) {
            if (!is_string($user) && $user != "anon."){
                $record['extra'] = array_merge(
                    $record['extra'],
                    array(
                        'user'       => $user,
                        'user_email' => $user->getEmail(),
                        'user_id'    => $user->getId(),
                        'user_name'  => $user->getUsername(),
                        )
                    );
            }
        }

        return $record;
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
