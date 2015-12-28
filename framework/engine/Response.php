<?php

namespace framework\engine;

use framework\engine\PrototypeTemplate;
use framework\engine\registry\FrameworkRegistry;
use framework\engine\utils\BaseService;
use framework\exceptions as FrmworkExcep;

/**
* Обеспечивает формирование и отправку клиенту ответа.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class Response 
{
    private $currentCode = 200;     //@var int код ответа 
    private $headers     = array(); //@var array заголовки 
    private $content;               //@var string контент 
    private static $msgForInvalidArgExcp;//@var string сообщение для исключений
              
    public static $enumCodes =  array(  100 => 'Continue',
                                        101 => 'Switching Protocols',
                                        200 => 'OK',
                                        201 => 'Created',
                                        202 => 'Accepted',
                                        203 => 'Non-Authoritative Information',
                                        204 => 'No Content',
                                        205 => 'Reset Content',
                                        206 => 'Partial Content',
                                        300 => 'Multiple Choices',
                                        301 => 'Moved Permanently',
                                        302 => 'Found',  
                                        303 => 'See Other',
                                        304 => 'Not Modified',
                                        305 => 'Use Proxy',
                                        307 => 'Temporary Redirect',
                                        400 => 'Bad Request',
                                        401 => 'Unauthorized',
                                        402 => 'Payment Required',
                                        403 => 'Forbidden',
                                        404 => 'Not Found',
                                        405 => 'Method Not Allowed',
                                        406 => 'Not Acceptable',
                                        407 => 'Proxy Authentication Required',
                                        408 => 'Request Timeout',
                                        409 => 'Conflict',
                                        410 => 'Gone',
                                        411 => 'Length Required',
                                        412 => 'Precondition Failed',
                                        413 => 'Request Entity Too Large',
                                        414 => 'Request-URI Too Long',
                                        415 => 'Unsupported Media Type',
                                        416 => 'Requested Range Not Satisfiable',
                                        417 => 'Expectation Failed',
                                        500 => 'Internal Server Error',
                                        501 => 'Not Implemented',
                                        502 => 'Bad Gateway',
                                        503 => 'Service Unavailable',
                                        504 => 'Gateway Timeout',
                                        505 => 'HTTP Version Not Supported',
                                        507 => 'Insufficient Storage',
                                        509 => 'Bandwidth Limit Exceeded' );
     
    public function __construct($content = '', $code = 200, $headers = array()) {
        // устанавливаем свойства класса
        $this->setContent($content);
        $this->setCode($code);
        $this->headers  = $headers;
        if ( ! self::$msgForInvalidArgExcp ) {
            self::$msgForInvalidArgExcp  = 'Неверный аргумент метода.';
        }
    }
    
   /**
    * Установка кода ответа.
    * @param int $code - код
    */
    public function setCode( $code ) 
    {
        if ( ! array_key_exists($code, self::$enumCodes) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$code', 'int');
        }
        $this->currentCode  = $code;
    }
    
    /**
    * Отправка кода.
    */
    private function sendCode() 
    {
        http_response_code($this->currentCode); 
    }
    
   /**
    * Возвращает описание заданного текущего кода.
    * 
    * @return string
    */
    private function getMessageForCode( ) 
    {
        if ( ! array_key_exists($this->currentCode, self::$enumCodes) ) {
            $this->setCode(200);
        }
        return self::$enumCodes[$this->currentCode];
    }
    
    /**
    * Устанавливает контент.
    * 
    * @param string содержимое
    */
    private function setContent( $content )
    {
        $this->content = (string) $content;
    }
    
    private function sendContent( ) 
    {
        if (is_string( $this->content ) && strlen( trim ($this->content) ) >= 1 ) {
            echo $this->content;
        }
    }
    
    /**
    * Устанавливает заголовок.
    * 
    * @param string $name  имя
    * @param string $value значение
    */
    private function setHeader($name, $value)
    {
        if ( BaseService::isNotString( $name ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$name', 'string');
        }
        if ( is_null($value) ) {
             throw new FrmworkExcep\NullReferenceException ('Попытка передачи null.', Response::class, __METHOD__, '$value');
        }
        $this->headers[$name] = (string)$value;
    }
     
    /**
    * Устанавливает заголовок Content-Type.
    * 
    * @param string $type  тип
    * @param string $charsetVal формат
    */
    public function setContentType( $type = 'text/html', $charsetVal = 'UTF-8')
    {
        if ( BaseService::isNotString( $type ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$type', 'string');
        }
        if ( BaseService::isNotString( $charsetVal ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$charsetVal', 'string');
        }
        $this->setHeader( 'Content-Type',  $type . ($charsetVal ? '; charset=' . $charsetVal : ''));
    }
    
   /**
    * Отправка заголовков.
    */
    private function sendHeaders() 
    {
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value, false, $this->currentCode);
        }
    }
    
   /**
    * Отправка заданного пользователем заголовка. 
    * 
    * @param string $headerVal  заголовок
    * @param int $code код
    */
    public function sendHeader($headerVal, $code = 200) 
    {
        if ( BaseService::isNotString( $headerVal ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$headerVal', 'string');
        }
        $this->setCode($code);
        header($headerVal, false, $this->currentCode);
    }
    
   /**
    * Формирование контента для отправки. 
    * 
    * @param string $nameTemplateView  имя шаблона
    * @param array $arrViewData массив с данными для вставки в шаблон
    * @return string контент для отправки.
    */
    public function preparePage( $nameTemplateView, $arrViewData =  array() ) 
    {
        $this->setContent((new PrototypeTemplate($nameTemplateView, $arrViewData))->generateView());
        return $this;
    }
    
    /**
    * Отправка сформированной страницы клиенту. 
    */
    public function sendPage() 
    {
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->send();
    }
    
    /**
    * Отправка ответа клиенту. 
    */
    private function send() 
    {
        $this->sendCode();       
        $this->sendHeaders();
        $this->sendContent();
    }
    
    /**
    * Редирект на стартовую страницу приложения. 
    */
    public function redirectToStartPage()
    {
        $baseUrl = (BaseService::strEndsWith(FrameworkRegistry::getBaseUrl(), '/'))
                   ? FrameworkRegistry::getBaseUrl()
                   : FrameworkRegistry::getBaseUrl() . '/';
        $this->redirect($baseUrl . FrameworkRegistry::getStartRoute());
    }
    
    /**
    * Редирект на страницу с описанием сгенерированого исключения. 
    */
    public function redirectToExcepPage()
    {
        $this->redirect(FrameworkRegistry::getExcepPage());
    }
    
    /**
    * Редирект на указанную страницу.
    * 
    * @param string $url  адрес 
    * @param int $code  код  
    */
    public function redirect($url, $code = 302)
    {
        if ( BaseService::isNotString( $url ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$url', 'string');
        }
        $this->setCode($code);
        $this->setHeader('Location', $url);
        $this->setContent('');
        $this->send();
    }
    
   /**
    * Выгрузка файла для клиента.
    * 
    * @param string $fileName  файл
    * @param string $mimetype  формат 
    */
    public function downloadFile($fileName, $mimetype='application/octet-stream')
    {
        if ( BaseService::isNotString( $fileName ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$fileName', 'string');
        }
        if ( ! file_exists($fileName) ) {
            $this->setCode(404);
            $this->sendHeader($_SERVER["SERVER_PROTOCOL"] . ' ' . $this->currentCode . ' ' . $this->getMessageForCode(), $this->currentCode);
            return;
        }
        $this->setCode(200);
        $this->setHeader('Content-Description', 'File Transfer');
        $this->setContentType($mimetype, null);
        $this->setHeader('Content-Transfer-Encoding', 'binary');
        $this->setHeader('Expires', '0');
        $this->setHeader('Cache-Control', 'must-revalidate');
        $this->setHeader('Pragma', 'public');
        // Дата последней модификации файла:      
        $this->setHeader('Last-Modified', gmdate('r', filemtime($fileName)));
        //  Отправляем уникальный идентификатор документа, значение которого меняется при его изменении:
        $this->setHeader('ETag', sprintf('%x-%x-%x', fileinode($fileName), filesize($fileName), filemtime($fileName)));
        $this->setHeader('Content-Disposition', 'attachment; filename="' . basename($fileName) . '";');
        $this->setContent('');
        $this->send();
        
        readfile($fileName);
    }
    
    /**
     * Отключение кэширования у браузера клиента.
     */
    public function nocache() 
    {
        $this->setCode(200);
        $this->setHeader('Expires',       'Thu, 19 Feb 1998 13:24:18 GMT');
        $this->setHeader('Last-Modified', gmdate("D, d M Y H:i:s") . ' GMT');
        $this->setHeader('Cache-Control', 'no-cache, must-revalidate, post-check=0, pre-check=0, max-age=0');
        $this->setHeader('Pragma',        'no-cache');
        $this->setContent('');
        $this->send();
    }
        
    public function setCookie($name, $value, $time, $path = '', $domain = '', $secure = null, $httpOnly = null)
    {
        if ( BaseService::isNotString( $name ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$name', 'string');
        }
        if ( is_null($value) ) {
            throw new FrmworkExcep\NullReferenceException ('Попытка передачи null.', Response::class, __METHOD__, '$value');
        }
        setcookie(  $name,
                    $value,
                    $time     != null ? \DateTime::from($time)->format('U') : 0,
                    $path     == null ? $this->cookiePath      : (string) $path,
                    $domain   == null ? $this->cookieDomain    : (string) $domain,
                    $secure   == null ? $this->cookieSecure    : (bool) $secure,
                    $httpOnly == null ? $this->cookieHttpOnly  : (bool) $httpOnly );
    }   
    
    public function deleteCookie($name, $path = '', $domain = '', $secure = null)
    {
        if ( BaseService::isNotString( $name ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Response::class, __METHOD__, '$name', 'string');
        }
        $this->setCookie( $name, '', 0, $path, $domain, $secure );
    }
   
}
