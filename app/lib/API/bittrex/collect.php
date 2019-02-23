<?php defined('_3AKOH') or die(header('/'));

include API.'collect_class.php';
class Collect extends Collect_parent{
    /**
     * Запрос к серверу до получения данных!
     * @param $command
     * @param array $myquery
     */
    public function __construct(){
        parent::__construct();
    }

}