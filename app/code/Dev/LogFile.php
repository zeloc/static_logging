<?php
namespace Dev;

/**
 * Only logs string data or single dimension arrays
 * Set paths and log file name in the config variable
 * Base path is to the root dir of site format '/var/www/html/test/'
 * Log file name is just a string name eg zlog.log
 * The log file will get rendered in /var/log/
 *
 * \Dev\LogFile::logFile($data);
 * \Dev\LogFile::logTrace();
 *
 */
class LogFile
{
    public static $basePathVar;
    public static $logFileVar;
    public static $logPath = '/var/log/';
    public static $config = [
        'base_path' => '/var/www/html/ecc_mage/',
        'log_file' => 'zdebug.log'
    ];

    /**
     * @param $data
     * @param $typeShow
     * @return false|void
     */
    public static function logFile($data, $typeShow = true)
    {
        if(self::isConfigSet()){
            self::$basePathVar = self::$config['base_path'];
            self::$logFileVar = self::$config['log_file'];;
        }
        if (!self::$basePathVar || !self::$logFileVar) {
            return false;
        }

        if (is_string($data) || is_integer($data)) {
            self::stringDataOut($data, $typeShow);
        }
        if(is_object($data)){
            self::stringDataOut('object', $typeShow);
        }
        if(is_array($data)){
            $newArray = [];
            $loop = 0;
            foreach($data as $key => $value){
                if(is_object($value) ){
                    continue;
                }
                if((is_array($value) && !self::isComplexArray($value))
                    || (is_string($value) || is_int($value) || is_bool($value))
                ){
                    $newArray[$key] = $value;
                }else{
                    $newArray[$key] = 'complex type';
                }

                $loop++;
            }
            self::stringDataOut(print_r($newArray, true), $typeShow);
        }

    }

    public static function isComplexArray($testArray, $check = 0)
    {
        if(is_object($testArray)){
            return true;
        }
        if($check > 20){
            return true;
        }
        if(is_array($testArray)){
            foreach($testArray as $value){
                $check ++;
                if(is_object($value)){
                    return true;
                }
                if(is_array($value) && self::isComplexArray($value, $check)){
                    return true;
                }
            }
        }
    }

    public static function logTrace()
    {
        try {
            throw new \Exception('test trace');
        } catch (\Exception $e) {
            self::logFile($e->getTraceAsString(), false);
        }
    }

    public static function isConfigSet()
    {
        $basePath = self::$config['base_path']?? false;
        $logfile = self::$config['log_file']?? false;
        if(!$basePath || !$logfile){
            return false;
        }
        return is_dir($basePath);
    }

    public static function stringDataOut($data, $typeShow)
    {
        $dataOut = self::getTimeStamp() . ' :: ' . print_r($data, true) . PHP_EOL;
        self::printLine($dataOut);
        if($typeShow){
            $dataType = self::getTimeStamp() . ' >> Type is: string' . PHP_EOL;
            self::printLine($dataType);
        }
    }

    public static function arrayDataOut($data)
    {
        if(self::isSingleArray($data)){
            self::printLine(printf($data, true));
        }else{
            $arrayData = self::getTimeStamp() . ' :: array' . PHP_EOL;
            $arrayData .= '[' . PHP_EOL;
            foreach ($data as $key => $arrayValue) {
                $arrayData .= '   [' . $key . ']' . ' => ' . $arrayValue . ' ___Type: ' . gettype($arrayValue) . PHP_EOL;
            }
            $arrayData .= ']' . PHP_EOL;

            self::printLine($arrayData);
        }

    }

    public static function getLogPath()
    {
        if (self::isValidData()) {
            return self::$basePathVar . self::$logPath . self::$logFileVar;
        }
    }

    public static function isValidData()
    {
        return self::$basePathVar && self::$logFileVar;
    }

    public static function printLine($dataOut)
    {
        if (self::isValidData()) {
            file_put_contents(self::getLogPath(), $dataOut, FILE_APPEND);
        }
    }

    /**
     * checks if the data is a single dimension array
     * @param $array
     * @return bool
     */
    public static function isSingleArray($array)
    {
        return is_array($array) && (count($array) === count($array, COUNT_RECURSIVE));
    }

    /**
     * checks if the data values in the array are a printable type
     * @param $array
     * @return bool
     */
    public static function isValidArray($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $valid = true;
        foreach ($array as $value) {
            if (is_array($value) || is_object($value)) {
                self::printLine('array data invalid contains array or object');
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Adds the time date stamp
     * @return string
     */
    public static function getTimeStamp()
    {
        $date = new \DateTime();
        return $date->format("y:m:d h:i:s");
    }

    public static function uploadXmlMessage($user, $pass, $responderUrl, $xmlStringData = '')
    {
        $username= $user;
        $password= $pass;
        $URL=$responderUrl;

        $postFields = $xmlStringData;
        if(!$postFields){
            self::logFile('Unable to upload xml, no data');
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$URL);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); //timeout after 30 seconds
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $result=curl_exec ($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);   //get status code
        curl_close ($ch);

        $curl = curl_init();
    }
}
