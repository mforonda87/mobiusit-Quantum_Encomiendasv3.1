<?php

class RecepcionController extends Zend_Controller_Action {

    private $person;
    private $session;
    private $ciudadOrigen;
    private $nombreCiudadVendedor;
    private $baseURL;
    private $logger;

    public function init() {
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        $this->session = $session;
        $this->person = $session->person;
        $this->ciudadOrigen = $session->ciudadID;
        $this->nombreCiudadVendedor = $session->ciudadName;
        $this->view->baseUrl = $this->_request->getBaseUrl();
        $this->baseURL = $this->_request->getBaseUrl();
        $configModel = new App_Model_ConfiguracionSistema();
        $accionModel = new App_Model_Accion();
        $this->view->idCiudadOrigen = base64_encode($session->ciudadID);
        $this->view->configuraciones = Zend_Json::encode($configModel->getAll());
        $events = $accionModel->getByUser($session->person->id_persona);
        $this->view->events = $events;
        $this->logger = Zend_Registry::get('log');
    }

    public function indexAction() {
//        $viajeModel = new App_Model_Viaje( );
//        $this->view->headScript()->appendScript($this->view->events);
        $ciudadModel = new App_Model_CiudadModel();
        $encomiendaModel = new App_Model_EncomiendaModel();
        $manifModel = new App_Model_ManifiestoModel();

        $fecha = $this->getRequest()->getParam('calendario');
        if (!isset($fecha) || $fecha == "") {
            $fecha = date('Y-m-d');
        }
        $ciudades = $ciudadModel->getCiudadDestinoForDropDown($this->ciudadOrigen);
        $ciudadSelected = $this->getRequest()->getParam("ciudadSelected");
        if ($ciudadSelected == "") {
            $ciudadSelected = key($ciudades);
        }

        $sucModel = new App_Model_SucursalModel();
        $eqp = $this->getEncomiendasPredefinidas($this->ciudadOrigen, $ciudadSelected);
        /*         * ********************   LISTA DE CLIENTES A ENVIAR  ******************* */
        $clienteModel = new App_Model_Cliente();
        $clientes = array();
        foreach ($clienteModel->getAll() as $cli) {
            $clientes[] = array("nit" => $cli->nit, "nombre" => $cli->nombre);
        }
        /*         * *********************  LISTA DE MANIFEISTO  **************************** */
        $manifiestos = $manifModel->getByDate(date("Y-m-d"), $this->person->id_persona);
        $actions = array(
            array("icon" => "/images/icon-lista.png", "action" => "cargarManifiestos", "nombre" => "ver manifiesto", "titulo" => "Ver Maniviestos de viaje"),
            array("icon" => "/images/mover_encomiendas.gif", "action" => "moverEncomiendas", "nombre" => "mover encomiendas", "titulo" => "Mover encomiendas")
        );
        $sucModel = new App_Model_SucursalModel();
        $suc = $sucModel->getById($this->person->sucursal);

        $this->view->userSucursal = Zend_Json::encode($suc);
        $this->view->viajes = $this->makeItinerario($fecha, "selectForManifiesto", $actions);
        $this->view->sucursales = $sucModel->getSucursalByCiudad($ciudadSelected);
        $this->view->ciudades = $ciudades;
        $this->view->predef = Zend_Json::encode($eqp);
        $this->view->clientes = Zend_Json::encode($clientes);
        $this->view->encomiendas = $encomiendaModel->getByEstadoDestino("Recibido", $ciudadSelected);
        $this->view->manifiestos = $manifiestos;
    }

    /**
     * Search clientes starting with the term to be searched
     * @param type $term
     */
    function getClientesAction($term) {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $datos = $this->getRequest()->getParams();
        $clienteModel = new App_Model_Cliente();
        $clientes = array();
        foreach ($clienteModel->searchByTerm($datos['search']) as $cli) {
            $value = array("nombre" => $cli->nombre, "nit" => $cli->nit, "tipo" => ucfirst($cli->tipo));
            $clientes[] = array("label" => $cli->nombre, "value" => $value);
        }
        echo Zend_Json::encode($clientes);
    }

    /**
     * recupera la informacion de precios de encomiendas predefinidas para
     * un destino
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.net.bo
     * @version beta
     * @date $(date)
     * TODO :
     */
    function getEncomiendasPredefinidas($idCiudadOrigen, $ciudadSelected) {
        $eqp = array();
        $eqPredefModel = new App_Model_Precio();
        $destinoModel = new App_Model_DestinoModel();
        $destino = $destinoModel->getByOrigenDestino($idCiudadOrigen, $ciudadSelected);
        foreach ($eqPredefModel->getByDestino($destino->id_destino) as $eq) {
            $eqp[] = array("detalle" => $eq->descripcion, "peso" => $eq->peso, "precio" => $eq->precio);
        }
        return $eqp;
    }

    /*
     * Permite registrar las encomiendas que se enviaran
     */

