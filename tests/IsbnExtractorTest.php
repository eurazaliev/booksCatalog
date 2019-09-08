<?php



class IsbnExtractorTest extends \PHPUnit\Framework\TestCase
{
    private $isbnExtractor;
    const WRONGISBNS = ['0123456780', '0123456789120'];
    const CORRECTISBNS = ['9111111119', '9000000000009', '9777777777779', '9222222229', '9555555555559'];

    protected function setUp() : void 
    {
//        $this->isbnExtractor = new App\IsbnExtractor();
    }
 
    protected function tearDown() : void 
    {
        $this->isbnExtractor = NULL;
    }

    public function testIsbnValidationStub()
    {
        $stub = $this
            ->getMockBuilder('Isbn\Validation')
            ->disableOriginalConstructor()
            ->setMethods(['isbn'])
            ->getMock();        
        $this->assertInstanceOf('Isbn\Validation', $stub);
        $stub->expects($this->any())
               ->method('isbn')
               ->will($this->returnCallback(
                   function ($isbn) {
                       if (in_array($isbn, self::CORRECTISBNS)) return true;
                       else return false;
                   }

               ));
        return $stub;
    }
    
    public function testIsbnCheckerStub()
    {
        $stub = $this
            ->getMockBuilder('Isbn\Isbn')
            ->disableOriginalConstructor()
            ->getMock();
        $stub->property = 'validation';
        $this->assertInstanceOf('Isbn\Isbn', $stub);
        $validationStub = $this->testIsbnValidationStub();
        $stub->validation = $validationStub;
        $this->assertInstanceOf('Isbn\Validation', $stub->validation);
//        var_dump(self::CORRECTISBNS[array_rand(self::CORRECTISBNS)]);
        $this->assertTrue($stub->validation->isbn(self::CORRECTISBNS[array_rand(self::CORRECTISBNS)]));
        $this->assertFalse($stub->validation->isbn(self::WRONGISBNS[array_rand(self::WRONGISBNS)]));
        $this->assertFalse($stub->validation->isbn('havetobefailed'));
        return $stub;

    }    
 
