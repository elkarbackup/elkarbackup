<?php

namespace CG\Tests\Generator;

use CG\Core\DefaultGeneratorStrategy;
use CG\Generator\DefaultVisitor;
use CG\Generator\PhpMethod;
use CG\Generator\PhpParameter;
use CG\Generator\PhpClass;
use CG\Generator\Writer;
use CG\Generator\PhpFunction;

class DefaultVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function testVisitFunction()
    {
        $writer = new Writer();

        $function = new PhpFunction();
        $function
            ->setName('foo')
            ->addParameter(PhpParameter::create('a'))
            ->addParameter(PhpParameter::create('b'))
            ->setBody(
                $writer
                    ->writeln('if ($a === $b) {')
                    ->indent()
                    ->writeln('throw new \InvalidArgumentException(\'$a is not allowed to be the same as $b.\');')
                    ->outdent()
                    ->write("}\n\n")
                    ->write('return $b;')
                    ->getContent()
            )
        ;

        $visitor = new DefaultVisitor();
        $visitor->visitFunction($function);

        $this->assertEquals($this->getContent('a_b_function.php'), $visitor->getContent());
    }

    public function testVisitMethod()
    {
        $method  = new PhpMethod();
        $visitor = new DefaultVisitor();

        $method
            ->setName('foo')
            ->setReferenceReturned(true);
        $visitor->visitMethod($method);

        $this->assertEquals($this->getContent('reference_returned_method.php'), $visitor->getContent());
    }

    public function testVisitMethodWithCallable()
    {
        if (PHP_VERSION_ID < 50400) {
            $this->markTestSkipped('`callable` is only supported in PHP >=5.4.0');
        }

        $method    = new PhpMethod();
        $parameter = new PhpParameter('bar');
        $parameter->setType('callable');

        $method
            ->setName('foo')
            ->addParameter($parameter);

        $visitor = new DefaultVisitor();
        $visitor->visitMethod($method);

        $this->assertEquals($this->getContent('callable_parameter.php'), $visitor->getContent());
    }

    public function testVisitClassWithPhp7Features()
    {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped('Test only valid for PHP >=7.0');
        }

        $ref = new \ReflectionClass('CG\Tests\Generator\Fixture\EntityPhp7');
        $class = PhpClass::fromReflection($ref);

        $generator = new DefaultGeneratorStrategy();
        $content = $generator->generate($class);


        $this->assertEquals($this->getContent('php7_class.php'), $content);
    }


    /**
     * @dataProvider visitFunctionWithPhp7FeaturesDataProvider
     */
    public function testVisitFunctionWithPhp7Features($filename, $function)
    {
        if (PHP_VERSION_ID < 70000) {
            $this->markTestSkipped('Test only valid for PHP >=7.0');
        }

        $visitor = new DefaultVisitor();
        $visitor->visitFunction($function);

        $this->assertEquals($this->getContent($filename.'.php'), $visitor->getContent());

    }

    public function visitFunctionWithPhp7FeaturesDataProvider()
    {
        $builtinReturn = PhpFunction::create('foo')
                            ->setReturnType('bool');
        $nonbuiltinReturn = PhpFunction::create('foo')
                            ->setReturnType('\Foo');


        return array(
            array('php7_builtin_return', $builtinReturn),
            array('php7_func_nonbuiltin_return', $nonbuiltinReturn),
        );
    }

    /**
     * @param string $filename
     */
    private function getContent($filename)
    {
        if (!is_file($path = __DIR__.'/Fixture/generated/'.$filename)) {
            throw new \InvalidArgumentException(sprintf('The file "%s" does not exist.', $path));
        }

        return file_get_contents($path);
    }
}
