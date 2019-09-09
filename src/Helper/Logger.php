<?php
namespace App\Helper;

use Exception;
use App\Helper\FileNameGenerator;
use DateTime;

class Logger extends \Keboola\Csv\CsvWriter
{

    const LOGFILEEXT = 'csv';
    const LOGFILEPATH = 'logs';
    const DATETIMEFORMAT = 'Y-m-d H:i:s';
    
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
        $this->logFile = $this->fileNameGenerator->getRandomFileName(self::LOGFILEPATH, self::LOGFILEEXT);
        return $this->logFile;
    }
    
    public function writeLog (array $row) 
    {
        $this->rowId++;
        $date = new DateTime();
        $dateStr = $date->format(self::DATETIMEFORMAT);
        array_unshift($row, $this->rowId, $dateStr);
        
        $this->writeRow($row);
    }

}
    

