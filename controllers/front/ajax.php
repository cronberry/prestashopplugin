<?php


defined('_PS_VERSION_') or exit;


class CronberryIntegrationAjaxModuleFrontController extends ModuleFrontController
{
    public $ssl = true;
    protected $content_only = true;

    public function postProcess()
    {
    $cronberryModule = Module::getInstanceByName("cronberryIntegration");
    
        if (!$cronberryModule) {
            $this->ajaxDie(array(
                'errors' => array("Error: cronberry module should be enabled!"),
            ));
    }

     $token =  sha1($this->context->cookie->date_add._COOKIE_KEY_);
     if( $token != null && Tools::getValue('token')  == $token && Tools::getValue('fcm') && Tools::getValue('fcm') != ""){
       //save token to db 
       $results = Db :: getInstance()->ExecuteS("select * from "._DB_PREFIX_."cronberry_integration where fcmtoken = '". Tools::getValue('fcm')."'");
       if(count($results) == 0){
       $res = Db::getInstance()->execute('
			insert into '._DB_PREFIX_.'cronberry_integration  (sessionid,fcmtoken,id_lang,id_shop) values("'.$this->context->cookie->getName().'","'.Tools::getValue('fcm').'",'.$this->context->cookie->id_lang.','.$this->context->shop->id.')'
		);
        }
        ob_end_clean();
        header('Content-Type: application/json');
        die(json_encode([
            'success' => "success"
        ])); 
     }else{
        ob_end_clean();
        header('Content-Type: application/json');
        die(json_encode([
            'Error' => "error"
        ])); 
     }
}
}