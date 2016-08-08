<?php

namespace Droid\Test\Transform;

use org\bovigo\vfs\vfsStream;

use Droid\Transform\FileTransformer;

class FileTransformerTest extends \PHPUnit_Framework_TestCase
{
    protected $transformer;
    protected $vfs;

    protected function setUp()
    {
        $this->transformer = new FileTransformer;
        $this->vfs = vfsStream::setup();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage value is not a string path to a file
     */
    public function testTransformFailsIfValueIsNotAString()
    {
        $this->transformer->transform(0);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage value is not a non-empty string path to a file
     */
    public function testTransformFailsIfValueIsANonEmptyString()
    {
        $this->transformer->transform('');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage value is not a path to an existing file
     */
    public function testTransformFailsIfValueIsNotAPathToAFileWhichExists()
    {
        $this->transformer->transform(vfsStream::url('root/not-exist'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage value is not a path to a file
     */
    public function testTransformFailsIfValueIsNotAPathToAFile()
    {
        $this->transformer->transform(vfsStream::url('root'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage value is not a path to a readable file
     */
    public function testTransformFailsIfValueIsNotAPathToAReadableFile()
    {
        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->chmod(0222)
        ;
        $this->transformer->transform(vfsStream::url('root/some_file'));
    }

    public function testTransformPathToEmptyFileIntoEmptyStringContent()
    {
        vfsStream::newFile('some_file')
            ->at($this->vfs)
        ;

        $this->assertSame(
            '',
            $this->transformer->transform(vfsStream::url('root/some_file'))
        );
    }

    public function testTransformPathToFileIntoStringContent()
    {
        $expected = 'I am Ironhide. Stupid human!';
        vfsStream::newFile('some_file')
            ->at($this->vfs)
            ->setContent($expected)
        ;

        $this->assertSame(
            $expected,
            $this->transformer->transform(vfsStream::url('root/some_file'))
        );
    }
}
