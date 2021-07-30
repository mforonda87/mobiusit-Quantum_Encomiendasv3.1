<?php

/**
 * Most of the interaccion of cliente will happens here
 *
 * @author Paolo
 */
class ClientController extends Zend_Controller_Action {
    
    public function init() {
        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->_response->setHeader('Access-Control-Allow-Headers', 'origin, x-requested-with, content-type');
        $this->_response->setHeader('Access-Control-Allow-Methods', 'PUT, GET, POST, DELETE, OPTIONS');
    }
    
    /**
     * Search user bys some criteria 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * 
     * @version   
     * @date 2020-11-29
     */
    public function searchAction() {
        $this->_helper->layout->disableLayout();

//        $nombre = $this->getRequest()->getParam("nombre");
//        $nit = $this->getRequest()->getParam("nit");
//        $tipo = $this->getRequest()->getParam("tipo");
//        $ajax = $this->getRequest()->getParam("ajax");
//        
//
//        $clienteModel = new App_Model_Cliente();
//        $this->view->clientes = $clienteModel->searchBy($nombre, $nit, $tipo);
    }
    public function clientDebtAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        echo 'hola mundo';
    }
    
    public function listPackagesAction(){
        $this->_helper->layout->disableLayout();
        $clientId = $this->getRequest()->getParam('client');
        $coorporativa = new App_Model_EncomiendaCoorporativa();
        $this->view->packages = $coorporativa->getDebtsByClient($clientId);
    }
    
    public function payPackagesAction(){
        $this->_helper->layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $this->_response->setHeader('Content-type', 'application/json; charset=utf-8');
        $clientId = $this->getRequest()->getParam('clientID');
        $encomiendaIds = Zend_Json::decode($this->getRequest()->getParam('packagesToPay'));
        $clientModel = new App_Model_Cliente();
        $return = $clientModel->payPackages($clientId, $encomiendaIds);
        
        echo Zend_Json::encode($return);
    }
}
