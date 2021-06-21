<?php


defined('_PS_VERSION_') or exit;


class CronberryIntegrationInappModuleFrontController extends ModuleFrontController
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
     if( $token != null && Tools::getValue('token')  == $token ){
        $templateData = "";
        $data = $this->getInapp(Configuration::get('CRONBERRY_PROJECT_KEY'),"");
      
      if(!empty($data)){
       $dataDecode = json_decode($data);
       if($dataDecode->status){
        $templateData = $dataDecode->data->data;
       }
    }
    
    
        ob_end_clean();
        header('Content-Type: application/json');
        $this->ajaxRender(Tools::jsonEncode([
            'quickview_html' => $this->getHtml(
                 $templateData    
             )
     ]));
            
     }else{
        ob_end_clean();
        header('Content-Type: application/json');
        die(json_encode([
            'Error' => "errro"
        ])); 
     }
}


public function getHtml($data){

    
$content = "<div>";
if($data != "" && count($data)>0){
    foreach($data as $item){
        $content .= '<div class="preview">
        <div class="preview-inner">
            <div class="banner">
                <img src="'.$item->image.'">
                
            </div>
            <div class="title-box">
                <h3>'.$item->title.'</h3>
            </div>
            <div class="description-box">
                <div class="inner">'.$item->content.'</div>
                <div class="buttons" >
                <a class="btn btn-primary" href="'.$item->buttonLink.'" target="" style="background: '.$item->buttonColor.';">'.$item->buttonName.'</a>
                </div>
            </div>
        </div>
    </div>';
    }
}else{
    $content .= '<p> No notification available. </p>';
}
return $content."</div>";
}

public function getInapp($key,$audienceId){
    $header = array(
        'Content-Type: application/json',
        'Authorization: Basic Y3JvbmJlcnJ5QHVzZXJuYW1lOmNyb25iZXJyeUBwYXNzd29yZA==',
        'api-key: '.$key,
    );
    $http = array(
        'method' => 'POST',
        'user_agent' => $_SERVER['SERVER_SOFTWARE'],
        'max_redirects' => 5,
        'timeout' => 5,
    );
    $payload = json_encode( array( "audienceId"=> $audienceId, "limit" => 50 ,"page" =>0 ) );

    if( Configuration::get('CRONBERRY_LIVE_MODE')){
$url = "https://api.cronberry.com/cronberry/api/campaign/fetch-inapp-notifications-list";
    }else{
        $url = "https://api.qa1.cronberry.com/cronberry/api/campaign/fetch-inapp-notifications-list";
    }

     $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => 1,
        CURLOPT_HTTPHEADER =>  $header,
        CURLOPT_USERAGENT => $http['user_agent'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_MAXREDIRS => $http['max_redirects'],
        CURLOPT_TIMEOUT => $http['timeout'],
        CURLOPT_RETURNTRANSFER => 1,
       
        
    ));
    $resp = curl_exec($ch);
    curl_close($ch);
   
    return $resp;
 }

}