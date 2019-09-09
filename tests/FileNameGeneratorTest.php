<?php

class FileNameGeneratorTest extends \PHPUnit\Framework\TestCase
{
    public function testFetchIsbn()
    {
        $randomFileNameGenerator = new App\Helper\FileNameGenerator;
        $this->assertInstanceOf('App\Helper\FileNameGenerator', $randomFileNameGenerator);
        $this->assertIsString($randomFileNameGenerator->getRandomFileName('nowhere', 'txt'));
    }
}
