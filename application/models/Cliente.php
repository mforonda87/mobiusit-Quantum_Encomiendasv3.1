<?php

/**
 * SucursalModel
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Cliente extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'cliente';
    protected $_sequence = false;

    /**
     * Muestra el dialogo de venta para los asientos seleccionados
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
    function getAll() {
        $logger = Zend_Registry::get('log');
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('cl' => 'cliente'), array('nit', 'nombre', 'tipo'));
        $select->where('cl.estado=?', 'Activo');
        $select->order('nit');
        $results = $db->fetchAll($select);

        return $results;
    }

    /**
     * * Busca clientes por un termino en el nombre
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @version beta
     * @date $(date)
     * TODO :
     */
    function searchByTerm($term) {
        $logger = Zend_Registry::get('log');

        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('cl' => 'cliente'), array('nombre', 'nit', 'tipo'));
        $select->where('cl.nit like ?', $term . '%');
        $select->order('nombre');
        $select->limit(20, 0);
        $results = $db->fetchAll($select);
        $logger->err(Zend_Json::encode($results));

        return $results;
    }

    /**
     * guarda la informacion de un cliente
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
    function txSave($cliente) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $clientId = $this->insert($cliente);
            $db->commit();
            return array("error" => false, "clientId" => $clientId, "message" => "Registro Exitoso");
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            if (strpos($zdbe->getMessage(), 'Unique violation') !== false) {
                return array("error" => true, "message" => "The client with nit " . $cliente['nit'] . " already Exist");
            }

            $log->err("DB Transaction persisting client" . Zend_Json::encode($cliente));
            $log->err($zdbe->getTraceAsString());
            throw new Zend_Db_Exception($zdbe, 125);
        }
    }

    function findByNIT($nit) {
        $log = Zend_Registry::get("log");
        $log->info("Finding by nit " . $nit);
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('cl' => 'cliente'), array('id' => 'id_cliente', 'nombre', 'nit', 'tipo', 'deuda'));
        $select->where('cl.nit =?', $nit);
        $select->order('nombre');
        $results = $db->fetchRow($select);

        return $results;
    }

    /**
     * actualiza la informacion de un cliente
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
    function txUpdate($cliente, $id) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $where [] = "id_cliente = '$id'";
            $clientId = $this->update($cliente, $where);
            $db->commit();
            return array("error" => false, "clientId" => $clientId, "message" => "Actualizacion Exitosa");
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            if (strpos($zdbe->getMessage(), 'Unique violation') !== false) {
                return array("error" => true, "message" => "Cannot update the client to NIT " . $cliente['nit'] . " the NIT already Exist");
            }

            $log->err("DB Transaction persisting client" . Zend_Json::encode($cliente));
            $log->err($zdbe->getTraceAsString());
            throw new Zend_Db_Exception($zdbe, 125);
        }
    }

    /**
     * Serch clientes accordint to set variables
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @version beta
     * @date 2020/11/29
     * TODO :
     */
    function searchBy($name, $nit, $type) {
        $logger = Zend_Registry::get('log');

        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('cl' => 'cliente'), array('id' => 'id_cliente', 'nombre', 'nit', 'tipo', 'deuda'));
        if (isset($name) && $name != "" && $name != "0") {
            $select->where('cl.nombre like ?', '%' . $name . '%');
        }
        if (isset($nit) && $nit != "") {
            $select->where('cl.nit like ?', $nit . '%');
        }
        if (isset($type) && $type != "" && $type != "all") {
            $select->where('cl.tipo = ?', $type);
        }
        $select->order('nombre');
        $select->limit(20, 0);
        $logger->err("cliente select " . $select->__toString());
        $results = $db->fetchAll($select);

        return $results;
    }

    function payPackages($clientId, $idEncomiendas) {
        require_once(__DIR__.'/../../library/phpqrcode/qrlib.php');
        $db = $this->getAdapter();
        $db->beginTransaction();
        $client = $this->fetchRow(array("id_cliente='$clientId'"));
        if ($client == null) {
            return array("error" => true, "message" => "The clien does not exist");
        }

        $facModel = new App_Model_FacturaEncomienda();
        $dosificacionModel = new App_Model_DosificacionModel ( );
        $sucursalModel = new App_Model_SucursalModel();
        $movimientoVendedorModel = new App_Model_MovimientoVendedor();
        $configuracionSM = new App_Model_ConfiguracionSistema();
        $encomiendaModel = new App_Model_EncomiendaModel();

        $empresa = $configuracionSM->getEmpresaHeader();
        $encomiendaList = $encomiendaModel->getByIds($idEncomiendas);
        $totalFactura = 0;
        $itemsReturn = array();
        $i = 0;
        $guias = "";
        foreach ($encomiendaList as $encomienda) {
            $totalFactura += $encomienda->total;
            $newItem = array(
                "id_item_encomienda" => $i--,
                "cantidad" => 1,
                "detalle" => $encomienda->detalle,
                "monto" => $encomienda->total,
                "peso" => 1,
                "encomienda" => $encomienda->id_encomienda
            );
            $newItem["total"] = $encomienda->total;
            $guias.=$encomienda->guia.", ";
            $itemsReturn[] = $newItem;
            unset($newItem);
        }
        if ($client->deuda < $totalFactura) {
            return array("error" => true, "Message" => "El monto total es mayor al adeudado, por favor cargue de nuevvo la pagina");
        }
        $hoy = date("Y-m-d");
        $fecha = date("Y-m-d");
        $hora = date("H:i:s");

        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        $user = $session->person;

        $suc = $sucursalModel->getById($user->sucursal);
        $cabecera = App_Util_Facturacion::getFacturaHeader($suc, $user->nombres, $session->ciudadName);
        /*         * *************************   FACTURACION ******************** */
        $dosificacion = $dosificacionModel->getLastAutomaticoBySucursal($user->sucursal);

        if (!isset($dosificacion) || !$dosificacion) {
            throw new Zend_Db_Exception("No existe una dosificacion Activa");
        }
        if ($dosificacion->fecha_limite < $hoy) {
            throw new Zend_Db_Exception(" La Fecha limite de facturacion ha expirado por favor registre una nueva dosificacion");
        }

        $facturacionBean = new App_Util_Facturacion();
        $factura = $facturacionBean->newFactura($user, $dosificacion, $client, $totalFactura);
        $facModel->insert($factura);

        $whereFA [] = "vendedor='" . $user->id_persona . "'";
        $whereFA [] = "monto=" . $totalFactura;
        $whereFA [] = "tipo='Automatico'";
        $whereFA [] = "fecha='$hoy'";
        $whereFA [] = "texto_factura='En proceso de codigo de control'";
        $facturaA = $facModel->fetchRow($whereFA);

        if (!isset($facturaA)) {
            throw new Zend_Db_Exception(" No se Pudo Recuperar la Ultima Factura ");
        }

        $updateFactura ["codigo_control"] = $facturacionBean->generarCodigoControl($dosificacion->autorizacion, $facturaA->numero_factura, $hoy, $totalFactura, $dosificacion->llave, $nit);
        $updateFactura ["texto_factura"] = "Finalizado";
        $whereFCC = array("id_factura='$facturaA->id_factura'");
        if ($facModel->update($updateFactura, $whereFCC) == 0) {
            throw new Zend_Db_Exception(" No se Pudo Recuperar la Ultima Factura con codigo de control");
        }
        $ultimaFactura = $facModel->fetchRow($whereFCC);

        $fechaFactura = new Zend_Date($factura["fecha"], null, 'es_BO');
        $fechaPrint = $fechaFactura->toString("dd/MMM/YYYY");


        $fileName = 'qt_'.date("YmdHis").'.png';
        $pngAbsoluteFilePath = dirname(dirname(dirname( __FILE__))).'/public/images/temp/qrs/'.$fileName;
        $baseUrl = new Zend_View_Helper_BaseUrl();
        $url_qr = $baseUrl->baseUrl().'/images/temp/qrs/'.$fileName;
        if (!file_exists($pngAbsoluteFilePath)) {
            $qr_string = $empresa['nit'].'|'.$empresa['title'].'|'.$factura['numerofactura'].'|'.$factura['autorizacion'];
            $qr_string .= '|'.$factura['fecha'].'|'.$factura['total'].'|'.$factura['codigoControl'];
            $qr_string .= '|'.$factura['fechaLimite'].'|0|0|'.$factura['nit'].'|'.$factura['nombre'];
            QRcode::png($qr_string, $pngAbsoluteFilePath, QR_ECLEVEL_L, 5);
        }
        $result['url_qr'] = $url_qr;


        $datosFactura = array(
            "fecha" => $fechaPrint,
            "hora" => $factura["hora"],
            "nombre" => strtoupper($factura["nombre"]),
            "nit" => $factura["nit"],
            "numerofactura" => "$ultimaFactura->numero_factura",
            "autorizacion" => $dosificacion->autorizacion,
            "codigoControl" => $ultimaFactura->codigo_control,
            "fechaLimite" => $factura["fecha_limite"],
            "total" => $factura["monto"],
            "totalLiteral" => App_Util_Statics::num2letras($totalFactura, true, true, "Bolivianos")
        );
        $encomienda = array();
        $encomienda["factura"] = $ultimaFactura->id_factura;
        $encomiendaModel->updateFacturaToEncomiendas($idEncomiendas, $encomienda);
        $movimientoVendedorModel->createMovimientoFacturacionCliente($user->id_persona, $fecha, $hora, $totalFactura, $idEncomiendas, "Facturacion Cliente");


        $this->update(array('deuda' => new Zend_Db_Expr('deuda - ' . $totalFactura)), array("id_cliente='$clientId'"));
        $datosEncomienda = array(
            "detalle" => $guias,
            "origen" => $suc->nombre,
            "remitente"=>"",
            "destino"=>"",
            "destinatario"=>"",
            "guia"=>$guias,
            "remitente"=>"ClienteCoorporativo",
            "total" => $totalFactura,
            "tipo"=>"NORMAL",
            "telefonoDestinatario"=>"",
            "declarado"=>false,
            "observacion"=>"Facturacion coorporativa",
            "ciudadDestino"=>""
        );

        $db->commit();

        $resp["factura"] = $datosFactura;
        $resp["encomienda"] = $datosEncomienda;        
        $resp["cabecera"] = $cabecera;
        $resp["empresa"] = $empresa;
        $resp["items"] = $itemsReturn;
        $resp["url_qr"] = $url_qr;
        $resp["error"] = false;


        return $resp;
    }

}
