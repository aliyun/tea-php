<?php

namespace AlibabaCloud\Dara\XML\Tests;

use DateTime;
use AlibabaCloud\Dara\File;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 * @method void setUp()
 * @method void tearDown()
 */
class FileTest extends TestCase
{

    private $testFilePath;
    private $emptyFilePath;
    private $newFilePath;
    private $testFileContent = 'This is a test file';
    
    /**
     * @before
     */
    protected function initialize()
    {
        // 在测试前设置一个临时文件
        $this->testFilePath = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($this->testFilePath, $this->testFileContent);

        $this->emptyFilePath = tempnam(sys_get_temp_dir(), 'empty');
        file_put_contents($this->emptyFilePath, '');

        $this->newFilePath = tempnam(sys_get_temp_dir(), 'new');
        file_put_contents($this->newFilePath, '');
    }

    /**
     * @after
     */
    protected function cleanup()
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }

        if (file_exists($this->emptyFilePath)) {
            unlink($this->emptyFilePath);
        }
    }

    public function testPath()
    {
        $file = new File($this->testFilePath);
        $this->assertEquals($this->testFilePath, $file->path());
    }

    public function testCreateTime()
    {
        $file = new File($this->testFilePath);
        $expected = new DateTime();
        $expected->setTimestamp(filectime($this->testFilePath));
        $this->assertEquals($expected->format('Y-m-d H:i:s'), $file->createTime()->format('YYYY-MM-DD HH:mm:ss'));

    }

    public function testModifyTime()
    {
        $file = new File($this->testFilePath);
        $expected = new DateTime();
        $expected->setTimestamp(filemtime($this->testFilePath));

        $this->assertEquals($expected->format('Y-m-d H:i:s'), $file->modifyTime()->format('YYYY-MM-DD HH:mm:ss'));

    }

    public function testLength()
    {
        $file = new File($this->testFilePath);
        $this->assertEquals(strlen($this->testFileContent), $file->length());
    }

    public function testRead()
    {
        $file = new File($this->testFilePath);
        
        $text1 = $file->read(4);
        $this->assertEquals('This', $text1);
        
        $text2 = $file->read(4);
        $this->assertEquals(' is ', $text2);

        $emptyFile = new File($this->emptyFilePath);
        $empty = $emptyFile->read(10);
        $this->assertNull($empty);
        
        $emptyFile->close();
    }

    public function testWrite()
    {
        $file = new File($this->testFilePath);
        $length = $file->length();
        $file->write(' Test');

        $modifyTimeAfterWrite = $file->modifyTime();
        $expectedModifyTime = filemtime($this->testFilePath);
        $this->assertEquals($expectedModifyTime, $modifyTimeAfterWrite->unix());
        
        $lengthAfterWrite = filesize($this->testFilePath);
        $this->assertEquals($length + 5, $lengthAfterWrite);

        $file->close();

        // 创建并测试新文件
        $newFile = new File($this->newFilePath);
        $newFile->write('Test');
        $textAfterWrite = $newFile->read(4);

        $this->assertEquals('Test', $textAfterWrite);
        $newFile->write(' Test2');
        $textAfterWrite = $newFile->read(4);
        $this->assertEquals(' Tes', $textAfterWrite);
        $textAfterWrite = $newFile->read(4);
        $this->assertEquals('t2', $textAfterWrite);
        $newFile->write(' Test3');
        $textAfterWrite = $newFile->read(3);
        $this->assertEquals(' Te', $textAfterWrite);
        $textAfterWrite = $newFile->read(10);
        $this->assertEquals('st3', $textAfterWrite);
        $newFile->close();
    }

    public function testExists()
    {
        $this->assertTrue(File::exists($this->testFilePath));
        $this->assertFalse(File::exists('some_non_existent_file.txt'));
    }

    public function testCreateStream()
    {
        $file = new File($this->testFilePath);
        $length = $file->length();
        $stream = File::createWriteStream($this->testFilePath);
        $this->assertInstanceOf(Stream::class, $stream);
        $stream->write('12345');
        $this->assertEquals($length + 5, $stream->getSize());
        $stream = File::createReadStream($this->testFilePath);
        $data = $stream->read(5);
        $this->assertEquals('This ', $data);
    }
}