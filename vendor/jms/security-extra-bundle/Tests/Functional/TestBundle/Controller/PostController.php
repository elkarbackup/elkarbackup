<?php

namespace JMS\SecurityExtraBundle\Tests\Functional\TestBundle\Controller;

use Symfony\Component\Security\Acl\Permission\MaskBuilder;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use JMS\SecurityExtraBundle\Tests\Functional\TestBundle\Entity\Post;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Tests\Functional\TestBundle\TokenStorageHelper;

class PostController
{
    /** @DI\Inject */
    private $em;

    /** @DI\Inject(TokenStorageHelper::SERVICE) */
    private $tokenStorage;

    /** @DI\Inject */
    private $router;

    /**
     * @PreAuthorize("isAuthenticated()")
     */
    public function newPostAction(Request $request)
    {
        if (!$title = $request->request->get('title')) {
            throw new HttpException(400);
        }

        $this->em->getConnection()->beginTransaction();
        try {
            $post = new Post($title);
            $this->em->persist($post);
            $this->em->flush();

            $oid = ObjectIdentity::fromDomainObject($post);
            $acl = $this->getAclProvider()->createAcl($oid);

            $sid = UserSecurityIdentity::fromToken($this->tokenStorage->getToken());
            $acl->insertObjectAce($sid, MaskBuilder::MASK_OWNER);
            $this->getAclProvider()->updateAcl($acl);

            $this->em->getConnection()->commit();

            return new Response('', 201, array(
                'Location' => $this->router->generate('post_controller_edit', array('id' => $post->getId())),
            ));
        } catch (\Exception $ex) {
            $this->em->getConnection()->rollBack();
            $this->em->close();

            throw $ex;
        }
    }

    /**
     * @PreAuthorize("hasPermission(#post, 'edit')")
     */
    public function editPostAction(Post $post)
    {
        return new Response($post->getTitle());
    }

    /**
     * @PreAuthorize("hasRole('ROLE_BAR')")
     */
    public function listPostAction()
    {
        return new Response('list');
    }

    /** @PreAuthorize("alwaysTrue()") */
    public function fooPostAction()
    {
        return new Response('foo');
    }

    /** @DI\LookupMethod("security.acl.provider") */
    protected function getAclProvider() { }
}
