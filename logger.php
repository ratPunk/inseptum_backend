<?php
class Logger {
    private $logFile;
    private $logLevel;
    
    // Уровни логирования
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_DEBUG = 'DEBUG';
    
    public function __construct($logFile = 'app.log', $logLevel = self::LEVEL_INFO) {
        $this->logFile = $logFile;
        $this->logLevel = $logLevel;
    }
    
    public function log($message, $level = self::LEVEL_INFO) {
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] [$level] $message" . PHP_EOL;
        
        file_put_contents(
            $this->logFile, 
            $logMessage, 
            FILE_APPEND | LOCK_EX
        );
    }
    
    public function info($message) {
        $this->log($message, self::LEVEL_INFO);
    }
    
    public function warning($message) {
        $this->log($message, self::LEVEL_WARNING);
    }
    
    public function error($message) {
        $this->log($message, self::LEVEL_ERROR);
    }
    
    public function debug($message) {
        $this->log($message, self::LEVEL_DEBUG);
    }
}

// Использование
// $logger = new Logger('myapp.log');
// $logger->info('Приложение запущено');
// $logger->warning('Низкий запас памяти');
// $logger->error('Файл не найден');
// $logger->debug('Значение переменной $x = 42');
?>