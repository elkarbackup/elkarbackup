<?php
namespace App\Api\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use Hautelook\AliceBundle\PhpUnit\RecreateDatabaseTrait;

class BaseApiTestCase extends ApiTestCase
{
    use RecreateDatabaseTrait;
    
    protected function createUser(): User
    {
        $user = new User();
        $user->setUsername('root');
        $user->setEmail('root@localhost');
        $user->setRoles(array('ROLE_ADMIN'));
        $user->setSalt(md5(uniqid(null, true)));
        $encoder = self::bootKernel()->getContainer()->get('security.encoder_factory')->getEncoder($user);
        $password = $encoder->encodePassword('root', $user->getSalt());
        $user->setPassword($password);
        $em = self::$container->get('doctrine')->getManager();
        $em->persist($user);
        $em->flush();
        return $user;
    }
}

