<?php
namespace App;

use Isbn\Isbn as Isbn;

class IsbnExtractor
{
    private $isbnCheker;
    private $stringContaining;
    private $correctIsbns;
    private $wrongIsbns;


    function __construct(Isbn $isbnChecker) {    
//    function __construct(Isbn $isbnChecker, string $string) {    
        $this->isbnCheker = $isbnChecker; 
        $this->correctIsbns = [];
        $this->wrongIsbns = [];
    }

    public function setStringContaining (string $string) {
        $this->stringContaining = $string;
        if (strlen($string) >= 10) $this->fetchIsbns();
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

    // заполняет массивы корректных и некорректных isbn, найденных в строке
    private function fetchIsbns() {
        // разбиваем подстроку на массив, содержащий цифры и номера из позиций в строке
        $correctIsbns = [];
        $wrongIsbns = [];
        preg_match_all('!\d!', $this->stringContaining, $digitsAndItsPosArray, PREG_OFFSET_CAPTURE);
        $potentialIsbn = null;
        foreach ($digitsAndItsPosArray as $digitAndPos) {
            /* вот в этом цикле перебираем каждую цифру из кусочков descriprion_ru
               если оказывается, что из цифр таки складывается isdn, то сохраняем его.
            */
            $firstDigitPos =  $digitAndPos[array_key_first($digitAndPos)][1]; 
            $cut = false;
            foreach ($digitAndPos as $digit) {
                $potentialIsbn .= $digit[0];
                /* если в предыдущей ротации уже нашли isbn, то первым
                   символом подстроки, содержащим анализируемые данные
                   считаем следующий содержащий цифру
                */
                if ($cut) $firstDigitPos = $digit[1];
                $subStrContDigits = substr($this->stringContaining, $firstDigitPos, $digit[1] - $firstDigitPos);
                /* если найденная цифровая комбинация проходит проверку как isbn
                   и содержит только лишь - и цифры, то считаем такой isbn корректным
                */
                if (($this->isbnCheker->validation->isbn($potentialIsbn)) and !(preg_match('/[^-0-9]/', $subStrContDigits))) { 
                    $this->correctIsbns[] = $potentialIsbn;
                    $potentialIsbn = null;
                    $cut = true;
                }
                elseif (strlen($potentialIsbn) == 10 and count($digitAndPos) < 13) {
                    $this->wrongIsbns[] = $potentialIsbn; 
                    $cut = true;
                }
                elseif (strlen($potentialIsbn) == 13) {
                    $this->wrongIsbns[] = $potentialIsbn; 
                    $cut = true;
                }
                else { 
                    $cut = false;
                }
            }
        }
    }
}