    public function saveAction() {
$log = Zend_Registry::get("log");
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $datos = $this->getRequest()->getParams();

        unset($datos["controller"]);
        unset($datos["action"]);
        unset($datos["module"]);

        $items = $datos["items"];
        unset($datos["items"]);

        $encoModel = new App_Model_EncomiendaModel();
        $result = array();
        $tipoEncomienda = base64_decode($datos["tipoEncomienda"]);
        if ($datos["total"] == "0" && ($tipoEncomienda == "NORMAL" || $tipoEncomienda == "GIRO")) {
            $result["mensaje"] = "Una encomienda normal no puede tener costo '0'";
            $result["error"] = true;
        } else {
            try {
                if ($datos['Nit'] != 0 && $datos['Nit'] != "") {
                    try {
                        $clienteM = new App_Model_Cliente();
                        $cliente = array(
                            "id_cliente" => "-1",
                            "nombre" => strtoupper($datos['nombreFactura']),
                            "telefono" => $datos['remitenteTelf'],
                            "nacion" => "Boliviano",
                            "ocupacion" => "No registrado",
                            "correo" => "",
                            "tipo_documento" => "ci",
                            "pais_residencia" => "bolivia",
                            "nit" => $datos['Nit']);
                        $clienteM->insert($cliente);
                    } catch (Zend_Db_Exception $ze) {
                       $log->err("Error inserting client".$ze->getTraceAsString()); 
                    }
                }
                $errorValidation = $this->isValidEncomienda($datos);
                if ($errorValidation['error'] == false) {
                    $items = str_replace("'", '"', $items);
                    $items = str_replace("\\", '', $items);
                    $objectJSON = Zend_Json::decode($items);

                    if (!is_null($objectJSON)) {
                        if ($datos["tipo"] == "Manual") {
                            if ($datos["dosificacion"] == "" && base64_decode($datos["tipoEncomienda"]) == "NORMAL") {
                                $mensaje = "Debe seleccionar una dosificacion para registrar una factura manual";
                                $error = true;
                            } elseif ($datos["numeroFactura"] == "" && $datos["numeroFactura"] == "0") {
                                $mensaje = "Debe introducir el numero de la factura manual";
                                $error = true;
                            } elseif ($datos["fecha"] == "") {
                                $mensaje = "Debe introducir La fecha para la factura";
                                $error = true;
//                        } elseif ($datos["viaje"] == "0") {
//                            $mensaje = "No se ha seleccionado un viaje para la encomienda";
//                            $error = true;
                            } else {
                                $data = $encoModel->txSaveManual($datos, $objectJSON, $this->person, $this->ciudadOrigen);
                                $mensaje = "La encomienda se registro con exito";
                                $error = false;
                                $cabeceraF = "Manual";
                            }
                        } else {
                            $data = $encoModel->txSave($datos, $objectJSON, $this->person, $this->nombreCiudadVendedor);
                            $mensaje = "La encomienda se registro con exito";
                            $error = false;
                            $result["factura"] = $data["factura"];
                            $result["items"] = $data["items"];
                            $cabeceraF = $data["cabecera"];
                            $empresa = $data["empresa"];
                        }
                        $result["encomienda"] = $data["encomienda"];
                        $result["encomienda"]["tipo"] = base64_decode($datos['tipoEncomienda']);
                        $result["mensaje"] = $mensaje;
                        $result["error"] = $error;
                        $result["cabecera"] = $cabeceraF;
                        $result["empresa"] = $empresa;
                        $result["tipo"] = base64_decode($datos['tipoEncomienda']);
                    } else {
                        $result["mensaje"] = "Debe almenos registrar un item para la encomienda ";
                        $result["error"] = true;
                    }
                } else {
                    $result["mensaje"] = $errorValidation["errors"];
                    $result["error"] = true;
                }
            } catch (Zend_DB_Exception $zde) {
                $result["mensaje"] = $zde->getMessage();
                $result["error"] = true;
                $log = Zend_Registry::get("log");
                $log->info($zde);
            }
        }
//        echo "val :".$objectJSON['nombreFactura']."  ---- ";
//        $objectJSON;
//        print_r($objectJSON);
        echo Zend_Json::encode($result);
    }

    /**
     * Valida el formulario de la s encomiendas
     * 
     */
    function isValidEncomienda($encomiendaForm) {
        $return = array("error" => false, "errors" => array());
        foreach ($encomiendaForm as $key => $value) {
            if ($value == "" && ($key != "Nit" && $key != "nombreFactura" && $key != "declarado")) {
                $return["errors"][$key] = "El campo $key no puede ser vacio";
                $return["error"] = true;
            }

            if ($key == "destinoSuc" && (base64_decode($value == 0) || base64_decode($value) == "0")) {
                $return["errors"][$key] = "No existen sucursales de destino ";
                $return["error"] = true;
            }
        }
        return $return;
    }

    /**
     * Recupera la lista de dosificaciones manuales activas y lo pone en un
     * formulario para registrar una factura manual
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date $(now)
     *
     */
    function listDosificacionAction() {
        $this->_helper->layout->disableLayout();

        $fecha = $this->getRequest()->getParam("fecha");
        $tipo = $this->getRequest()->getParam("tipo");
        $nombre = $this->getRequest()->getParam("nombre");
        if (!isset($fecha)) {
            $fecha = date("Y-m-d");
        }

        $tipoEncomienda = base64_decode($tipo);
        if ($tipoEncomienda == "NORMAL" || $tipoEncomienda == "GIRO") {
            $dosModel = new App_Model_DosificacionModel();

            $dosificaciones = $dosModel->getManualBySucusalSistema($this->person->sucursal, App_Util_Statics::$SYSTEM);
            $this->view->dosificaciones = $dosificaciones;
        }
        $this->view->fecha = $fecha;
        $this->view->nombre = $nombre == "" ? "SIN NOMBRE" : $nombre;
    }

