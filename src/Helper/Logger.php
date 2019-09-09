<?php
namespace App\Helper;

use Exception;
use App\Helper\FileNameGenerator;
use App\Config\MainConfig;
use DateTime;

class Logger extends \Keboola\Csv\CsvWriter
{

    private $fileNameGenerator;
    private $logFile;
    private $rowId;
    
    function __construct(FileNameGenerator $fileNameGenerator, array $csvCaption) 
    {
        $this->fileNameGenerator = $fileNameGenerator;
        $this->createLogFIle();
        parent::__construct($this->logFile);
        $this->writeRow($csvCaption);
        $this->rowId = 0;
    }
    
    private function createLogFile () : string
    {
        $this->logFile = $this->fileNameGenerator->getRandomFileName(MainConfig::LOGFILEPATH, MainConfig::LOGFILEEXT);
        return $this->logFile;
    }
    
    public function writeLog (array $row) 
    {
        $this->rowId++;
        $date = new DateTime();
        $dateStr = $date->format(MainConfig::DATETIMEFORMAT);
        array_unshift($row, $this->rowId, $dateStr);
        
        $this->writeRow($row);
    }

}
    

