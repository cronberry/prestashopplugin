<?php


defined('_PS_VERSION_') or exit;


class CronberryIntegrationCronjobModuleFrontController extends ModuleFrontController
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
    
   
        ob_end_clean();
        header('Content-Type: application/json');
        if(Configuration::get('CRONBERRY_USER')){
           
             $this->fetchEligileData();
        }
        $this->ajaxRender(Tools::jsonEncode([
            'success' =>  "success"
     ]));
 }

public function fetchEligileData(){
    
     $sql = "select id_cronberry,
      fcmtoken as web_fcm_token, 
      pc.id_shop,
      concat(pc.id_shop,id_cronberry,md5(id_cronberry)) as audienceId,
      cart.date_add as cart_add_date, 
      concat(customer.firstname,' ',customer.lastname) as name,
      customer.email as email ,
      orders.reference as order_id,
      orders.date_add as order_date, 
      concat('[',group_concat(concat('\"',ppl.name,'\"')),']') as product_names, 
      concat('[',group_concat(concat('\"',pcp.quantity,'\"')),']')  as product_quantity, 
      posl.name as order_status, 
      address.phone, 
      address.phone_mobile, 
      address.city, 
      address.postcode,
      orders.total_paid as amount
      from "._DB_PREFIX_."cronberry_integration pc 
      left join "._DB_PREFIX_."cart cart on cart.id_cart=pc.id_cart 
      left join "._DB_PREFIX_."customer customer on customer.id_customer=cart.id_customer 
      left join "._DB_PREFIX_."orders orders on orders.id_cart = pc.id_cart 
      left join "._DB_PREFIX_."address address on address.id_address = cart.id_address_delivery 
      left join "._DB_PREFIX_."cart_product pcp on pcp.id_cart=cart.id_cart 
      left join "._DB_PREFIX_."product pp on pp.id_product = pcp.id_product 
      left join "._DB_PREFIX_."product_lang ppl on ppl.id_product = pp.id_product and  ppl.id_lang = cart.id_lang and ppl.id_shop =  cart.id_shop 
      left join "._DB_PREFIX_."order_state_lang posl on posl.id_order_state = orders.current_state and cart.id_lang=posl.id_lang 
      where (status = 0 or status is null)   and pc.add_date < date_sub(now(), interval 15 MINUTE) group by cart.id_cart limit 20";


    $results = Db :: getInstance()->ExecuteS( $sql );
     if($results!= null && count($results)>0) {
       foreach($results as $data){
           try{
           {
            $key = Configuration::get('CRONBERRY_PROJECT_KEY',null,null,$data['id_shop']);
            $post_data = $this->get_form_details($key, $data);
    
            $detailObject =   $this->post_order_details($post_data);
    //print_r($detailObject );
    if( $detailObject != null){
        if($detailObject['status']){
            
            $res = Db::getInstance()->execute('
			update '._DB_PREFIX_.'cronberry_integration  set status = "1", message="'.$detailObject['data'].'" where id_cronberry = '.$data["id_cronberry"]
		);
        }else{
            $res = Db::getInstance()->execute('
			update '._DB_PREFIX_.'cronberry_integration  set status = "2", message="'.$detailObject['error_msgs'].'" where id_cronberry = '.$data["id_cronberry"]
		);
        }
    }else{
        $res = Db::getInstance()->execute('
			update '._DB_PREFIX_.'cronberry_integration  set status = "2", message="somthing went wrong" where id_cronberry = '.$data["id_cronberry"]
		);
    }
}

           }catch(Exception $e){
            $res = Db::getInstance()->execute('
			update '._DB_PREFIX_.'cronberry_integration  set status = "2", message="'.$e->getMessage().'" where id_cronberry = '.$data["id_cronberry"]
		);
           }
       }
   } 
   
    
}


function get_form_details($project_key, $data)
{
    $paramData['paramList'] = array();

    $userObject =    [
        "projectKey" => $project_key,
        "audienceId" => $data['audienceId'] ,
        "name" => $data['name'],
        "mobile" => empty($data['phone_mobile'])?(empty($data['phone'])?"":$data['phone']):$data['phone_mobile'],
        "email" => $data['email'],
        "web_fcm_token" => $data['web_fcm_token'],
    ];


$cronberryOrderKeys = array('cart_add_date','product_names','product_quantity',
'order_id','order_date','order_status','city','postcode','amount    ');

$cartArray = array();
$orderArray = array();

   
if(Configuration::get('CRONBERRY_ABONDON_CART_AND_ORDER')){
    foreach($cronberryOrderKeys as $key){
        if(!empty($data[$key])){
            array_push($orderArray,["paramKey"=>$key,"paramValue"=>$data[$key]]);
        }
    }
    if(empty($data['order_id'])){
        array_push($cartArray,["paramKey"=>'abandon_cart',"paramValue"=>"true"]);
    }
}

$userObject['paramList'] = array_merge($cartArray,$orderArray);
return $userObject;


}

function post_order_details($data)
{
    
   if( Configuration::get('CRONBERRY_LIVE_MODE')){
    $url = 'https://api.cronberry.com/cronberry/api/campaign/register-audience-data';
    }else{
        $url = 'https://api.qa1.cronberry.com/cronberry/api/campaign/register-audience-data';
    }
    
    //print_r(json_encode($data) . PHP_EOL);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS,  json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER,  array(
        'Content-Type: application/json'
    ));
    $response = curl_exec($curl);
    $status_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    //print_r("response json from api " . $response . PHP_EOL);
    //print_r("status code for api " . $status_code . PHP_EOL);
    if ($status_code == 200) {
        $response = json_decode($response, true);
        return  $response;
        // if ($response['status']) {
           
        //     echo "posted successfully";
        //     return 1;
        // } else {
        //     echo "posted successfully but there is issue on api";
        //     return 0;
        // }
    }else{
        return  null;
    }
   
}

}