    /**
     * recupera las sucursales de la ciudad enviada como parametro
     * ademas cambia la informacion de precios de las encoiendas predefinidas
     * segun el destino seleccionado
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.net.bo
     * @version beta
     * @date $(date)
     * TODO :
     */
    function cambiarCiudadAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $datos = $this->getRequest()->getParam("id");
        $idciudad = base64_decode($datos);
        $sucModel = new App_Model_SucursalModel();
        $sucursales = $sucModel->getSucursalByCiudad($idciudad);
        $resp = array();
        if (count($sucursales) > 0) {
            $sucResp = "";
            foreach ($sucursales as $suc) {
                $sucResp .= "<option value='" . base64_encode($suc["id_sucursal"]) . "'>" . $suc["nombre"] . "</option>";
            }
            $resp["sucursales"] = $sucResp;
        } else {
            $resp["sucursales"] = "no existen sucursales";
        }
        $resp["encomiendas"] = $this->getEncomiendasPredefinidas($this->ciudadOrigen, $idciudad);
        echo Zend_Json::encode($resp);
    }

    /**
     * recupera las encomiedas segun el destino que llega,
     * lo formatea en un array de json para volver a cargar los
     * datos en la tabla de la vista
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function filtroEncomiendasAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $encomiendaModel = new App_Model_EncomiendaModel();

        $destino = $this->getRequest()->getParam("destino");
        $destino = base64_decode($destino);
        $encomiendas = $encomiendaModel->getByEstadoDestino("Recibido", $destino);
        $datos = array();
        foreach ($encomiendas as $encomienda) {
            $encomienda["id_encomienda"] = base64_encode($encomienda["id_encomienda"]);
            $datos[] = $encomienda;
        }
        echo Zend_Json::encode($datos);
    }

    /**
     * Recupera la informacion de los choferes de un viaje seleccioando
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getChoferesAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $viajeModel = new App_Model_Viaje();

        $viaje = $this->getRequest()->getParam("viaje");
        $viaje = base64_decode($viaje);
        $chofers = $viajeModel->getChoferesViaje($viaje);
        $datos = "";
        if (count($chofers) == 0) {
            $datos = "<option value='000'>No Asignados</option>";
        } else {
            foreach ($chofers as $chof) {
//                $chof["id_encomienda"] = base64_encode($chof["id_encomienda"]);
                $datos .= "<option value='" . base64_encode($chof->id_chofer) . "'>" . $chof->nombre_chofer . "</option>";
            }
        }
        echo $datos;
    }

    /**
     * Recibe informacion de las encomiendas que
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function saveManifiestoAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $viaje = $this->getRequest()->getParam("viaje");

        $encomiendas = $this->getRequest()->getParam("data");
        $encomiendas = trim($encomiendas);
        $chofer = $this->getRequest()->getParam("chofer");
        $bus = $this->getRequest()->getParam("bus");
        $destino = $this->getRequest()->getParam("destino");
        $fecha = $this->getRequest()->getParam("fechaViaje");

        $viaje = $viaje == "0" ? "0" : base64_decode($viaje);
        if ($chofer != "000" && $chofer != "undefined") {
            $chofer = base64_decode($chofer);
        }

        $bus = $bus == "0" ? "0" : base64_decode($bus);
        $destino = base64_decode($destino);

        if ($viaje == "0" || $chofer == "000" || $chofer == "undefined" || $bus == "0") {
            if ($viaje == "0") {
                $mensaje = "No se ha seleccionado un viaje para las encomiendas ";
            } elseif ($chofer == "000" || $chofer == "undefined") {
                $mensaje = "No se han asignado choferes a este viaje por favor solite la asignacion en administracion";
            }
            $error = true;
        } else {
            $encomiendas = Zend_Json::decode($encomiendas);
            if (count($encomiendas) == 0) {
                $error = true;
                $mensaje = "No se han seleccionado encomiendas";
            } else {
                try {
                    $ids = array();
                    foreach ($encomiendas as $id => $data) {
                        $ids[base64_decode($id)] = array("size" => count($data), "items" => array());
                        $data = split(",", $data);
                        foreach ($data as $item) {
                            $ids[base64_decode($id)]["items"][] = base64_decode($item);
                        }
                    }

                    $manModel = new App_Model_ManifiestoModel();
                    $idMan = $manModel->saveTx($viaje, $chofer, $bus, $ids, $this->person, $destino, $this->ciudadOrigen, $fecha);
                    $encs = $manModel->getEncomiendasById($idMan);

                    $configuracionSM = new App_Model_ConfiguracionSistema();
                    $configuraciones = $configuracionSM->getAll();

                    $result = array();
                    foreach ($encs as $enc) {
                        $result[] = array("guia" => $enc->guia, "detalle" => $enc->detalle, "total" => $enc->total, "tipo" => $enc->tipo);
                    }
                    $resp["manifiesto"] = array("fecha" => date("Y-m-d"));
                    $resp["lista"] = $result;
                    $resp["cabecera"] = array("emp" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_1], "title" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_2], "direccion" => "ENVIO");
                    $error = false;
                    $mensaje = "El manifiesto se registro con exito";
                } catch (Zend_Db_Exception $zde) {
                    $error = true;
                    $mensaje = "transaccion :" . $zde->getMessage();
                }
            }
        }
        $resp["error"] = $error;
        $resp['mensaje'] = $mensaje;
        echo Zend_Json::encode($resp);
    }

    /**
     * Recibe informacion de las encomiendas que
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function removeEncomiendaAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $manifiesto = $this->getRequest()->getParam("manifiesto");
        $encomiendas = $this->getRequest()->getParam("data");
        $encomiendas = trim($encomiendas);


        if ($manifiesto == "0") {
            $mensaje = "No se ha seleccionado un viaje para las encomiendas ";
            $error = true;
        } else {
            $encomiendas = Zend_Json::decode($encomiendas);
//            ECHO "nuemrp de encomienda:[".base64_decode($encomiendas[0])."]";
//            print_r($encomiendas);
            if (count($encomiendas) == 0) {
                $error = true;
                $mensaje = "No se han seleccionado encomiendas";
            } else {
                try {
                    $ids = array();
                    foreach ($encomiendas as $id => $data) {
                        $ids[base64_decode($id)] = array("size" => count($data), "items" => array());
                        $data = split(",", $data);
                        foreach ($data as $item) {
                            $ids[base64_decode($id)]["items"][] = base64_decode($item);
                        }
                    }

                    $manModel = new App_Model_ManifiestoModel();
                    $manModel->removeEncomiendaTx($manifiesto, $ids, $this->person, $destino, $this->ciudadOrigen);
                    $encs = $manModel->getEncomiendasById($manifiesto);

                    $configuracionSM = new App_Model_ConfiguracionSistema();
                    $configuraciones = $configuracionSM->getAll();

                    $result = array();
                    foreach ($encs as $enc) {
                        $result[] = array("guia" => $enc->guia, "detalle" => $enc->detalle, "total" => $enc->total, "tipo" => $enc->tipo);
                    }
                    $resp["manifiesto"] = array("fecha" => date("Y-m-d"));
                    $resp["lista"] = $result;
                    $resp["cabecera"] = array("emp" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_1], "title" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_2], "direccion" => "ENVIO");
                    $error = false;
                    $session = Zend_Registry::get(App_Util_Statics::$SESSION);
                    $sucursalName = $session->sucursalName;
                    $mensaje = "Las encomiendas rezagadas se registraron en la sucursal ($sucursalName) con exito";
                } catch (Zend_Db_Exception $zde) {
                    $error = true;
                    $mensaje = "transaccion :" . $zde->getMessage();
                }
            }
        }
        $resp["error"] = $error;
        $resp['mensaje'] = $mensaje;
        echo Zend_Json::encode($resp);
    }

    /**
     * Muestra todas las encomiendas que le pertenecen aun manifiesto
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function listEncomiendaAction() {
        $this->_helper->layout->disableLayout();
        $idManif = $this->getRequest()->getParam("man");
        $idManif = base64_decode($idManif);

        $manModel = new App_Model_ManifiestoModel();
        $manifeistos = $manModel->getEncomiendasById($idManif);

        $this->view->manifiestos = $manifeistos;
    }

    /**
     * Muestra la interfaz de seleccion y envio de encomiendas en un itinerario
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-03-12
     */
    function showVerEncomiendaAction() {
        $this->_helper->layout->disableLayout();

        $destino = $this->getRequest()->getParam("destino");
        $destino = base64_decode($destino);
        $encModel = new App_Model_EncomiendaModel();
        $encomiendas = $encModel->getByEstadoOrigen(App_Util_Statics::$ESTADOS_ENCOMIENDA['Recibido'], $this->person->sucursal);
        $datos = array();
        foreach ($encomiendas as $encomienda) {
            $key = $encomienda["ciudad_de"];
            $encomienda["id_encomienda"] = base64_encode($encomienda["id_encomienda"]);
            if (key_exists($key, $datos)) {
                $datos[$key][] = $encomienda;
            } else {
                $datos[$key] = array();
                $datos[$key][] = $encomienda;
            }
        }
        $this->view->encomiendas = $datos;
        $this->view->itinerario = $this->makeItinerario(date("Y-m-d"), "selectForManifiesto", array());
    }

    /**
     * Recupera la informacion del viaje para mostrar en la cabecera del manifiesto
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-03-13
     */
    function showDataViajeManifiestoAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $viaje = $this->getRequest()->getParam("viaje");
        $bus = $this->getRequest()->getParam("bus");

        $bus = base64_decode($bus);
        $viaje = base64_decode($viaje);

        $viajeModel = new App_Model_Viaje();
        $busModel = new App_Model_Bus();
        $manifiestoModel = new App_Model_ManifiestoModel();
        $encomiendaModel = new App_Model_EncomiendaModel();
        $choferViajeModel = new App_Model_ChoferBus();

        $infoProp = $busModel->getPropietario($bus);
        $infoViaje = $viajeModel->findByIdAllData($viaje);

        $user = $this->person;
        $manifiesto = $manifiestoModel->getByUserViaje($user->id_persona, $viaje);
        $choferes = $choferViajeModel->getChoferesViaje($viaje);

        if ($manifiesto == false) {
            $manifiestoModel->saveTx($viaje, key($choferes), $bus, array(), $user, $infoViaje[0]->llegada, $infoViaje[0]->salida, $infoViaje[0]->fecha);
        }

        $manifiesto = $manifiestoModel->getByUserViaje($user->id_persona, $viaje);
        if ($manifiesto->chofer == "") {
            $manifiestoModel->update(array("chofer" => key($choferes)), array("id_manifiesto='$manifiesto->id_manifiesto'"));
        }
        $encomiendas = $encomiendaModel->getByManfiesto($manifiesto->id_manifiesto); //ViajeSucursal($viaje, $this->person->sucursal);
        $dataEncomienda = array();
        foreach ($encomiendas as $encomienda) {
            $dataEncomienda[] = array("id" => base64_encode($encomienda->id_item_encomienda), "idEncomienda" => base64_encode($encomienda->id_encomienda), "guia" => $encomienda->guia, "detalle" => $encomienda->detalle);
        }
        foreach ($choferes as $id => $chofer) {
            $choferes[$id]["id_chofer"] = base64_encode($chofer["id_chofer"]);
        }

        $dataViaje = array(
            "viaje" => base64_encode($infoViaje[0]->id_viaje),
            "idBus" => base64_encode($infoViaje[0]->id_bus),
            "interno" => $infoViaje[0]->interno,
            "placa" => $infoViaje[0]->placa,
            "propietario" => $infoProp->nombres,
            "fecha" => $infoViaje[0]->fecha,
            "hora" => $infoViaje[0]->hora,
            "destino" => base64_encode($infoViaje[0]->idDestino),
            "ciudadDestino" => $infoViaje[0]->destino
        );
        $json = array();
        $json["choferes"] = $choferes;
        $json["viaje"] = $dataViaje;
        $json["encomiendas"] = $dataEncomienda;
        $json["manifiesto"] = $manifiesto;
        echo Zend_Json::encode($json);
    }

    /**
     * Cambia la vista del formulario segun sea el parametro (recepcion,entrega,arribo,busqueda,equipaje)
     * caso contrario muestra siempre el formulario de recepcion
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version rc1
     * @date $(now)
     */
    function changeFormAction() {
        $this->_helper->layout->disableLayout();

        $contentRender = $this->getRequest()->getParam("content");
        $ciudadModel = new App_Model_CiudadModel();
        $ciudades = $ciudadModel->getCiudadDestinoForDropDown($this->ciudadOrigen);
        switch ($contentRender) {
            case "Entrega":
                $this->view->ciudades = $ciudades;
                $this->render('entrega');
                break;
            case "Arribo":
                $manifModel = new App_Model_ManifiestoModel();
                $sucModel = new App_Model_SucursalModel();

                $manifiestos = $manifModel->getByDate(date("Y-m-d"), $this->person->id_persona);
                $ciudadSelected = key($ciudades);
                $this->view->ciudadDestino = $ciudades;
                $this->view->ciudades = $ciudades;
                $this->view->sucursales = $sucModel->getSucursalByCiudad($ciudadSelected);
                $this->view->manifiestos = $manifiestos;
                $this->render('arribo');
                break;
            case "Envio":
                $encModel = new App_Model_EncomiendaModel();
                $facturas = $encModel->getByEstadoOrigen(App_Util_Statics::$ESTADOS_ENCOMIENDA['Recibido'], $this->person->sucursal);
                $datos = array();
                foreach ($facturas as $factura) {
                    $key = strtoupper($factura["nombre_ciudad_destino"]);
                    $factura["id_encomienda"] = base64_encode($factura["id_encomienda"]);
                    $factura["id_item_encomienda"] = base64_encode($factura["id_item_encomienda"]);
                    if (key_exists($key, $datos)) {
                        $datos[$key][] = $factura;
                    } else {
                        $datos[$key] = array();
                        $datos[$key][] = $factura;
                    }
                }

                $this->view->encomiendas = $datos;
                $this->view->itinerario = $this->makeItinerario(date("Y-m-d"), "selectForManifiesto", array());
                $this->render('envio');
                break;
            case "Busqueda":
                $ciudades = $ciudadModel->getCiudadForDropDown();
                $this->view->ciudades = $ciudades;
                $this->render('busqueda');
                break;
            case "Clave":
                $this->render('change-password');
                break;
            case "Extracto":
                $date = $this->getRequest()->getParam("fecha");
                if (!isset($date)) {
                    $date = date("Y-m-d");
                }

                $configuracionSM = new App_Model_ConfiguracionSistema();
                $configuraciones = $configuracionSM->getAll();

                $this->view->user = $this->session;
                $this->view->date = $configuraciones;
                $this->view->date = $date;
                $facturaModel = new App_Model_FacturaEncomienda();
                $facturas = $facturaModel->getByAllVendedorFecha($date, $this->person->id_persona);
                $porPagar = array();
                $porAnular = array();
                $normal = array();
                foreach ($facturas as $fac) {
                    if ($fac->is_porpagar_entregada == true) {
                        $porPagar[] = $fac;
                    } elseif ($fac->estado == "POR ANULAR") {
                        $porAnular[] = $fac;
                    } else {
                        $normal[] = $fac;
                    }
                }
                $this->view->porPagar = $porPagar;
                $this->view->porAnular = $porAnular;
                $this->view->normales = $normal;
                $this->render('abstract-detail');
                break;


            case "Clientes":

                $clienteModel = new App_Model_Cliente();
                $clientes = array();
                foreach ($clienteModel->getAll() as $cli) {
                    $clientes[] = array("nit" => $cli->nit, "nombre" => $cli->nombre, "tipo" => $cli->tipo);
                }
                $this->view->clientes = $clientes;
                $this->render('clientes');
                break;

            case "Equipaje":
                $this->view->ciudadUser = $this->session->ciudadNombre;
//                $webService = App_Util_Statics::$webService;
//                $service = $webService[$this->session->ciudadName]["ip"] . $webService["route"] . "/viaje/rest/method/modelo/format/json/userid/1/apikey/123";
//                $this->view->urlService = $service;
//                $eqPredefModel = new App_Model_EncomiendaPredefinida();
//                foreach ($eqPredefModel->getAll() as $eq) {
//                    $eqp[] = array("detalle" => $eq->descripcion, "peso" => $eq->peso, "tipo" => $eq->tipo);
//                }
//                $this->view->predef = Zend_Json::encode($eqp);

                $this->render('equipaje');
                break;
            default://will load the reception view
                $manifModel = new App_Model_ManifiestoModel();
                $sucModel = new App_Model_SucursalModel();

                $manifiestos = $manifModel->getByDate(date("Y-m-d"), $this->person->id_persona);
                $ciudadSelected = key($ciudades);
                $this->view->ciudades = $ciudades;
                $this->view->sucursales = $sucModel->getSucursalByCiudad($ciudadSelected);
                $this->view->manifiestos = $manifiestos;
                $this->render('recepcion');
                break;
        }
        return;
    }

    /**
     * recupera el extracto del vendedor sessionado 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-02-07
     */
    function extractoAction() {
        $this->_helper->layout->disableLayout();

        $fecha = $this->getRequest()->getParam("fecha");
        $ajax = $this->getRequest()->getParam("ajax");
        if (!isset($fecha) || $fecha == "undefined") {
            $fecha = date("Y-m-d");
        }

        $facturaModel = new App_Model_FacturaEncomienda();
        $configuracionSM = new App_Model_ConfiguracionSistema();
        $configuraciones = $configuracionSM->getAll();

        $totalFacturado = $facturaModel->getByVendedorFecha($fecha, $this->person->id_persona);
        $totalAnulado = $facturaModel->getDevolucionesByVendedorFecha($fecha, $this->person->id_persona);


        $this->view->ajax = $ajax;

        $this->view->configuracion = $configuraciones;
        $this->view->user = $this->session;
        $this->view->date = $fecha;
        $this->view->ingresos = $totalFacturado;
        $this->view->egresos = $totalAnulado;
    }

    /**
     * Muestra un detalle de las encomiendas recibidas la guia el estado y el monto
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-10-01 16:29
     */
    function detalleAction() {
        $this->_helper->layout->disableLayout();

        $fecha = $this->getRequest()->getParam("fecha");
        $encomiendaModel = new App_Model_EncomiendaModel();
        if ($fecha == "") {
            $fecha = date("Y-m-d");
        }
        $lista = $encomiendaModel->getByUserDateSucursal($this->person->id_persona, $fecha, $this->person->sucursal);
        $this->view->userName = $this->person->nombres . " " . $this->person->apellido_paterno;
        $this->view->lista = $lista;
    }

    /**
     * recupera el itinerario de viajes de una fecha que llega como parametro
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date $(now)
     *
     */
    function listItineraryAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $fecha = $this->getRequest()->getParam("date");
        $actions = array(
            array("icon" => "/images/icon-lista.png", "action" => "cargarManifiestos", "nombre" => "ver manifiesto", "titulo" => "Ver Maniviestos de viaje"),
            array("icon" => "/images/mover_encomiendas.gif", "action" => "moverEncomiendas", "nombre" => "mover encomiendas", "titulo" => "Mover encomiendas")
        );
        echo $this->makeItinerario($fecha, "selectViaje", $actions);
    }

    /**
     * crea una lista de viajes y maketea en un itinerario
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2012-03-12
     * 
     * @param $fecha fecha en la que se recargara el manifiesto
     * @param $javascriptAction   accion que se ejecutara al seleccionar un viaje
     * @param $viajeActions       acciones que se le permiten al usuario realizar sobre un viaje<strong>(funciones asignadas)</strong>
     */
    function makeItinerario($fecha, $javaScriptAction, $viajeActions = array()) {
        $viajeModel = new App_Model_Viaje( );
        $viajes = $viajeModel->getViajesByOrigenFecha($this->ciudadOrigen, $fecha);
        $arrayViajes = array();
        foreach ($viajes as $viaje) {
            if (!key_exists($viaje->destino, $arrayViajes)) {
                $listaB = new App_Form_ViajeListBean($this->baseURL, $javaScriptAction, $viajeActions);
                $listaB->addViaje($viaje->id_viaje, $viaje->hora, $viaje->interno, $viaje->idDestino, $viaje->bus, $viaje->modelo);
                $listaB->setDestino($viaje->destino);
                $listaB->setOrigen($viaje->origen);
                $listaB->setModelo($viaje->modelo);
                $arrayViajes [$viaje->destino] = $listaB;
                $listaB->setIdCiudadDestino($viaje->idCiudadDestino);
            } else {
                $listaB1 = $arrayViajes [$viaje->destino];
                $listaB1->addViaje($viaje->id_viaje, $viaje->hora, $viaje->interno, $viaje->idDestino, $viaje->bus, $viaje->modelo);
                $listaB1->setModelo($viaje->modelo);
                $listaB1->setIdCiudadDestino($viaje->idCiudadDestino);
            }
        }
        $itinierario = new App_Form_ItinerarioBean ( );
        $itinierario->setListaViajes($arrayViajes);
        return $itinierario->makeItinerario();
    }

    /**
     * Recupera la informacion de manifiestos segun la fecha especificada
     *
     * @param date  fecha seleccionada para listar los manifiestos
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date $(now)
     *
     */
    function listManifestAction() {
        $this->_helper->layout->disableLayout();
        $fecha = $this->getRequest()->getParam('date');
        if (!isset($fecha) || $fecha == "") {
            $fecha = date('Y-m-d');
        }


        $manifModel = new App_Model_ManifiestoModel();
        $manifiestos = $manifModel->getByDate($fecha, $this->person->id_persona);
        $this->view->manifiestos = $manifiestos;
    }

    /**
     * Lista todos los manifiesto de un viaje para ver el contenido de los mismos
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-11
     */
    function showManifiestosViajeAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $viaje = $this->getRequest()->getParam('viaje');
        $viaje = base64_decode($viaje);
        $bus = $this->getRequest()->getParam('bus');


        $manifModel = new App_Model_ManifiestoModel();
        $manifiestos = $manifModel->getByViaje($viaje);
        $listM = array();
        foreach ($manifiestos as $m) {
            $listM["m" . $m->id_manifiesto] = array("id" => $m->id_manifiesto,
                "print" => $m->despachador == $this->person->id_persona,
                "encargado" => $m->nombres,
                "fecha" => $m->fecha,
                "hora" => $m->hora,
                "destino" => $m->destino,
                "numBus" => $m->numero,
                "bus" => $m->id_bus,
                "total" => $m->total);
        }

        $encModel = new App_Model_EncomiendaModel();
        $configModel = new App_Model_ConfiguracionSistema();
        $encs = array();
        $m = 0;
        foreach ($manifiestos as $man) {
            $e = 0;
            $encos = array();
            foreach ($encModel->getByManfiesto($man->id_manifiesto) as $enc) {
                $encos["e" . $e] = array("guia" => $enc->guia, "detalle" => $enc->detalle, "total" => $enc->total, "tipo" => $enc->tipo);
                $e++;
            }
            $encs["m" . $man->id_manifiesto] = $encos;
            $m++;
        }
        $empresa = $configModel->getByKey(App_Util_Statics::$nombreEmpresa);
        $title = $configModel->getByKey(App_Util_Statics::$lemaEmpresa);
        $cabecera = array("emp" => $empresa->value, "title" => $title->value);

        $json = array();
        $json["encomiendas"] = $encs;
        $json["manifiestos"] = $listM;
        $json["cabecera"] = $cabecera;
        echo Zend_Json::encode($json);
    }

    /**
     * Recupera la planilla de un viaje
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   
     * @date 2011-04-07
     */
    function getPlanillaAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $viaje = $this->getRequest()->getParam('viaje');
        $viaje = base64_decode($viaje);
        $interno = $this->getRequest()->getParam('interno');
        $model = new App_Util_ModelBean(null);
        $model->setForViaje(true);
        $model->setViaje($viaje);
        $model->setInterno($interno);
        $json ['modelo'] = $model->makeModel();
        $datosBus = $model->getDatosViaje();
        if ($this->getRequest()->getParam("mover") == "si") {
            $json["modelBus"] = $model->getModelo();
            $json["interno"] = $model->getInterno();
        } else {
            $json ['datosBus'] = $datosBus;
            $json ['pasajeros'] = $model->getListEquipajes();
        }
        echo Zend_Json::encode($json);
    }

    /**
     * Muestra informacion del  formulario de registro de equipajes
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version   v1.3
     * @date 2011-04-11
     */
    function showEquipajeFormAction() {
        $this->_helper->layout->disableLayout();
        $asiento = $this->getRequest()->getParam("asiento");
        $tipo = $this->getRequest()->getParam("tipo");

        $asientoModel = new App_Model_Asiento();
        $configModel = new App_Model_ConfiguracionGral();

        $precioAsiento = $configModel->getByKey("franquicia_equipaje");
        $precio_X_kilo = $configModel->getByKey("precio_X_kilo");
        $dataA = $asientoModel->getById($asiento);
        $allAsientos = $asientoModel->getAsientosByFactura($dataA->factura);
        $numAsientos = count($allAsientos);

        $this->view->precioAsiento = $precioAsiento;
        $this->view->precio_X_kilo = $precio_X_kilo;
        $this->view->numAsientos = $numAsientos;
        $this->view->allAsientos = $allAsientos;
        $this->view->tipo = $tipo;
        $this->view->dataA = $dataA;
    }

    /**
     * permite registrar los equipajes mandandolos a guardar a la base de datos
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 15-03-2010
     */
    function saveEquipajeAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $nombre = $this->getRequest()->getParam("nombre");
        $nit = $this->getRequest()->getParam("nit");
        $viaje = $this->getRequest()->getParam("viaje");
        $destino = $this->getRequest()->getParam("destino");
        $chofer = $this->getRequest()->getParam("chofer");
        $destino = $this->getRequest()->getParam("destinoViaje");
        $asientos = $this->getRequest()->getParam("datos");
        $asientos = str_replace("'", '"', $asientos);
        $array = Zend_Json::decode($asientos);
        $equipajeModel = new App_Model_Equipaje();
        $configModel = new App_Model_ConfiguracionGral();
        $precioAsiento = $configModel->getByKey("franquicia_equipaje");
        try {
            $person = $this->person;
            $resp = $equipajeModel->saveTx($array, $precioAsiento, $nombre, $nit, $person, $viaje, $destino, $chofer, $destino);
            $resp["impresion"] = 0;
            $mensaje = "El equipaje se registro correctamente";
            $error = false;
        } catch (Zend_Db_Exception $zde) {
            $mensaje = $zde->getMessage();
            $error = true;
            $resp = null;
        }
        $json["mensaje"] = $mensaje;
        $json["error"] = $error;
        $json["printer"] = $resp;
        echo Zend_Json::encode($json);
    }

    /**
     * recupera la informacion de un viaje, modelo de un bus , placa, interno, fecha viaje, hora viaje
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-10
     */
    function getDetalleViajeAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $idViaje = $this->getRequest()->getParam("viaje");
        $idViaje = base64_decode($idViaje);

        $viajeModel = new App_Model_Viaje();
        $detalleViaje = $viajeModel->getDetalleViaje($idViaje);
        $choferes = array();
        foreach ($detalleViaje as $dv) {
            $choferes[] = array("id" => $dv->id_chofer, "nombre" => $dv->nombre_chofer);
        }
        $dv = $detalleViaje[0];
        $infoV = array("idV" => $dv->id_viaje, "fecha" => $dv->fecha, "hora" => $dv->hora, "interno" => $dv->numero, "modelo" => $dv->descripcion);
        $json["mensaje"] = array("datos" => $infoV, "choferes" => $choferes);
        $json["error"] = false;
        echo Zend_Json::encode($json);
    }

    /**
     * muestra el dialogo en el cual se puede realizar movimiento de una encomienda a un bus diferente
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-18
     */
    function showMoverEncomiendaAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $idViaje = $this->getRequest()->getParam("viaje");
        $fecha = $this->getRequest()->getParam("fecha");
        $idViaje = base64_decode($idViaje);

        $manModel = new App_Model_ManifiestoModel();
        $encModel = new App_Model_EncomiendaModel();
        $detalleMan = $manModel->getByUserViaje($this->person->id_persona, $idViaje);
        if ($detalleMan) {
            $listEnc = $encModel->getByManfiesto($detalleMan->id_manifiesto);
        } else {
            $detalleMan = "Este viaje no creo ningun manifiesto para el usuario(" . $this->person->identificador . ")<br/> registre una encomienda o pidale al administrador que cre el manifiesto por favor";
            $listEnc = 0;
            $envioManif = array();
        }
        $listManif = $manModel->getByDate1($fecha);
        $envioManif = array();
        $dest = "";
        foreach ($listManif as $man) {
            if ($dest != $man->destino) {
                $dest = $man->destino;
                $envioManif[$man->destino] = array(
                    "title" => $man->origen . " " . $man->destino,
                    "content" => "<li class='itineraryManifest' id='" . $man->id_manifiesto . "'>" . $man->hora . " " . $man->numero . " " . $man->nombres . "</li>"
                );
            } else {
                $envioManif[$man->destino]["content"] .= "<li class='itineraryManifest' id='" . $man->id_manifiesto . "'>" . $man->hora . " " . $man->numero . " " . $man->nombres . "</li>";
            }
        }
        $choferes = array();
        $json["selected"] = $detalleMan;
        $json["encomiendas"] = $listEnc;
        $json["manifiestos"] = $envioManif;
        echo Zend_Json::encode($json);
    }

    /**
     * Recupera la lista de encomiendas de un manifiesto
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-19
     */
    function getEncomiendasAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $idManifiesto = $this->getRequest()->getParam("manifiesto");

        $manModel = new App_Model_ManifiestoModel();
        $encModel = new App_Model_EncomiendaModel();
        $sistemaModel = new App_Model_ConfiguracionSistema();

        $cabecera = array();
        $cabecera["empresa"] = $sistemaModel->getByKey(App_Util_Statics::$TITULO_FACTURA_2)->value;
        $cabecera["title"] = $sistemaModel->getByKey(App_Util_Statics::$TITULO_FACTURA_1)->value;
        $cabecera["direccion"] = " ENVIO ";
        $cabecera["usuario"] = $this->person->identificador;

        $dataManifiesto = $manModel->findById($idManifiesto);
        $listEnc = $encModel->getByManfiesto($idManifiesto);
        $json["encomiendas"] = $listEnc;
        $json["info"] = $dataManifiesto;
        $json["cabecera"] = $cabecera;
        echo Zend_Json::encode($json);
    }

    /**
     * recibe el id de un manifiesto y el ide de una encomiend para cambiar el manifiesto en la encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-26
     */
    function moveEncomiendaAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $idManifiesto = $this->getRequest()->getParam("manifiesto");
        $idencomienda = $this->getRequest()->getParam("enc");
        $error = false;
        $info = "Se registro el movimiento";
        $encModel = new App_Model_EncomiendaModel();
        $encData = $encModel->getById($idencomienda);
        if ($encData->manifiesto == $idManifiesto) {
            $error = true;
            $info = "No puede mover una a encomienda a un mismo manifiesto";
        } else {
            $encModel->updateManifiesto($idencomienda, $costoEncom, $idManifiesto);
        }
        $json["error"] = $error;
        $json["info"] = $info;
        echo Zend_Json::encode($json);
    }

    /**
     * Registra el envio de encomiendas
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation date
     */
    function registerSendAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $idManifiesto = $this->getRequest()->getParam("manifiesto");
        $bus = $this->getRequest()->getParam("bus");
        $error = false;
        $info = "Se registro el envio del manifiesto";
        $encModel = new App_Model_EncomiendaModel();
        try {
            $encModel->saveMovimientoEncomienda(App_Util_Statics::$ESTADOS_ENCOMIENDA['Envio'], $idManifiesto, $this->person, $bus);
        } catch (Zend_Db_Exception $exc) {
            $exc->getTraceAsString();
            $error = true;
            $info = $exc->getMessage();
        }

        $json["error"] = $error;
        $json["info"] = $info;
        echo Zend_Json::encode($json);
    }

    /**
     * Registra una factura por la entrega de una encomienda por pagar
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-09-2012 11:48
     */
    function saveEntregaPorpagarAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $params = $this->getRequest()->getParams();

        unset($params['module']);
        unset($params['action']);
        unset($params['controller']);

        $data = $params;
        $encomiendaModel = new App_Model_EncomiendaModel();
        try {
            if (strlen($data['nit']) > 15) {
                throw new Zend_Db_Exception("El valor del nit eta fuera de rango por favor verifique e intentelo nuevamente", 1);
            }
            $tipoFactura = $data['encomienda']['tipoFactura'];
//            echo "Tipo de factura es ".$tipoFactura;
            if ($tipoFactura == "manual") {
                $dataPrint = $encomiendaModel->savePorPagarManual($data['encomienda'], $data['items'], $this->person, $this->nombreCiudadVendedor);
            } else {
                $dataPrint = $encomiendaModel->savePorPagar($data['encomienda'], $data['items'], $this->person, $this->nombreCiudadVendedor);
                $dataPrint['tipoFactura'] = 'Automatica';
            }
            $error = false;
            $mensaje = $dataPrint;
        } catch (Zend_Db_Exception $ze) {
            $error = true;
            $mensaje = $ze->getMessage();
        }

        $json = array();
        $json["error"] = $error;
        $json["info"] = $mensaje;
        echo Zend_Json::encode($json);
    }

    /**
     * Registra un log de impresion de documentos con el applet
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-09-03 16:25
     */
    function logImpresionAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $ex = $this->getRequest()->getParams();

        unset($ex['module']);
        unset($ex['action']);
        unset($ex['controller']);
