<?php
namespace App;

use Isbn\Isbn as Isbn;
use App\Config\MainConfig;

class IsbnExtractor
{
    private $isbnCheker;
    private $stringContaining;
    private $correctIsbns;
    private $wrongIsbns;
    
//    const ISBN10 = 10;
//    const ISBN13 = 13;

    function __construct(Isbn $isbnChecker) {    
        $this->isbnCheker = $isbnChecker; 
        $this->correctIsbns = [];
        $this->wrongIsbns = [];
    }

    public function setStringContaining (string $string) {
        $this->stringContaining = $string;
        if (strlen($string) >= MainConfig::ISBN10) $this->fetchIsbns();
    }

    public function getCorrectIsbns() :array {
        return $this->correctIsbns;
    }

    public function getWrongIsbns() :array {
        return $this->wrongIsbns;
    }
    
    public function getAllIsbns() :array {
        return array_merge($this->correctIsbns, $this->wrongIsbns);
    }

    public function reset() {
        unset($this->correctIsbns);
        unset($this->wrongIsbns);
        unset($this->stringContaining);
        $this->correctIsbns = [];
        $this->wrongIsbns = [];

    }

    /** заполняет массивы корректных и некорректных isbn, найденных в строке
        метод получился очень большим, но подробить его никак
        тесты проходит см /tests  **/
    private function fetchIsbns() {
        // разбиваем подстроку на массив, содержащий цифры и номера из позиций в строке
        $correctIsbns = [];
        $wrongIsbns = [];
        preg_match_all('!\d!', $this->stringContaining, $digitsAndItsPosArray, PREG_OFFSET_CAPTURE);
        $potentialIsbn = null;
        foreach ($digitsAndItsPosArray as $digitAndPos) {
            /** вот в этом цикле перебираем каждую цифру из кусочков descriprion_ru
                если оказывается, что из цифр таки складывается isdn, то сохраняем его.**/
            $firstDigitPos =  $digitAndPos[array_key_first($digitAndPos)][1]; 
            $subStrContDigits = substr($this->stringContaining, $firstDigitPos);

            /** такой цикл нужен, чтобы можно было вручную менять указать массива
                ниже в коде это будет использовано. был вариант итератором сделать **/
            while(list($key, $val) = each($digitAndPos)) {
                $potentialIsbn = null;
                $digit = $val;
                /** тут формируем массив, содержащий анализируемый на isbn кусок
                    для чего в массив добавляем сведения о следующей цифре, включающие
                    саму цифру и номер ее позиции в строке**/
                $potentialIsbnArray['digit'] = $digit[0];
                $potentialIsbnArray['pos'] = $digit[1];
                $array[] = $potentialIsbnArray;
                // из цифр, содержащихся в массиве собираем потенциальный isbn в строку
                foreach ($array as $key => $value) {$potentialIsbn .=  $value['digit'];};
                /** а тут выделяем подстроку, из анализируемой начинающуюся
                    с позии первой цифры и заканчивающуюся текущей цифрой**/
                $subStrContDigits = substr($this->stringContaining, $array[0]['pos'], $digit[1] - $array[0]['pos'] + 1);
                /** вот это условие оно для поиска isdn в конце строки, когда
                    нам выделять isbn нужно не с начала, а с конца строки**/
                if ($digitAndPos[array_key_last($digitAndPos)][1] == $digit[1]) {
                    if (strlen($potentialIsbn) == MainConfig::ISBN13) {
                        $subStrContDigits1 = substr($this->stringContaining, $array[3]['pos'], $array[12]['pos'] - $array[3]['pos'] + 1);
                    }
                    elseif (strlen($potentialIsbn) == MainConfig::ISBN10) {
                        $subStrContDigits1 = substr($this->stringContaining, $array[3]['pos'], $array[9]['pos'] - $array[3]['pos'] + 1);
                    }
                    else {$subStrContDigits1 = null;}
                    if (
                        !(preg_match('/[^-0-9]/', $subStrContDigits1)) and
                        $this->isbnCheker->validation->isbn(substr($potentialIsbn, -10, MainConfig::ISBN10))
                        ) { 
                            $this->correctIsbns[] = substr($potentialIsbn, -10, MainConfig::ISBN10);
                            array_shift($array);
                          }
                }
                /** а тут основная логика. анализирую последовательность цифр, которые составляют
                    потенциальный isbn. их 13**/
                if (strlen($potentialIsbn) == MainConfig::ISBN13) {
                    // для случая, если у нас окажется isbn10 мы выделяем подстроку содержащую 10 цифр
                    $subStrContDigits1 = substr($this->stringContaining, $array[0]['pos'], $array[9]['pos'] - $array[0]['pos']);
                    /** проверяем, что среди разделителей нет чего-либо кроме -
                        в случае, если кусок последовательности прошел проверку на isbn10, то запоминаем его **/
                    if ($this->checkIsbn10($subStrContDigits1, $potentialIsbn)) {
                             $this->correctIsbns[] = substr($potentialIsbn, 0, MainConfig::ISBN10);
                             /** при этом отматываем указатель массива на 3 элемента назад 
                                 нужно это в связи с тем, что если в последовательности
                                 из 13 проанализированных цифр, мы нашли isbn10
                                 то следующий анализ надо чать десятой цифры, а не с 13й**/
                             prev($digitAndPos);
                             prev($digitAndPos);
                             prev($digitAndPos);
                             // и убираем из массива анализируемой последовательсности первую цифру
                             array_shift($array);
                     }
                    /** проверяем, что среди разделителей нет чего-либо кроме -
                        в случае, если кусок последовательности прошел проверку на isbn13, то запоминаем его **/
                    else if ($this->checkIsbn13($subStrContDigits, $potentialIsbn)) {
                        $this->correctIsbns[] = $potentialIsbn;
                        array_shift($array);
                    }
                    /** все остальные последовательнсти цифр считаем неправильными isbn **/
                    elseif (
                        !$this->isbnCheker->validation->isbn(substr($potentialIsbn, 0, MainConfig::ISBN10)) and
                        !$this->isbnCheker->validation->isbn($potentialIsbn)
                       ) {
                             $this->wrongIsbns[] = $potentialIsbn;
                             array_shift($array);
                       }
                }

            }
        }
    }
    
    private function checkIsbn13 (string $subStrContDigits, string $potentialIsbn) :bool 
        {
        if (
            !(preg_match('/[^-0-9]/', $subStrContDigits)) and
            !$this->isbnCheker->validation->isbn(substr($potentialIsbn, 0, MainConfig::ISBN10)) and
            $this->isbnCheker->validation->isbn($potentialIsbn)
            ) return true;
            else return false;
        }

    private function checkIsbn10 (string $subStrContDigits, string $potentialIsbn) :bool 
        {
        if (
            !(preg_match('/[^-0-9]/', $subStrContDigits)) and
            $this->isbnCheker->validation->isbn(substr($potentialIsbn, 0, MainConfig::ISBN10)) and
            !$this->isbnCheker->validation->isbn($potentialIsbn) 
            ) return true;
            else return false;
        }

}
