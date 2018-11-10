<?php

namespace App;

use Exception;

class Logger
{
    protected $message = '';

    private $logType = 'info';
    private $directory = 'logs';
    private $logFile = '';
    private $logExtension = '.txt';

    /**
     * @param $message
     *
     * @throws Exception
     */
    public function addError($message)
    {
        $this->message = $message;
        $this->logFile = getenv('LOG_ERROR_FILENAME') ?: 'errors';
        $this->logType = 'error';

        $this->writeLine();
    }

    /**
     * @param $message
     *
     * @throws Exception
     */
    public function addWarning($message)
    {
        $this->message = $message;
        $this->logFile = getenv('LOG_WARNING_FILENAME') ?: 'errors';
        $this->logType = 'warning';

        $this->writeLine();
    }

    /**
     * @throws Exception
     */
    public function writeLine()
    {
        $message = PHP_EOL.'['.$this->logType.']::'.date('H:i:s d-m-Y').'::'.$this->message;

        file_put_contents($this->filePath(), $message, FILE_APPEND);

        return $this;
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function filePath()
    {
        $logDirectoryPath = __DIR__.'/../'.$this->directory;

        if(!$logDirectoryPath){
            throw new Exception('Log directory `'.$logDirectoryPath.'` is empty! Please create it.');
        }

        return __DIR__.'/../'.$this->directory.'/'.$this->logFile.$this->logExtension;
    }
}