    public function testFetchIsbn()
    {
        $isbnChecker = $this->testIsbnCheckerStub();
        $extractor = new App\IsbnExtractor($isbnChecker);
        $this->assertInstanceOf('App\IsbnExtractor', $extractor);

        // для проверки положим корректных 3 isbn
        $testString = self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] . 
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] . 
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)];
        $this->assertNull($extractor->setStringContaining($testString));
        // в любом случае тут должен быть массив
        $this->assertIsArray($extractor->getAllIsbns());
        $this->assertGreaterThan(0, $extractor->getWrongIsbns());
        $this->assertCount(3, $extractor->getCorrectIsbns());
        $extractor->reset();

        // два некорректных и 1 корректный
        $testString = self::WRONGISBNS[0] .
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] .
                      self::WRONGISBNS[1];
        $this->assertNull($extractor->setStringContaining($testString));
        // в любом случае тут должен быть массив
        $this->assertGreaterThan(3, $extractor->getAllIsbns());
        $this->assertGreaterThan(2, $extractor->getWrongIsbns());
    
        $this->assertCount(1, $extractor->getCorrectIsbns());
        
        $extractor->reset();
        // у нас пробел не может быть разделителем, поэтому такое сечетание не будет isbn
        $testString = '123456789' . ' ' . 0;
        $this->assertNull($extractor->setStringContaining($testString));
        $this->assertCount(0, $extractor->getAllIsbns());
        $extractor->reset();
        
        // а если разделитель любой и в любом количестве, то засчитывается
        $testString = '1234' . '!@#plpOk' .'56789' . '-#$%^imdi~zll][{{}' . '0123';
        $this->assertNull($extractor->setStringContaining($testString));
        $this->assertGreaterThan(1, $extractor->getAllIsbns());
        $extractor->reset();
        /* причем, если в качестве разделителей используется хоть что-то кроме -
         * такой isbn все равно считается некорректным, даже если отдельно
         * цифры составляют корректный isbn */ 
        $testString = '9111111119';
        $this->assertNull($extractor->setStringContaining($testString));
        $this->assertCount(1, $extractor->getCorrectIsbns());
        $extractor->reset();

        $testString = '911-1-1111-1-9';
        $this->assertNull($extractor->setStringContaining($testString));
        $this->assertCount(1, $extractor->getCorrectIsbns());
        $extractor->reset();
        
        /* вот тут среди правильных разделителей мы поместили левый
         * что привело к тому, что строка перестала содержать корректные коды */
        $testString = '911-1-11#11-1-9';
        $this->assertNull($extractor->setStringContaining($testString));
        $this->assertCount(0, $extractor->getCorrectIsbns());
        $this->assertGreaterThan(1, $extractor->getWrongIsbns());
        $extractor->reset();

        // корректный вначале строки
        $testString = 
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] . // корректный
                      $this->generateTextString(rand(1, 50), '-абвгдежзиклмноasdfghkk!@#$%%^&&ZVMXBKEH')  
        ;
        $this->assertNull($extractor->setStringContaining($testString));
        $this->assertCount(1, $extractor->getCorrectIsbns());
        $this->assertGreaterThan(1, $extractor->getWrongIsbns());
        $extractor->reset();

        //в конце строки
        $testString = 
                      $this->generateTextString(rand(1, 50), '-абвгдежзиклмнопрсasdfghkk!@#$%%^&&ZVMXBKEH') .
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)]
        ;
        $this->assertNull($extractor->setStringContaining($testString));
        $this->assertCount(1, $extractor->getCorrectIsbns());
        $this->assertGreaterThan(1, $extractor->getWrongIsbns());
        $extractor->reset();

        //2 корректных в середине строки, разделенных некорректными
        $testString = 
                      $this->generateTextString(rand(1, 50), '-asdfghkk!@#$%%^&&ZVMXBKEH') .
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] .
                      $this->generateTextString(rand(1, 50), '-абвгдежзиклмнопрсasdfghkk!@#$%%^&&ZVMXBKEH') .
                      self::WRONGISBNS[array_rand(self::WRONGISBNS)] .
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] .
                      $this->generateTextString(rand(1, 50), '-asdfghkk!@#$%%^&&ZVMXBKEH')
        ;
        $this->assertNull($extractor->setStringContaining($testString));
        $this->assertCount(2, $extractor->getCorrectIsbns());
        $this->assertGreaterThan(1, $extractor->getWrongIsbns());
        $extractor->reset();
        
        /* возьмём что-нибудь по сложнее, безумный текст, содержащий
         * 1 некорректный и 2 корректных isbn */
        $testString = 
                      $this->generateTextString(rand(1, 10), 'asdfghkk!@#$%%^&&ZVMXBKEH') .
                      self::WRONGISBNS[array_rand(self::WRONGISBNS)] .
                      $this->generateTextString(rand(7, 15), 'aHSGDNNsdfghkk!@#$%%^&&ZVMXBKEH') .
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] .
                      $this->generateTextString(rand(7, 15), 'asdfghkk!@#$%%^&&ZVMXBKEH') . 
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)]
        ;
        $this->assertNull($extractor->setStringContaining($testString));

        $this->assertCount(2, $extractor->getCorrectIsbns());
        $this->assertGreaterThan(1, $extractor->getWrongIsbns());
        $extractor->reset();

        // вот тут еще в корректные вставим допустимые и недопустимые разделители
        $testString = 
                      '9-1-111-1111-9' . // корректный 1
                      $this->generateTextString(rand(1, 50), '-asdfghkk!@#$%%^&&ZVMXBKEH') .
                      self::WRONGISBNS[array_rand(self::WRONGISBNS)] . 
                      $this->generateTextString(rand(1, 50), '-aHSGDNNsdfghkk!@#$%%^&&ZVMXBKEH') .
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] . // корректный 2
                      '90-0000-0000-009' . // корректный 3
                      $this->generateTextString(rand(1, 50), 'asdfghkk!@#$%%^&&ZVMXBKEH') . 
                      self::CORRECTISBNS[array_rand(self::CORRECTISBNS)] . // корректный 4
                      $this->generateTextString(rand(1, 50), '-asdfghkk!@#$%%^&&ZVMXBKEH') .
                      '97-777!77777779'. 
                      self::WRONGISBNS[array_rand(self::WRONGISBNS)]
        ;
        $this->assertNull($extractor->setStringContaining($testString));

        $this->assertCount(4, $extractor->getCorrectIsbns());
        $this->assertGreaterThan(1, $extractor->getWrongIsbns());
        $extractor->reset();
    }
    
    private function generateTextString($length, $chars = "abdefhiknrstyzABDEFGHKNQRSTYZ23456789") 
    {
        //создадим случайную строку длиной $length из символов по-умолчанию из анг. букв и цифр
        $numChars = mb_strlen($chars);
        $string = '';
        for ($i = 1; $i <= $length; $i++) {
            $string .= mb_substr($chars, rand(1, $numChars) - 1, 1);
         }
        return $string;
    }

    
}
