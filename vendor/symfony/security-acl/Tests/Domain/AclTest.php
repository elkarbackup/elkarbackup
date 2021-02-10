<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Security\Acl\Tests\Domain;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\PermissionGrantingStrategy;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

class AclTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructor()
    {
        $acl = new Acl(1, $oid = new ObjectIdentity('foo', 'foo'), $permissionStrategy = new PermissionGrantingStrategy(), [], true);

        $this->assertSame(1, $acl->getId());
        $this->assertSame($oid, $acl->getObjectIdentity());
        $this->assertNull($acl->getParentAcl());
        $this->assertTrue($acl->isEntriesInheriting());
    }

    /**
     * @dataProvider getDeleteAceTests
     */
    public function testDeleteAceThrowsExceptionOnInvalidIndex($type)
    {
        $this->expectException(\OutOfBoundsException::class);

        $acl = $this->getAcl();
        $acl->{'delete'.$type.'Ace'}(0);
    }

    /**
     * @dataProvider getDeleteAceTests
     */
    public function testDeleteAce($type)
    {
        $acl = $this->getAcl();
        $acl->{'insert'.$type.'Ace'}(new RoleSecurityIdentity('foo'), 1);
        $acl->{'insert'.$type.'Ace'}(new RoleSecurityIdentity('foo'), 2, 1);
        $acl->{'insert'.$type.'Ace'}(new RoleSecurityIdentity('foo'), 3, 2);

        $listener = $this->getListener([
            $type.'Aces', 'aceOrder', 'aceOrder', $type.'Aces',
        ]);
        $acl->addPropertyChangedListener($listener);

        $this->assertCount(3, $acl->{'get'.$type.'Aces'}());

        $acl->{'delete'.$type.'Ace'}(0);
        $this->assertCount(2, $aces = $acl->{'get'.$type.'Aces'}());
        $this->assertEquals(2, $aces[0]->getMask());
        $this->assertEquals(3, $aces[1]->getMask());

        $acl->{'delete'.$type.'Ace'}(1);
        $this->assertCount(1, $aces = $acl->{'get'.$type.'Aces'}());
        $this->assertEquals(2, $aces[0]->getMask());
    }

    public function getDeleteAceTests()
    {
        return [
            ['class'],
            ['object'],
        ];
    }

    /**
     * @dataProvider getDeleteFieldAceTests
     */
    public function testDeleteFieldAceThrowsExceptionOnInvalidIndex($type)
    {
        $this->expectException(\OutOfBoundsException::class);

        $acl = $this->getAcl();
        $acl->{'delete'.$type.'Ace'}('foo', 0);
    }

    /**
     * @dataProvider getDeleteFieldAceTests
     */
    public function testDeleteFieldAce($type)
    {
        $acl = $this->getAcl();
        $acl->{'insert'.$type.'Ace'}('foo', new RoleSecurityIdentity('foo'), 1, 0);
        $acl->{'insert'.$type.'Ace'}('foo', new RoleSecurityIdentity('foo'), 2, 1);
        $acl->{'insert'.$type.'Ace'}('foo', new RoleSecurityIdentity('foo'), 3, 2);

        $listener = $this->getListener([
            $type.'Aces', 'aceOrder', 'aceOrder', $type.'Aces',
        ]);
        $acl->addPropertyChangedListener($listener);

        $this->assertCount(3, $acl->{'get'.$type.'Aces'}('foo'));

        $acl->{'delete'.$type.'Ace'}(0, 'foo');
        $this->assertCount(2, $aces = $acl->{'get'.$type.'Aces'}('foo'));
        $this->assertEquals(2, $aces[0]->getMask());
        $this->assertEquals(3, $aces[1]->getMask());

        $acl->{'delete'.$type.'Ace'}(1, 'foo');
        $this->assertCount(1, $aces = $acl->{'get'.$type.'Aces'}('foo'));
        $this->assertEquals(2, $aces[0]->getMask());
    }

    public function getDeleteFieldAceTests()
    {
        return [
            ['classField'],
            ['objectField'],
        ];
    }

    /**
     * @dataProvider getInsertAceTests
     */
    public function testInsertAce($property, $method)
    {
        $acl = $this->getAcl();

        $listener = $this->getListener([
            $property, 'aceOrder', $property, 'aceOrder', $property,
        ]);
        $acl->addPropertyChangedListener($listener);

        $sid = new RoleSecurityIdentity('foo');
        $acl->$method($sid, 1);
        $acl->$method($sid, 2);
        $acl->$method($sid, 3, 1, false);

        $this->assertCount(3, $aces = $acl->{'get'.$property}());
        $this->assertEquals(2, $aces[0]->getMask());
        $this->assertEquals(3, $aces[1]->getMask());
        $this->assertEquals(1, $aces[2]->getMask());
    }

    /**
     * @dataProvider getInsertAceTests
     */
    public function testInsertClassAceThrowsExceptionOnInvalidIndex($property, $method)
    {
        $this->expectException(\OutOfBoundsException::class);

        $acl = $this->getAcl();
        $acl->$method(new RoleSecurityIdentity('foo'), 1, 1);
    }

    public function getInsertAceTests()
    {
        return [
            ['classAces', 'insertClassAce'],
            ['objectAces', 'insertObjectAce'],
        ];
    }

    /**
     * @dataProvider getInsertFieldAceTests
     */
    public function testInsertClassFieldAce($property, $method)
    {
        $acl = $this->getAcl();

        $listener = $this->getListener([
            $property, $property, 'aceOrder', $property,
            'aceOrder', 'aceOrder', $property,
        ]);
        $acl->addPropertyChangedListener($listener);

        $sid = new RoleSecurityIdentity('foo');
        $acl->$method('foo', $sid, 1);
        $acl->$method('foo2', $sid, 1);
        $acl->$method('foo', $sid, 3);
        $acl->$method('foo', $sid, 2);

        $this->assertCount(3, $aces = $acl->{'get'.$property}('foo'));
        $this->assertCount(1, $acl->{'get'.$property}('foo2'));
        $this->assertEquals(2, $aces[0]->getMask());
        $this->assertEquals(3, $aces[1]->getMask());
        $this->assertEquals(1, $aces[2]->getMask());
    }

    /**
     * @dataProvider getInsertFieldAceTests
     */
    public function testInsertClassFieldAceThrowsExceptionOnInvalidIndex($property, $method)
    {
        $this->expectException(\OutOfBoundsException::class);

        $acl = $this->getAcl();
        $acl->$method('foo', new RoleSecurityIdentity('foo'), 1, 1);
    }

    public function getInsertFieldAceTests()
    {
        return [
            ['classFieldAces', 'insertClassFieldAce'],
            ['objectFieldAces', 'insertObjectFieldAce'],
        ];
    }

    public function testIsFieldGranted()
    {
        $sids = [new RoleSecurityIdentity('ROLE_FOO'), new RoleSecurityIdentity('ROLE_IDDQD')];
        $masks = [1, 2, 4];
        $strategy = $this->createMock('Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface');
        $acl = new Acl(1, new ObjectIdentity(1, 'foo'), $strategy, [], true);

        $strategy
            ->expects($this->once())
            ->method('isFieldGranted')
            ->with($this->equalTo($acl), $this->equalTo('foo'), $this->equalTo($masks), $this->equalTo($sids), $this->isTrue())
            ->willReturn(true)
        ;

        $this->assertTrue($acl->isFieldGranted('foo', $masks, $sids, true));
    }

    public function testIsGranted()
    {
        $sids = [new RoleSecurityIdentity('ROLE_FOO'), new RoleSecurityIdentity('ROLE_IDDQD')];
        $masks = [1, 2, 4];
        $strategy = $this->createMock('Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface');
        $acl = new Acl(1, new ObjectIdentity(1, 'foo'), $strategy, [], true);

        $strategy
            ->expects($this->once())
            ->method('isGranted')
            ->with($this->equalTo($acl), $this->equalTo($masks), $this->equalTo($sids), $this->isTrue())
            ->willReturn(true)
        ;

        $this->assertTrue($acl->isGranted($masks, $sids, true));
    }

    public function testSetGetParentAcl()
    {
        $acl = $this->getAcl();
        $parentAcl = $this->getAcl();

        $listener = $this->getListener(['parentAcl']);
        $acl->addPropertyChangedListener($listener);

        $this->assertNull($acl->getParentAcl());
        $acl->setParentAcl($parentAcl);
        $this->assertSame($parentAcl, $acl->getParentAcl());

        $acl->setParentAcl(null);
        $this->assertNull($acl->getParentAcl());
    }

    public function testSetIsEntriesInheriting()
    {
        $acl = $this->getAcl();

        $listener = $this->getListener(['entriesInheriting']);
        $acl->addPropertyChangedListener($listener);

        $this->assertTrue($acl->isEntriesInheriting());
        $acl->setEntriesInheriting(false);
        $this->assertFalse($acl->isEntriesInheriting());
    }

    public function testIsSidLoadedWhenAllSidsAreLoaded()
    {
        $acl = $this->getAcl();

        $this->assertTrue($acl->isSidLoaded(new UserSecurityIdentity('foo', 'Foo')));
        $this->assertTrue($acl->isSidLoaded(new RoleSecurityIdentity('ROLE_FOO', 'Foo')));
    }

    public function testIsSidLoaded()
    {
        $acl = new Acl(1, new ObjectIdentity('1', 'foo'), new PermissionGrantingStrategy(), [new UserSecurityIdentity('foo', 'Foo'), new UserSecurityIdentity('johannes', 'Bar')], true);

        $this->assertTrue($acl->isSidLoaded(new UserSecurityIdentity('foo', 'Foo')));
        $this->assertTrue($acl->isSidLoaded(new UserSecurityIdentity('johannes', 'Bar')));
        $this->assertTrue($acl->isSidLoaded([
            new UserSecurityIdentity('foo', 'Foo'),
            new UserSecurityIdentity('johannes', 'Bar'),
        ]));
        $this->assertFalse($acl->isSidLoaded(new RoleSecurityIdentity('ROLE_FOO')));
        $this->assertFalse($acl->isSidLoaded(new UserSecurityIdentity('schmittjoh@gmail.com', 'Moo')));
        $this->assertFalse($acl->isSidLoaded([
            new UserSecurityIdentity('foo', 'Foo'),
            new UserSecurityIdentity('johannes', 'Bar'),
            new RoleSecurityIdentity('ROLE_FOO'),
        ]));
    }

    /**
     * @dataProvider getUpdateAceTests
     */
    public function testUpdateAceThrowsOutOfBoundsExceptionOnInvalidIndex($type)
    {
        $this->expectException(\OutOfBoundsException::class);

        $acl = $this->getAcl();
        $acl->{'update'.$type}(0, 1);
    }

    /**
     * @dataProvider getUpdateAceTests
     */
    public function testUpdateAce($type)
    {
        $acl = $this->getAcl();
        $acl->{'insert'.$type}(new RoleSecurityIdentity('foo'), 1);

        $listener = $this->getListener([
            'mask', 'mask', 'strategy',
        ]);
        $acl->addPropertyChangedListener($listener);

        $aces = $acl->{'get'.$type.'s'}();
        $ace = reset($aces);
        $this->assertEquals(1, $ace->getMask());
        $this->assertEquals('all', $ace->getStrategy());

        $acl->{'update'.$type}(0, 3);
        $this->assertEquals(3, $ace->getMask());
        $this->assertEquals('all', $ace->getStrategy());

        $acl->{'update'.$type}(0, 1, 'foo');
        $this->assertEquals(1, $ace->getMask());
        $this->assertEquals('foo', $ace->getStrategy());
    }

    public function getUpdateAceTests()
    {
        return [
            ['classAce'],
            ['objectAce'],
        ];
    }

    /**
     * @dataProvider getUpdateFieldAceTests
     */
    public function testUpdateFieldAceThrowsExceptionOnInvalidIndex($type)
    {
        $this->expectException(\OutOfBoundsException::class);

        $acl = $this->getAcl();
        $acl->{'update'.$type}(0, 'foo', 1);
    }

    /**
     * @dataProvider getUpdateFieldAceTests
     */
    public function testUpdateFieldAce($type)
    {
        $acl = $this->getAcl();
        $acl->{'insert'.$type}('foo', new UserSecurityIdentity('foo', 'Foo'), 1);

        $listener = $this->getListener([
            'mask', 'mask', 'strategy',
        ]);
        $acl->addPropertyChangedListener($listener);

        $aces = $acl->{'get'.$type.'s'}('foo');
        $ace = reset($aces);
        $this->assertEquals(1, $ace->getMask());
        $this->assertEquals('all', $ace->getStrategy());

        $acl->{'update'.$type}(0, 'foo', 3);
        $this->assertEquals(3, $ace->getMask());
        $this->assertEquals('all', $ace->getStrategy());

        $acl->{'update'.$type}(0, 'foo', 1, 'foo');
        $this->assertEquals(1, $ace->getMask());
        $this->assertEquals('foo', $ace->getStrategy());
    }

    public function getUpdateFieldAceTests()
    {
        return [
            ['classFieldAce'],
            ['objectFieldAce'],
        ];
    }

    /**
     * @dataProvider getUpdateAuditingTests
     */
    public function testUpdateAuditingThrowsExceptionOnInvalidIndex($type)
    {
        $this->expectException(\OutOfBoundsException::class);

        $acl = $this->getAcl();
        $acl->{'update'.$type.'Auditing'}(0, true, false);
    }

    /**
     * @dataProvider getUpdateAuditingTests
     */
    public function testUpdateAuditing($type)
    {
        $acl = $this->getAcl();
        $acl->{'insert'.$type.'Ace'}(new RoleSecurityIdentity('foo'), 1);

        $listener = $this->getListener([
            'auditFailure', 'auditSuccess', 'auditFailure',
        ]);
        $acl->addPropertyChangedListener($listener);

        $aces = $acl->{'get'.$type.'Aces'}();
        $ace = reset($aces);
        $this->assertFalse($ace->isAuditSuccess());
        $this->assertFalse($ace->isAuditFailure());

        $acl->{'update'.$type.'Auditing'}(0, false, true);
        $this->assertFalse($ace->isAuditSuccess());
        $this->assertTrue($ace->isAuditFailure());

        $acl->{'update'.$type.'Auditing'}(0, true, false);
        $this->assertTrue($ace->isAuditSuccess());
        $this->assertFalse($ace->isAuditFailure());
    }

    public function getUpdateAuditingTests()
    {
        return [
            ['class'],
            ['object'],
        ];
    }

    /**
     * @dataProvider getUpdateFieldAuditingTests
     */
    public function testUpdateFieldAuditingThrowsExceptionOnInvalidField($type)
    {
        $this->expectException(\InvalidArgumentException::class);

        $acl = $this->getAcl();
        $acl->{'update'.$type.'Auditing'}(0, 'foo', true, true);
    }

    /**
     * @dataProvider getUpdateFieldAuditingTests
     */
    public function testUpdateFieldAuditingThrowsExceptionOnInvalidIndex($type)
    {
        $this->expectException(\OutOfBoundsException::class);

        $acl = $this->getAcl();
        $acl->{'insert'.$type.'Ace'}('foo', new RoleSecurityIdentity('foo'), 1);
        $acl->{'update'.$type.'Auditing'}(1, 'foo', true, false);
    }

    /**
     * @dataProvider getUpdateFieldAuditingTests
     */
    public function testUpdateFieldAuditing($type)
    {
        $acl = $this->getAcl();
        $acl->{'insert'.$type.'Ace'}('foo', new RoleSecurityIdentity('foo'), 1);

        $listener = $this->getListener([
            'auditSuccess', 'auditSuccess', 'auditFailure',
        ]);
        $acl->addPropertyChangedListener($listener);

        $aces = $acl->{'get'.$type.'Aces'}('foo');
        $ace = reset($aces);
        $this->assertFalse($ace->isAuditSuccess());
        $this->assertFalse($ace->isAuditFailure());

        $acl->{'update'.$type.'Auditing'}(0, 'foo', true, false);
        $this->assertTrue($ace->isAuditSuccess());
        $this->assertFalse($ace->isAuditFailure());

        $acl->{'update'.$type.'Auditing'}(0, 'foo', false, true);
        $this->assertFalse($ace->isAuditSuccess());
        $this->assertTrue($ace->isAuditFailure());
    }

    public function getUpdateFieldAuditingTests()
    {
        return [
            ['classField'],
            ['objectField'],
        ];
    }

    protected function getListener($expectedChanges)
    {
        $aceProperties = ['aceOrder', 'mask', 'strategy', 'auditSuccess', 'auditFailure'];

        $listener = $this->createMock('Doctrine\Persistence\PropertyChangedListener;');
        foreach ($expectedChanges as $index => $property) {
            if (\in_array($property, $aceProperties)) {
                $class = 'Symfony\Component\Security\Acl\Domain\Entry';
            } else {
                $class = 'Symfony\Component\Security\Acl\Domain\Acl';
            }

            $listener
                ->expects($this->at($index))
                ->method('propertyChanged')
                ->with($this->isInstanceOf($class), $this->equalTo($property))
            ;
        }

        return $listener;
    }

    protected function getAcl()
    {
        return new Acl(1, new ObjectIdentity(1, 'foo'), new PermissionGrantingStrategy(), [], true);
    }
}
