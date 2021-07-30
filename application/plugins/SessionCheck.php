<?php

/**
 * App_Plugin_SessionCheck
 * 
 * @author Enrico Zimuel (enrico@zimuel.it)
 * @version 0.2
 */
class App_Plugin_SessionCheck extends Zend_Controller_Plugin_Abstract {

    /**
     * preDispatch
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options = $bootstrap->getOptions();
        $controller = $this->getRequest()->getControllerName();
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        
        if (($controller != 'index') && ($controller != 'error') && ($controller != 'rest')) {
            if ($options['auth']['active']) {
                $this->checkSession();
            }
        } elseif (!empty($session->username) && ($controller != 'index')) {
            $fc = Zend_Controller_Front::getInstance();
            $this->getResponse()->setRedirect($fc->getBaseUrl() . '/recepcion/')->sendResponse();
        }
    }

    /**
     * checkSession
     */
    public function checkSession() {
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);

        if (empty($session->username)) {
            $fc = Zend_Controller_Front::getInstance();
            $this->getResponse()->setRedirect($fc->getBaseUrl() . '/index/')->sendResponse();
            exit;
        }
    }

}