//        $ex = print_r($ex, true);
        $log = Zend_Registry::get("logImpresion");
        $log->info($ex['dataPrint']);
        echo $ex['dataPrint'];
    }

    /**
     * Muestra el formulario de datos de la factura ya sea computarizada o manual
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-09-12 15:23
     */
    function showFormFacturaAction() {
        $this->_helper->layout->disableLayout();

        $nombre = $this->getRequest()->getParam("nombre");

        $this->view->nombre = $nombre;
    }

    function updatePasswordAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $current = $this->getRequest()->getParam("current");
        $tochange = $this->getRequest()->getParam("new");
        $confirm = $this->getRequest()->getParam("confirm");
        $error = false;
        $message = "La contraseña ha sido modificada satisfactoriamente.";
        $userModel = new App_Model_Persona();
        if ($current == "" || $tochange == "" || $confirm == "") {
            $message = "Ninguno de los 3 campos puede ser vacio <br/> por favor revice los valores";
            $error = true;
        } else {
            $current = md5($current);
            $session = Zend_Registry::get(App_Util_Statics::$SESSION);
            $pass = $userModel->getPassword($session->person->id_persona);
            if ($current != $pass) {
                $message = "La contraseña actual no coincide intentelo nuevamente por favor</br> curr";
                $error = true;
            } elseif ($tochange != $confirm) {
                $message = "La confirmacion de la nueva contraseña no coincide intentelo nuevamente por favor";
            } else {
                $dataToUpdate = array("contrasenia" => md5($tochange));
                if ($userModel->updateTX($dataToUpdate, $session->person->id_persona) === false) {
                    $message = "No se pudo actualizar la contraseña error de sistema";
                    $error = true;
                }
            }
        }
        $json = array();
        $json["error"] = $error;
        $json["info"] = $message;
        echo Zend_Json::encode($json);
    }

    /**
     * Muestra la interfaz de registro de una factura manual con toda la 
     * informacion de la anterior factura si existe
     */
    function showAddFacturaManualAction() {

        $this->_helper->layout->disableLayout();

        $idEncomienda = $this->getRequest()->getParam("id");
        $idEncomienda = base64_decode($idEncomienda);

        $dosificacionModel = new App_Model_DosificacionModel();
        $encomiendaModel = new App_Model_EncomiendaModel();
        $facturaEncomienda = $encomiendaModel->getFactura($idEncomienda);
        $this->view->factura = $facturaEncomienda;
        $this->view->dosificaciones = $dosificacionModel->getManualBySucusalSistema($this->person->sucursal, App_Util_Statics::$SYSTEM);
    }

    function addFacturaManualEncomiendaAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $idEncomienda = $this->getRequest()->getParam("id");
        $idEncomienda = base64_decode($idEncomienda);

        $params = $this->getRequest()->getParams();
        $params = $this->getRequest()->getParams();

        unset($params['module']);
        unset($params['action']);
        unset($params['controller']);

        $data = $params;
        $encomiendaModel = new App_Model_EncomiendaModel();
        $encomiendaModel->updateToFacturaManual($idEncomienda, $data, $this->person);
    }

    function getDosificacionManualAction() {
        $this->_helper->viewRenderer->setNoRender();
        $this->_helper->layout->disableLayout();

        $dosModel = new App_Model_DosificacionModel();

        $dosificaciones = $dosModel->getManualBySucusalSistema($this->person->sucursal, App_Util_Statics::$SYSTEM);
        $data = "";
        foreach ($dosificaciones as $key => $value) {
            $data .= "<option value='$key'>$value</option>";
        }
        $json = array();
        $json["error"] = false;
        $json["options"] = $data;
        echo Zend_Json::encode($json);
    }

    /**
     * Funcion encargada de mostrar la informacion de la
     * factura y encomienda que sera anulada
     */
    function showMoveToCancelAction() {
        $this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam("id");
        $id = base64_decode($id);

        $idCiudadOrigen = $this->getRequest()->getParam("ciudadOrigen");
        $userCiudadId = $this->getRequest()->getParam("userCiudadId");
        $userCiudadId = base64_decode($userCiudadId);
        $idCiudadOrigen = base64_decode($idCiudadOrigen);

        $encomiendaM = new App_Model_EncomiendaModel();
        $encomienda = $encomiendaM->getById($id);
        $vendedor = $encomiendaM->getReceptor($id);
        $active = true;
        if ($idCiudadOrigen != $userCiudadId) {
            $active = false;
            $errorType = 1;
        }

        if ($encomienda->estado != "RECIBIDO" && $encomienda->estado != "ENVIADO" && $encomienda->estado != "TRASPASO") {
            $active = false;
            $errorType = 2;
        }
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        if ($vendedor->id_persona != $session->person->id_persona) {
            $this->view->vendedor = $vendedor->nombres;
            $this->view->actual = $session->person->nombres;
            $active = false;
            $errorType = 3;
        }

        echo '<input id="activeForCancel" type="hidden" value="' . ($active) . '" />';
        $this->view->encomienda = $encomienda;
        $facturaM = new App_Model_EncomiendaModel();
        $this->view->factura = $encomiendaM->getFactura($id);
        $this->view->active = $active;
        $this->view->errorType = $errorType;
    }

    function saveMoveToCancelAction() {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $id = $this->getRequest()->getParam("id");
        $id = base64_decode($id);

        $encomiendaM = new App_Model_EncomiendaModel();
        $response = $encomiendaM->moveToCancel($id);
        echo Zend_Json::encode($response);
    }

    /**
     * Search clientes by some of the fields
     * @return JsonArray list of clientes found by filters
     */
    function searchClientsByAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $nombre = $this->getRequest()->getParam("nombre");
        $nit = $this->getRequest()->getParam("nit");
        $tipo = $this->getRequest()->getParam("tipo");

        $clienteModel = new App_Model_Cliente();
        echo Zend_Json::encode($clienteModel->searchBy($nombre, $nit, $tipo));
    }

    /**
     * Save a new client
     */
    function saveClientAction() {

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $clientId = $this->getRequest()->getParam("clientId");
        $nombre = $this->getRequest()->getParam("nombre");
        $nit = $this->getRequest()->getParam("nit");
        $tipo = $this->getRequest()->getParam("tipo");
        $cliente = array("nombre" => $nombre, "nit" => $nit, "tipo" => $tipo, "estado" => "Activo");

        $clienteModel = new App_Model_Cliente();
        if (isset($clientId) && $clientId == "") {
            echo Zend_Json::encode($clienteModel->txSave($cliente));
        } else {
            echo Zend_Json::encode($clienteModel->txUpdate($cliente, $clientId));
        }
    }

}
