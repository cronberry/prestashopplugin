<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use PrestaShop\Module\PsAccounts\Config\Config;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CronberryIntegration extends Module
{
    protected $config_form = false;
    protected $_postErrors = array();
    protected $_html = '';

    public function __construct()
    {
        $this->name = 'cronberryIntegration';
        $this->tab = 'content_management';
        $this->version = '1.0.0';
        $this->author = 'Cronberry';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cronberry Integration');
        $this->description = $this->l('Integrate cronberry features like Announcement, push notifications, In app notifications, post your cart , order data to cronberry console');

        $this->confirmUninstall = $this->l('Are you sure want to uninstall ?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CRONBERRYINTEGRATION_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('overrideLayoutTemplate') && $this->registerHook('displayHeader')
            && $this->registerHook('displayBeforeBodyClosingTag')
            && $this->registerHook('actionCartSave') ;
            
    }


    

    public function hookActionCartSave($param){

        if($param['cart']!=null){
            $idCart = $param['cart']->id;
            $results = Db :: getInstance()->ExecuteS("select * from "._DB_PREFIX_."cronberry_integration 
            where  id_cart = ".$idCart);
            if(count($results) == 0){
            $res = Db::getInstance()->execute('
                 insert into '._DB_PREFIX_.'cronberry_integration 
                  (fcmtoken,id_cart,id_lang,id_shop) 
                  values("'.Tools::getValue('fcm').'",'.$idCart.','.$param['cookie']->id_lang.','.$this->context->shop->id.')'
             );
             }
        }
    }

    public function hookDisplayBeforeBodyClosingTag($params)
    {
        $script_content_plugins = $this->getScriptPlugins($params);
        $html = $this->getInappHtml($params);
        return $script_content_plugins.$html;
    }


    private function getScriptPlugins($params)
    {
       
        if(Configuration::get('FIREBASE_ENABLE') != null){
                return '<script type="text/javascript">
				    requestPermission();
				</script>';
        }
        return "";
    }

    private function getInappHtml($params){
        if(Configuration::get('CRONBERRY_INAPP_MODE')){
           return '<button type="button" id="inappbutton" class="bi bi-bell" data-toggle="modal" >
           <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bell" viewBox="0 0 16 16">
                    <path d="M8 16a2 2 0 0 0 2-2H6a2 2 0 0 0 2 2zM8 1.918l-.797.161A4.002 4.002 0 0 0 4 6c0 .628-.134 2.197-.459 3.742-.16.767-.376 1.566-.663 2.258h10.244c-.287-.692-.502-1.49-.663-2.258C12.134 8.197 12 6.628 12 6a4.002 4.002 0 0 0-3.203-3.92L8 1.917zM14.22 12c.223.447.481.801.78 1H1c.299-.199.557-.553.78-1C2.68 10.2 3 6.88 3 6c0-2.42 1.72-4.44 4.005-4.901a1 1 0 1 1 1.99 0A5.002 5.002 0 0 1 13 6c0 .88.32 4.2 1.22 6z"/>
            </svg>
         </button><div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="inappbody"></div>
     
    </div>
  </div>
</div>';
        }
        return "";

    }


    public static function getFirebaseObject(){
        return array('firebaseConfig' => array(
            'apiKey' => (Configuration::get('CRONBERRY_FIREBASE_API_KEY',null)),
            'authDomain' => (Configuration::get('CRONBERRY_FIREBASE_AUTH_DOMAIN',null)),
            'databaseURL' => (Configuration::get('CRONBERRY_FIREBASE_DATABASE_URL',null)),
            'projectId' => (Configuration::get('CRONBERRY_FIREBASE_PROJECT_ID',null)),
            'storageBucket' => (Configuration::get('CRONBERRY_FIREBASE_STORAGE_BUCKET',null)),
            'messagingSenderId' => (Configuration::get('CRONBERRY_FIREBASE_MESSAGING_SENDER_ID',null)),
            'appId' => (Configuration::get('CRONBERRY_FIREBASE_APP_ID',null)),
            'measurementId' => (Configuration::get('CRONBERRY_FIREBASE_MEASUREMENT_ID',null)),
        ));
    }

    public function hookDisplayHeader()
    {  


       
        $token =  sha1($this->context->cookie->date_add._COOKIE_KEY_);
        Media::addJsDef(array('tokencr' => $token));
        if(Configuration::get('FIREBASE_ENABLE') != null){
            Media::addJsDef(self::getFirebaseObject());
            $this->context->controller->registerJavascript("firebaseapp", "https://www.gstatic.com/firebasejs/8.6.2/firebase-app.js",['server' => 'remote', 'position' => 'bottom', 'priority' => 20]);
            $this->context->controller->registerJavascript("firebase-analytics", "https://www.gstatic.com/firebasejs/8.6.2/firebase-analytics.js", ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]);
            $this->context->controller->registerJavascript("firebase-messaging", "https://www.gstatic.com/firebasejs/8.6.2/firebase-messaging.js", ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]);
            $this->context->controller->registerJavascript("firebase-auth", "https://www.gstatic.com/firebasejs/8.6.2/firebase-auth.js", ['server' => 'remote', 'position' => 'bottom', 'priority' => 20]);
            $this->context->controller->registerJavascript("custom-cronberry", $this->_path.'views/js/front.js',['server' => 'remote','position' => 'bottom', 'priority' => 20]);
         
        }
        if(Configuration::get('CRONBERRY_ANNOUNCEMENT_URL') != null){
            $this->context->controller->registerJavascript("announcement", Configuration::get('CRONBERRY_ANNOUNCEMENT_URL'), ['server' => 'remote', 'position' => 'bottom', 'priority' => 20,'attributes' => 'async']);
          
        }

        if(Configuration::get('CRONBERRY_INAPP_MODE')){
            $this->context->controller->registerStylesheet("Inapp",$this->_path.'views/css/front.css');
            $this->context->controller->registerJavascript("inapp-cronberry", $this->_path.'views/js/inapp.js');
       
        }
      
    }
    public function uninstall()
    {

        
        Configuration::deleteByName('CRONBERRY_OLD_ALREADY_SENT');
        Configuration::deleteByName('CRONBERRY_SEND_PREVIOUS_ORDERS');
        Configuration::deleteByName('CRONBERRY_LIVE_MODE');
        Configuration::deleteByName('CRONBERRY_INAPP_MODE');
       // Configuration::deleteByName('CRONBERRY_PROJECT_KEY');
        Configuration::deleteByName('CRONBERRY_ANNOUNCEMENT_URL');
        Configuration::deleteByName('CRONBERRY_ABONDON_CART_AND_ORDER');
        
        Configuration::deleteByName('CRONBERRY_USER');
        Configuration::deleteByName('CRONBERRY_FIREBASE_API_KEY');
        Configuration::deleteByName('FIREBASE_ENABLE');

        
        Configuration::deleteByName('CRONBERRY_FIREBASE_AUTH_DOMAIN');
        Configuration::deleteByName('CRONBERRY_FIREBASE_DATABASE_URL');
        Configuration::deleteByName('CRONBERRY_FIREBASE_PROJECT_ID');
        Configuration::deleteByName('CRONBERRY_FIREBASE_MESSAGING_SENDER_ID');
        Configuration::deleteByName('CRONBERRY_FIREBASE_MEASUREMENT_ID');
        Configuration::deleteByName('CRONBERRY_FIREBASE_APP_ID');
        Configuration::deleteByName('CRONBERRY_FIREBASE_STORAGE_BUCKET');

       
        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {

        /**
         * If values have been submitted in the form, process.
         */
        $this->_postValidation();
        
            if (!count($this->_postErrors)) {
                if (((bool)Tools::isSubmit('submitCronberryModule')) == true) {
                $this->postProcess();
                }else{
                    $this->postProcessFirebase();
                }
            }else {
                $this->_html .= '<br />';
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        
        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('multistore', Shop::isFeatureActive());
        $this->context->smarty->assign('shopName', "https://".$this->context->shop->domain);
       

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
        
        return $this->_html.$output.$this->renderForm().$this->renderFormFiresbase();
    }

    protected function _postValidation()
    {
       
        if (((bool)Tools::isSubmit('submitCronberryModule')) == true) {
            if(!Tools::getValue('CRONBERRY_PROJECT_KEY')   || Tools::getValue('CRONBERRY_PROJECT_KEY')  == null) {
                $this->_postErrors[] = "Project Key is required";
            }
            $data = $this->createDefaultParams(Configuration::get('CRONBERRY_PROJECT_KEY'));
            if(!empty($data)){
               $dataDecode = json_decode($data);
               if(!$dataDecode->status){
                   $this->_postErrors[] = "Some details are not saved. Please save again.";
               }
        }
        }
        
        if (((bool)Tools::isSubmit('submitCronberryModuleFirebase')) == true) {
       
            if ((!Tools::getValue('CRONBERRY_FIREBASE_API_KEY')   || Tools::getValue('CRONBERRY_FIREBASE_API_KEY')  == null)
            ||  (!Tools::getValue('CRONBERRY_FIREBASE_AUTH_DOMAIN')   || Tools::getValue('CRONBERRY_FIREBASE_AUTH_DOMAIN')  == null) 
            || (!Tools::getValue('CRONBERRY_FIREBASE_PROJECT_ID')   || Tools::getValue('CRONBERRY_FIREBASE_PROJECT_ID')  == null)  
            || (!Tools::getValue('CRONBERRY_FIREBASE_MESSAGING_SENDER_ID')  || Tools::getValue('CRONBERRY_FIREBASE_MESSAGING_SENDER_ID')  == null)  
            || (!Tools::getValue('CRONBERRY_FIREBASE_APP_ID')   || Tools::getValue('CRONBERRY_FIREBASE_APP_ID')  == null)   
            || (!Tools::getValue('CRONBERRY_FIREBASE_STORAGE_BUCKET')   || Tools::getValue('CRONBERRY_FIREBASE_STORAGE_BUCKET')  == null)   
            ) {
                $this->_postErrors[] = "Firebase Details are required.";
            } 
        }
        
    }


    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCronberryModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigForm())) ;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderFormFiresbase()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCronberryModuleFirebase';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValuesFirebase(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm(array($this->getConfigFormFirebase())) ;
    }

    protected function getConfigFormFirebase()
    {
       // print_r();
       
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Firebase Settings (You can leave these settings if you dont want to configure push notifications.)'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Do you want to send push notification?'),
                        'name' => 'FIREBASE_ENABLE',
                        'is_bool' => true,
                        'desc' => $this->l('Firebase tokens will be send to cronberry and you can send push notification from cornberry console'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'col' => 8,
                        'required' => true,
                        'name' => 'CRONBERRY_FIREBASE_API_KEY',
                        'label' => $this->l('Firebase API KEY'),
                        'desc' => "Enter firebase API key. which you get from firebase console."
                    ),
                    array(
                        'type' => 'text',
                        'col' => 8,
                        'required' => true,
                        'name' => 'CRONBERRY_FIREBASE_AUTH_DOMAIN',
                        'label' => $this->l('Firebase Auth Domain'),
                        'desc' => "Enter firebase AUTH DOMAIN. which you get from firebase console."
                    ),
                    array(
                        'type' => 'text',
                        'col' => 8,
                        'required' => true,
                        'name' => 'CRONBERRY_FIREBASE_PROJECT_ID',
                        'label' => $this->l('Firebase Project ID'),
                        'desc' => "Enter firebase project id. you will get it from firebase console."
                    ),
                    array(
                        'type' => 'text',
                        'col' => 8,
                        'required' => true,
                        'name' => 'CRONBERRY_FIREBASE_STORAGE_BUCKET',
                        'label' => $this->l('Firebase storage bucket'),
                        'desc' => "Enter firebase storage bucket. you will get it from firebase console."
                    ),
                    array(
                        'type' => 'text',
                        'col' => 8,
                        'required' => true,
                        'name' => 'CRONBERRY_FIREBASE_MESSAGING_SENDER_ID',
                        'label' => $this->l('Firebase Messaging Sender ID'),
                        'desc' => "Enter firebase Messaging Sender ID. you will get it from firebase console"
                    ),
                    array(
                        'type' => 'text',
                        'col' => 8,
                        'required' => true,
                        'name' => 'CRONBERRY_FIREBASE_APP_ID',
                        'label' => $this->l('Firebase App Id'),
                        'desc' => "Enter firebase app id. You will get it from firebase console."
                    )
                    
                     
                ),
               
            
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
       // print_r();
       
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Cronberry Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'col' => 8,
                        'name' => 'CRONBERRY_PROJECT_KEY',
                        'required' => true,
                        'label' => $this->l('Cronberry Project Key'),
                        'desc' => "Enter project Key. you will get it from Cronberry Dashboard."
                    ),
                    
                    
                    
                   
                    array(
                        'type' => 'text',
                        'col' => 8,
                        'name' => 'CRONBERRY_ANNOUNCEMENT_URL',
                        'label' => $this->l('Cronberry Announcement URL'),
                        'desc' => "Get announcemnt script url from cronberry (https://www.cronberry.com/admin/configuration) at announcement tab"
                    ),
                    
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Live mode'),
                        'name' => 'CRONBERRY_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('If this flag is active then production settings will work '),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable InApp Notification'),
                        'name' => 'CRONBERRY_INAPP_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Do you want to show inapp notication to your customer. '),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Do you want to send user info to cronberry?'),
                        'name' => 'CRONBERRY_USER',
                        'is_bool' => true,
                        'desc' => $this->l('User info will be store in cronberry console .'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Do you want to send abondon cart and order data to cronberry?'),
                        'name' => 'CRONBERRY_ABONDON_CART_AND_ORDER',
                        'is_bool' => true,
                        'desc' => $this->l('Abondon cart user data and order data along with cart products will be stored in cronbery audience.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Do you want to send all previous orders and carts to cronberry console?'),
                        'name' => 'CRONBERRY_SEND_PREVIOUS_ORDERS',
                        'is_bool' => true,
                        'desc' => $this->l('You can send all previous orders and cart to cronberry'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    )
                    
                   
                ),
               
            
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            
            'CRONBERRY_LIVE_MODE' => Configuration::get('CRONBERRY_LIVE_MODE', false),
        'CRONBERRY_INAPP_MODE' => Configuration::get('CRONBERRY_INAPP_MODE', false),
        'CRONBERRY_PROJECT_KEY' => Configuration::get('CRONBERRY_PROJECT_KEY', null),
         'CRONBERRY_ANNOUNCEMENT_URL' => Configuration::get('CRONBERRY_ANNOUNCEMENT_URL', null),
         'CRONBERRY_ABONDON_CART_AND_ORDER' => Configuration::get('CRONBERRY_ABONDON_CART_AND_ORDER', false),
         'CRONBERRY_SEND_PREVIOUS_ORDERS' => Configuration::get('CRONBERRY_SEND_PREVIOUS_ORDERS', false),
         'CRONBERRY_USER' => Configuration::get('CRONBERRY_USER', false),
         
        );
    }

    protected function getConfigFormValuesFirebase()
    {
        return array(
            'FIREBASE_ENABLE' => Configuration::get('FIREBASE_ENABLE', false),
         'CRONBERRY_FIREBASE_API_KEY' => Configuration::get('CRONBERRY_FIREBASE_API_KEY', null),
         'CRONBERRY_FIREBASE_AUTH_DOMAIN' => Configuration::get('CRONBERRY_FIREBASE_AUTH_DOMAIN', null),
         'CRONBERRY_FIREBASE_DATABASE_URL' => Configuration::get('CRONBERRY_FIREBASE_DATABASE_URL', null),
         'CRONBERRY_FIREBASE_PROJECT_ID' => Configuration::get('CRONBERRY_FIREBASE_PROJECT_ID', null),
         'CRONBERRY_FIREBASE_MESSAGING_SENDER_ID' => Configuration::get('CRONBERRY_FIREBASE_MESSAGING_SENDER_ID', null),
         'CRONBERRY_FIREBASE_APP_ID' => Configuration::get('CRONBERRY_FIREBASE_APP_ID', null),
         'CRONBERRY_FIREBASE_STORAGE_BUCKET' => Configuration::get('CRONBERRY_FIREBASE_STORAGE_BUCKET', null),
  
        );
    }

    /**
     * Save form data.
     */



    protected function postProcessFirebase()
    {

    
        $form_values = $this->getConfigFormValuesFirebase();
            foreach (array_keys($form_values) as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
        }
          $message = 'importScripts("https://www.gstatic.com/firebasejs/8.6.2/firebase-app.js");
        importScripts("https://www.gstatic.com/firebasejs/8.6.2/firebase-messaging.js");
        var firebaseConfig = '.json_encode(self::getFirebaseObject()["firebaseConfig"]) .'
        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();';
        if(Shop::isFeatureActive()){
            $src = $_SERVER['DOCUMENT_ROOT'].$this->_path.'views/js/'.$this->context->shop->domain.'-firebase-messaging-sw.js';  // source folder or file
        }else{
            $src = $_SERVER['DOCUMENT_ROOT'].'/firebase-messaging-sw.js';  // source folder or file
        }
        shell_exec("echo '$message' > $src ");
            
        }
       
        
       
       
    
   

    protected function postProcess()
    {
            $form_values = $this->getConfigFormValues();
            foreach (array_keys($form_values) as $key) {
                Configuration::updateValue($key, Tools::getValue($key));
            }
            if(!Configuration::get('CRONBERRY_OLD_ALREADY_SENT') &&  Configuration::get('CRONBERRY_SEND_PREVIOUS_ORDERS')){
                Configuration::updateValue('CRONBERRY_OLD_ALREADY_SENT', true);
                $results = Db :: getInstance()->Execute("insert into ps_cronberry_integration (id_shop,id_lang,id_cart,sessionid) select id_shop,id_lang,id_cart,secure_key from ps_cart where id_shop = ".$this->context->shop->id." and id_cart not in (select id_cart from ps_cronberry_integration)");
            }

            if(!Configuration::get('CRONBERRY_OLD_ALREADY_SENT') &&  Configuration::get('CRONBERRY_SEND_PREVIOUS_ORDERS')){
                Configuration::updateValue('CRONBERRY_OLD_ALREADY_SENT', true);
                $results = Db :: getInstance()->Execute("insert into ps_cronberry_integration (id_shop,id_lang,id_cart,sessionid) select id_shop,id_lang,id_cart,secure_key from ps_cart where id_shop = ".$this->context->shop->id." and id_cart not in (select id_cart from ps_cronberry_integration)");
            }

     
       
    }

    private function createDefaultParams($key){
        $header = array(
            'Content-Type: application/json',
            'Authorization: Basic Y3JvbmJlcnJ5QHVzZXJuYW1lOmNyb25iZXJyeUBwYXNzd29yZA==',
        );
        $defaultParams = array(
            [
                'paramName' => 'Order Date',
                'paramDatatype' => 'Date',
                'paramCategory' => '1',
                'param_key' => 'order_date'
            ],
            [
                'paramName' => 'Cart Add Date',
                'paramDatatype' => 'Date',
                'paramCategory' => '1',
                'param_key' => 'cart_add_date'
            ],
            [
                'paramName' => 'Product Names',
                'paramDatatype' => 'String',
                'paramCategory' => '1',
                'param_key' => 'product_names'
            ],
            
            [
                'paramName' => 'Product Quantity',
                'paramDatatype' => 'String',
                'paramCategory' => '1',
                'param_key' => 'product_quantity'
            ],
            [
                'paramName' => 'Order Id',
                'paramDatatype' => 'String',
                'paramCategory' => '1',
                'param_key' => 'order_id'
            ],
            
            [
                'paramName' => 'Order Status',
                'paramDatatype' => 'String',
                'paramCategory' => '1',
                'param_key' => 'order_status'
            ],

            [
                'paramName' => 'City',
                'paramDatatype' => 'String',
                'paramCategory' => '1',
                'param_key' => 'city'
            ],

            [
                'paramName' => 'Postcode',
                'paramDatatype' => 'String',
                'paramCategory' => '1',
                'param_key' => 'postcode'
            ],

            [
                'paramName' => 'Total Amount',
                'paramDatatype' => 'Numeric',
                'paramCategory' => '1',
                'param_key' => 'amount'
            ],
            [
                'paramName' => 'Abondon Cart',
                'paramDatatype' => 'string',
                'paramCategory' => '0',
                'param_key' => 'abandon_cart'
            ],
        );

        $http = array(
            'method' => 'POST',
            'user_agent' => $_SERVER['SERVER_SOFTWARE'],
            'max_redirects' => 5,
            'timeout' => 5,
        );
        $payload = json_encode( array( "projectKey"=> $key, "dynamicParamList" => $defaultParams ) );
    
        if( Configuration::get('CRONBERRY_LIVE_MODE')){
             $url = "https://api.cronberry.com/cronberry/api/plugins/create-dynamic-params";
        }else{
            $url = "https://api.qa1.cronberry.com/cronberry/api/plugins/create-dynamic-params";
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
    
    



    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        //this css is for croberry only. it is not required anywhere else
      
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }
    
    public function hookOverrideLayoutTemplate(){
    }
    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
   
}
