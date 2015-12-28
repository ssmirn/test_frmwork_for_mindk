<?php

namespace framework\engine;
use framework\exceptions as FrmworkExcep;

/**
* Прототип шаблонизатора.
* Заменяет маркеры (заключаются в {}) в файлах-шаблонах соответствующими 
* значениями из передаваемого в конструктор  массива $arrViewData.
* Шаблонизатор также может включать в main-шаблон другие файлы.
*  
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class PrototypeTemplate 
{
    private $arrData = array();//@var array массив с данными для подстановки в шаблон 
    
    function __construct($nameTemplateView, $arrViewData =  array()) 
    {
        $fileName = '..'    . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 
                    'views' . DIRECTORY_SEPARATOR . "{$nameTemplateView}.html";
        if ( !file_exists( $fileName ) ) {
           throw new FrmworkExcep\FileNotFoundException ('Отсутствует файл шаблона: "' . $fileName .  '" .', PrototypeTemplate::class, __METHOD__);
        }
        if( ! is_array($arrViewData) ){
            throw new FrmworkExcep\InvalidArgumentException('В шаблон необходимо передать массив.', PrototypeTemplate::class, __METHOD__, '$arrViewData', 'array');
        }
        //имена файлов сохраняються с ключом 'file',
        //а имена маркеров с ключом 'content':
        $this->arrData['main'] = array('file' => $fileName);
        foreach ($arrViewData as $key => $val){
            $this->arrData[$key] = array('content' => $val);
        }
    }
    
   /**
    * Возвращает результирующий файл с вставленными данными
    * вместо {маркеров}.
    * 
    * @param string $subset наименование ключа в arrData. В зависимости от значения
    *                       работаем с файлом  или {маркером}.
    * @return string   
    */
    public function generateView($subset = 'main') 
    {
        $noParse = false;
        $content = '';
        $tmpFile = $this->arrData[$subset]['file'];
        if( $tmpFile ) {
            //имеем дело с переданным в метод файлом *.html:
            $lenNameTmpFile = strlen($tmpFile);
            if($lenNameTmpFile > 5) {
                $extFile = substr($tmpFile, $lenNameTmpFile-5, 5);
            }
            if(strcasecmp($extFile, ".html") != 0) {
                // если файл не является html - выходим из метода:
                return $content;
            } else {
                //сохраняем в $content содержимое файла:
                $content = $this->fileRead($tmpFile);
            }
        } else {
            //имеем дело с переданным в метод {маркером}:
            if(isset($this->arrData[$subset]['content'])) {
                $content = $this->arrData[$subset]['content'];
            }
        }
        if( ! $noParse ) {
            // с помощью рекурсии обеспечиваем, чтобы КАЖДЫЙ маркер
            // шаблона был заменен данными в текущем вызове generateView() 
            // или в следующем вызове  generateView():
            $content = preg_replace("/{([A-Za-z_]*)}/e", "framework\\engine\\PrototypeTemplate::generateView('$1')", $content);
        }
        return $content;
    }
    
    /**
    * Считываем файл. Возвращаем содержимое файла.
    * 
    * @param string $nameFile наименование файла
    * @return string  содержимое файла 
    */
    private function fileRead ($nameFile)
    {
        $result = '';
        $stream = fopen($nameFile, "r");
        if(!$stream) {
            $result = "<!-- Ошибка загрузки '$nameFile' //-->";
        } else {
            $result = fread($stream, filesize($nameFile));
            fclose($stream);
        }
        return $result;
    }
    
}
