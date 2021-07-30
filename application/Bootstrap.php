<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initModuleAutoloader() {
        $al = new Zend_Loader_Autoloader_Resource(
                        array('basePath' => dirname(__FILE__), 'namespace' => 'App'));
        $al->addResourceType('util', 'utils', 'Util');
        $al->addResourceType('bean', 'beans', 'Bean');
        $al->addResourceType('rest', 'services', 'Rest');
        $app = new Zend_Application_Module_Autoloader(array('namespace' => 'App', 'basePath' => dirname(__FILE__)));
    }

    protected function _initView() {
        // Initialize view
        $view = new Zend_View();
        $view->doctype('XHTML1_STRICT');
        $view->headTitle('Sistema de Encomiendas');
        $view->addHelperPath('../library/Zend/View/Helper/', 'Zend_View_Helper');
        $request = new Zend_Controller_Request_Http();
        $baseUrl = $request->getBaseUrl();
        
        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");
        $view->jQuery()->addStylesheet($baseUrl . '/styles/jquery-ui-1.8.23.custom.css');
        $view->jQuery()->setLocalPath($baseUrl . '/scripts/jquery/jquery-1.8.0.pack.js');
        $view->jQuery()->setUiLocalPath($baseUrl . '/scripts/jquery/jquery-ui-1.8.23.custom.pack.js');
        $view->jQuery()->enable();
        $view->jQuery()->uiEnable();
        $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
        $viewRenderer->setView($view);
        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);

        return $view;

        // Add it to the ViewRenderer
//        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
//        $viewRenderer->setView($view);
        // Return it, so that it can be stored by the bootstrap
//        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
//        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
//        return $view;
    }

    protected function _initViewHelpers() {
//        $view = new Zend_View();
//        $view->addHelperPath(APPLICATION_PATH . '/views/helpers');
//        $view->addHelperPath("ZendX/JQuery/View/Helper", "ZendX_JQuery_View_Helper");
        //jQuery (using the ui-lightness theme)
//        $view->jQuery()->addStylesheet($this->baseUrl().'/js/jquery/css/ui-lightness/jquery-ui-1.7.2.custom.css')
//                ->setLocalPath('/js/jquery/js/jquery-1.3.2.min.js')
//                ->setUiLocalPath('/js/jquery/js/jquery-ui-1.7.2.custom.min.js');
//        $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
//        $viewRenderer->setView($view);
//        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);
    }

    protected function _initSession() {
        $options = $this->getOptions();
//        echo "la session es :".$options['resources']['session']['namespace'];
        $sessionName = $options['resources']['session']['namespace'];
        $session = new Zend_Session_Namespace($sessionName);
        Zend_Registry::set($sessionName, $session);
        App_Util_Statics::$SESSION = $sessionName;
    }

    protected function _initLog() {
        //  registramos el log de las sesiones
        $date = new Zend_Date();
        $name = $date->toString("YYYY-MM-dd");
        $stream = @fopen("../log/" . $name . ".log", 'a', false);
        if (!$stream) {
            throw new Exception('Failed to open stream');
        }
        $writer = new Zend_Log_Writer_Stream($stream);
//        $writer->setFormatter($formatter)
        $log = new Zend_Log($writer);
        Zend_Registry::set("log", $log);
        
        //  registramos el log de las impresiones
        $date = new Zend_Date();
        $name = $date->toString("YYYY-MM-dd");
        $stream = @fopen("../log/impresion" . $name . ".log", 'a', false);
        if (!$stream) {
            throw new Exception('Failed to open stream');
        }
        $writer = new Zend_Log_Writer_Stream($stream);
        $log = new Zend_Log($writer);
        Zend_Registry::set("logImpresion", $log);
    }

}

