<?php

/**
 * Most of the interaccion of cliente will happens here
 *
 * @author Paolo
 */
class EnvioController extends Zend_Controller_Action {

    private $person;
    private $session;
    private $ciudadOrigen;
    private $nombreCiudadVendedor;

    public function init() {
        $this->_response->setHeader('Access-Control-Allow-Origin', '*');
        $this->_response->setHeader('Access-Control-Allow-Headers', 'origin, x-requested-with, content-type');
        $this->_response->setHeader('Access-Control-Allow-Methods', 'PUT, GET, POST, DELETE, OPTIONS');

        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        $this->session = $session;
        $this->person = $session->person;
        $this->ciudadOrigen = $session->ciudadID;
        $this->nombreCiudadVendedor = $session->ciudadName;
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
    }

    public function showItinerarioAction() {
        $this->_helper->layout->disableLayout();
        $destinoModel = new App_Model_DestinoModel();
        $itinerarioModel = new App_Model_Itinerario();
        $busModel = new App_Model_Bus();
        $destinos = $destinoModel->getByOrigen($this->ciudadOrigen);

        $this->view->ciudadOrigenId = $this->ciudadOrigen;
        $this->view->ciudadOrigen = $this->nombreCiudadVendedor;
        $this->view->destinos = $destinos;

        $this->view->itinerario = $itinerarioModel->getItinerario($this->ciudadOrigen, $destinos[0]->llegada);
        $this->view->buses = $busModel->fetchAll("estado='Activo'");
    }

    public function changeItinerarioAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $ciudadDestinoId = $this->getRequest()->getParam('destino');
        $itinerarioModel = new App_Model_Itinerario();

        $itinerarioList = $itinerarioModel->getItinerario($this->ciudadOrigen, $ciudadDestinoId);

        echo Zend_Json::encode($itinerarioList);
    }

    public function addViajeAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $viajeData = $this->getRequest()->getParam('viaje');
        $ciudadOrigenId = $this->getRequest()->getParam('origen');
        $ciudadDestinoId = $this->getRequest()->getParam('destino');
        $viajeModel = new App_Model_Viaje();
        echo Zend_Json::encode($viajeModel->insertTX($viajeData, $ciudadOrigenId, $ciudadDestinoId));
    }

    public function editChoferesViajeAction() {
        $this->_helper->layout->disableLayout();
        $choferViajeModel = new App_Model_ChoferViaje();
        
        $viajeId = $this->getRequest()->getParam('viaje');

        $assignedDrivers = $choferViajeModel->getChoferesViaje($viajeId);
        print_r($assignedDrivers);
    }
    
    
    /**
     * Muestra los conductores asignados y permite hacer un cambio
     */
    public function listDriversAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $choferModel = new App_Model_ChoferModel();
        $termn = $this->getRequest()->getParam('search');
        $choferes = array();
        foreach ($choferModel->getTopTenByTermn($termn) as $chofer) {            
            $value = array("nombre" => $chofer->nombre_chofer, "id" => $chofer->id_chofer, "licencia" => $chofer->numero_licencia);
            $choferes[] = array("label" => ucfirst($chofer->nombre_chofer), "value" => $value);
        }
        echo Zend_Json::encode($choferes);
        
    }
    
    public function updateDriversAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();
        $choferModel = new App_Model_ChoferModel();
        $choferViaje = new App_Model_ChoferViaje();
        $drivers = $this->getRequest()->getParam('drivers');
        $viajeId = $this->getRequest()->getParam('viaje');
        $isSetDefault = $this->getRequest()->getParam('setDefault');
        
        $choferViaje->delete("viaje='$viajeId'");
        $choferViaje->insertTX($drivers, $viajeId);
        
    }
}
