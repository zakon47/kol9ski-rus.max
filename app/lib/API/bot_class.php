<?php

#TELEGRAM BOT
class Bot{
    private $token = '500276934:AAGbfY50dFB4PHKKYWVIMNa-AlPAV8h8bJ4';       //текущий токен
    private $address = ['br.max'=>'-1001266097221'];                        //адреса каналов и групп
    public function __construct(){}
    protected function query($method,$param=[]){
//        $url = "https://api-tg.tarsy.club/bot";       //если это ТУНЕЛЬ - то используем + перед ссылкой
        $url = "https://clickster.pro/bot";       //если это ТУНЕЛЬ - то используем + перед ссылкой
        $url.= $this->token;                 //токен бота
        $url.= '/'.$method;                  //вызываемый метод
        if(!empty($param)) $url.= "?".http_build_query($param);     //добавляем параметры
        $result = file_get_contents($url);
        return json_decode($result,1);
    }
    /**
     * Получить все сообщения от других людей - то что написали боту
     * @return mixed
     */
    public function getUpdates($chat_id){
        if(is_string($chat_id)) $chat_id = $this->address[$chat_id];    //получили ID для чата
        return $this->query('getUpdates',[
            'chat_id' => $chat_id,
        ]);
    }
    /**
     * Отправить сообщение в определенное место
     * @param $chat_id
     * @param $text
     * @return mixed
     */
    public function sendMessage($chat_id,$text){
        if(is_string($chat_id)) $chat_id = $this->address[$chat_id];    //получили ID для чата
        return $this->query('sendMessage',[
            'chat_id' => $chat_id,
            'parse_mode' => 'html',
            'text' => $text
        ]);
    }
    public function deleteMessage($chat_id,$message_id){
        if(is_string($chat_id)) $chat_id = $this->address[$chat_id];    //получили ID для чата
        return $this->query('deleteMessage',[
            'chat_id' => $chat_id,
            'message_id' => $message_id
        ]);
    }
    public function cleanMessage($chat_id){
        if(is_string($chat_id)) $chat_id = $this->address[$chat_id];    //получили ID для чата
        $message = $this->sendMessage('br.max','test');
        $message = $this->getUpdates();
    }
}