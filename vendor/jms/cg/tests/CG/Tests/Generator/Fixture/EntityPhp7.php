<?php

namespace CG\Tests\Generator\Fixture;

use \DateTime;

use CG\Tests\Generator\Fixture\SubFixture\Foo;
use CG\Tests\Generator\Fixture\SubFixture as Sub;

/**
 * Doc Comment.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class EntityPhp7
{
    /**
     * @var integer
     */
    private $id = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return EntityPhp7
     */
    public function setId(int $id = null): self
    {
        $this->id = $id;
        return $this;
    }

    public function getTime(): DateTime
    {
    }

    public function getTimeZone(): \DateTimeZone
    {
    }

    public function setTime(DateTime $time)
    {
    }

    public function setTimeZone(\DateTimeZone $timezone)
    {
    }

    public function setArray(array &$array = null): array
    {
    }

    public function getFoo(): Foo
    {
    }

    public function getBar(): Sub\Bar
    {
    }

    public function getBaz(): \CG\Tests\Generator\Fixture\SubFixture\Baz
    {
    }
}
