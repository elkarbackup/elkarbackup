<?php

namespace JMS\SecurityExtraBundle\Tests\Functional\TestBundle;

if (class_exists('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage')) {
    class TokenStorageHelper
    {
        const SERVICE = 'security.token_storage';
    }
} else {
    class TokenStorageHelper
    {
        const SERVICE = 'security.context';
    }
}
