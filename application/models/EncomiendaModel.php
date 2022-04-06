<?php

/**
 * SucursalModel
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_EncomiendaModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'encomienda';
    protected $_sequence = false;

    /**
     * Recupera un registro de la tabla encomiendas por un id de encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getById($id) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('guia', 'detalle', 'total', 'manifiesto', 'tipo', 'nombre_destino', 'nombre_ciudad_destino', 'estado'));
        $select->join(array("so" => "sucursal"), "e.sucursal_or=so.id_sucursal", array("nombre as sucursal_origen", "id_sucursal as idSucursalOrigen"));
        $select->join(array("co" => "ciudad"), "so.ciudad=co.id_ciudad", array("nombre as ciudad_origen"));
        $select->where("e.id_encomienda='$id'");
//        echo $select->__toString();
        return $db->fetchRow($select);
    }

    /**
     * Permite registrar contra la base de datos una encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.net.bo
     * @version beta
     * @date $(2010-06-16)
     */
    function txSave($datos, $items, $user, $nombreCiudadVendedor) {
        $db = $this->getAdapter();
        $db->beginTransaction();


        $ciudadM = new App_Model_CiudadModel();
        $itemM = new App_Model_ItemEncomienda();
        $facModel = new App_Model_FacturaEncomienda();
        $dosificacionModel = new App_Model_DosificacionModel ( );
        $sucursalModel = new App_Model_SucursalModel();
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $movimientoVendedorModel = new App_Model_MovimientoVendedor();
        $configuracionSM = new App_Model_ConfiguracionSistema();
        $cooporativaModel = new App_Model_EncomiendaCoorporativa();
        // instead use $configuracionSM->getEmpresaHeader();
        $configuraciones = $configuracionSM->getAll();

        $hoy = date("Y-m-d");
        $idCiudadDestino = base64_decode($datos['ciudadDest']);
        $ciudadDestino = $ciudadM->findByid($idCiudadDestino);

        $log = Zend_Registry::get("log");

        try {
            $suc = $sucursalModel->getById($user->sucursal);
            $ciudadOrigen = $ciudadM->findByid($suc->id_ciudad);

            $totalFactura = $datos['total'];
            $fecha = date("Y-m-d");
            $hora = date("H:i:s");
            $empresa = array(
                "title" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_1],
                "nombre" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_2],
                "nit" => $configuraciones[App_Util_Statics::$nitEmpresa]
            );

            $cabecera = array(
                "numeroSuc" => "$suc->numero",
                "nombSuc" => $suc->nombre,
                "telefono" => $suc->telefono,
                "direccion" => $suc->direccion,
                "direccion2" => $suc->direccion2,
                "ciudad" => $nombreCiudadVendedor,
                "usuario" => $user->nombres,
                "autoimpresor" => "",
                "leyendaActividad" => App_Util_Statics::$leyendaActividad,
                "tipoFactura" => "Encomienda",
                "ciudadCapital" => $suc->capital,
                "ciudad2" => $suc->nombre2,
                "municipio" => "$suc->municipio",
                "leyendaSucursal" => $suc->leyenda
            );
            $datosFactura = null;
            /*             * *************************   FACTURACION ******************** */
            $tipoEncomienda = base64_decode($datos["tipoEncomienda"]);
            $numeroFactura = "";

            if ($tipoEncomienda == "NORMAL" || $tipoEncomienda == "GIRO") {

                $dosificacion = $dosificacionModel->getLastAutomaticoBySucursal($user->sucursal);

                if (!isset($dosificacion) || !$dosificacion) {
                    throw new Zend_Db_Exception("No existe una dosificacion Activa");
                }
                if ($dosificacion->fecha_limite < $hoy) {
                    throw new Zend_Db_Exception(" La Fecha limite de facturacion ha expirado por favor registre una nueva dosificacion");
                }
                $nit = $datos['Nit'] == "" ? 0 : $datos['Nit'];
                $nombre = $datos['nombreFactura'] == "" ? "SIN NOMBRE" : strtoupper($datos['nombreFactura']);
                $facturacionBean = new App_Util_Facturacion();
                $factura["id_factura"] = "-1";
                $factura["vendedor"] = $user->id_persona;
                $factura["dosificacion"] = $dosificacion->id_datos_factura;
                $factura["nombre"] = $nombre;
                $factura["nit"] = $nit;
                $factura["fecha"] = $hoy;
                $factura["hora"] = $hora;
                $factura["monto"] = $totalFactura;
                $factura["texto_factura"] = "En proceso de codigo de control";
                $factura["fecha_limite"] = $dosificacion->fecha_limite;
                $factura["tipo"] = "Automatico";
                $factura["estado"] = "Activo";
                $factura["impresion"] = 1;


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


                $literal = App_Util_Statics::convertNumber($totalFactura);

                $fechaFactura = new Zend_Date($factura["fecha"], null, 'es_BO');
//                $fechaPrint = $fechaFactura->toString("d/m/Y");
                $fechaPrint = DateTime::createFromFormat('Y-m-d', $factura["fecha"])->format('d/m/Y');
                $datosFactura = array(
                    "fecha" => $fechaPrint,
                    "hora" => $factura["hora"],
                    "nombre" => strtoupper($factura["nombre"]),
                    "nit" => $factura["nit"],
                    "numerofactura" => "$ultimaFactura->numero_factura",
                    "autorizacion" => $dosificacion->autorizacion,
                    "codigoControl" => $ultimaFactura->codigo_control,
                    "fechaLimite" => DateTime::createFromFormat('Y-m-d', $factura["fecha_limite"])->format('d/m/Y'),
                    "total" => $factura["monto"],
                    "totalLiteral" => App_Util_Statics::num2letras($totalFactura, true, true, "Bolivianos")
                );
                $numeroFactura = "$ultimaFactura->numero_factura";
            }

            $codigoGuia = $this->getCodigo($ciudadDestino['abreviacion'], $suc, $configuraciones[App_Util_Statics::$NUMERACION_GUIA]);
            $sucDestino = $sucursalModel->getById(base64_decode($datos['destinoSuc']));
            $codigoGuia .= $datos['abreviacionDestino'];
            $declarado = null;
            $obsEncomienda = null;

            if ($datos['declarado'] == "true") {
                //TODO: este deberia ser un monto por el valor declarado por ahora 1 significa que se ha declarado el valor 
                $declarado = 1;
                $obsEncomienda = "Obs. Encomienda Con valor declarado";
            }

            $encomienda = array(
                "id_encomienda" => "0",
                "fecha" => $fecha,
                "hora" => $hora,
                "remitente" => strtoupper($datos['remitente']),
                "destinatario" => strtoupper($datos['destinatario']),
                "telefono_remitente" => $datos['remitenteTelf'],
                "telefono_destinatario" => $datos['destinatarioTelf'],
                "sucursal_or" => $user->sucursal,
                "sucursal_de" => base64_decode($datos['destinoSuc']),
                "ciudad_de" => $idCiudadDestino,
                "nombre_destino" => $datos['nombreDestino'],
                "nombre_ciudad_destino" => $datos['nombreCiudadDestino'],
                "guia" => $codigoGuia,
                "tipo" => base64_decode($datos['tipoEncomienda']),
                "total" => $datos['total'],
                "detalle" => $datos['detalle'],
                "carnet_recepcion" => (isset($datos['Nit'])? $datos['Nit'] : $datos['nitCliente']),
                "valor_declarado" => $declarado,
                "observacion" => $obsEncomienda,
                "estado" => "RECIBIDO",
                "puerta_puerta" => (isset($datos['puertaPuerta']))? $datos['puertaPuerta'] : 0
            );
            if (strtoupper($tipoEncomienda) == "NORMAL" || $tipoEncomienda == "GIRO") {
                $encomienda["factura"] = $facturaA->id_factura;
            }
            $this->insert($encomienda);
            $sql = $db->select();
            $sql->from($this->_name);
            $where[] = "fecha='" . $fecha . "'";
            $where[] = "hora='" . $hora . "'";
            $where[] = "remitente='" . strtoupper($datos['remitente']) . "'";
            $where[] = "sucursal_de='" . base64_decode($datos['destinoSuc']) . "'";
            $where[] = "tipo='" . base64_decode($datos['tipoEncomienda']) . "'";
            $encomiendaR = $this->fetchRow($where);

            $movimiento = array(
                "id_movimiento" => "0",
                "fecha" => $hoy,
                "hora" => $hora,
                "movimiento" => "RECIBIDO",
                "usuario" => $user->id_persona,
                "encomienda" => $encomiendaR->id_encomienda,
                "sucursal" => $user->sucursal,
                "observacion" => "Recepcion normal",
            );
            $movimientoModel->insert($movimiento);
            if ($tipoEncomienda == "Coorporativo") {
                $log->info("Saving Corporate data" . $tipoEncomienda);
                $cooporativaModel->insertTX($encomiendaR, $datos['nitCliente']);
            }
            $i = 0;
            $itemsReturn = array();

            foreach ($items as $item) {
                if (($tipoEncomienda == "INTERNO" && $item["cantidad"] != "") || ($item["cantidad"] != "" && $item["valor"] != "0")) {
                    $newItem = array(
                        "id_item_encomienda" => $i--,
                        "cantidad" => $item["cantidad"],
                        "detalle" => $item["detalle"],
                        "monto" => $item["valor"],
                        "peso" => $item["peso"],
                        "encomienda" => $encomiendaR->id_encomienda
                    );
                    $itemM->insert($newItem);
                    $newItem["total"] = $item["valor"];
                    $itemsReturn[] = $newItem;
                    unset($newItem);
                }
            }
            if (count($itemsReturn) <= 0) {
                throw new Zend_Db_Exception("La encomienda no tiene items", 101);
            }
            $movimientoVendedorModel->createMovimientoRecepcionEncomienda($user->id_persona, $hoy, $hora, $tipoEncomienda, $numeroFactura, $datos['total'], "");
            $db->commit();
            $session = Zend_Registry::get(App_Util_Statics::$SESSION);
            $sucD = $sucursalModel->getById($encomienda["sucursal_de"]);
            $declarado = $encomienda["valor_declarado"] != null ? "Obs: Encomienda con valor declarado" : "Obs: Sin Dinero ni objetos de valor / contenido no declarado";
            $datosEncomienda = array(
                "idEncomienda" => base64_encode($encomiendaR->id_encomienda),
                "remitente" => $encomienda["remitente"],
                "destinatario" => $encomienda["destinatario"],
                "telefonoDestinatario" => $datos['destinatarioTelf'],
                "origen" => $session->sucursalName,
                "destino" => strtoupper($datos['nombreDestino']),
                "ciudadDestino" => strtoupper($datos['nombreCiudadDestino']),
                "detalle" => $encomienda["detalle"],
                "guia" => $encomienda["guia"],
                "total" => $encomienda["total"],
                "declarado" => $encomienda["valor_declarado"],
                "observacion" => $declarado
            );

            $resp["factura"] = $datosFactura;
            $resp["encomienda"] = $datosEncomienda;
            $resp["cabecera"] = $cabecera;
            $resp["empresa"] = $empresa;
            $resp["items"] = $itemsReturn;

            return $resp;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->err($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     * Este metodo generara el numero de guia si el tipo es 
     *  INDEPENDIENTE se toma el codigo de ciudad-numero-sucursal-numero-encomienda i.e. CBB-05-125
     *  (NO EN USO) FACTURA       se tomara el numero de factura como numero de guia  
     *  OTRO CASO     se toma la abreviacion de la ciudad-numero-encomienda i.e. H-125    
     */
    function getCodigo($codigoCiudad, $sucursal, $tipo) {
        if ($tipo == "INDEPENDIENTE") {
            $numeroSuc = $sucursal->numero < 10 ? "0" . $sucursal->numero : $sucursal->numero;
            $codigoGuia = $codigoCiudad . "-" . $numeroSuc . "-" . ($this->getMaxGuia($sucursal->id_sucursal) + 1);
        } else {
            $codigoGuia = $sucursal->abreviacion . "-" . ($this->getMaxGuia($sucursal->id_sucursal) + 1);
        }
        return $codigoGuia;
    }

    /**
     * Permite registrar una encomienda por pagar que se entrego
     * para ello registrara una nueva encomienda que no tendra 
     * mas que un movimiento en de entrega
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.net.bo
     * @version rc1
     * @date $(2010-06-16)
     */
    function savePorPagar($encomienda, $items, $user, $nombreCiudadVendedor) {
        $db = $this->getAdapter();
        $db->beginTransaction();


        $ciudadM = new App_Model_CiudadModel();
        $itemM = new App_Model_ItemEncomienda();
        $facModel = new App_Model_FacturaEncomienda();
        $dosificacionModel = new App_Model_DosificacionModel ( );
        $sucursalModel = new App_Model_SucursalModel();
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $configuracionSM = new App_Model_ConfiguracionSistema();
        $configuraciones = $configuracionSM->getAll();

        $hoy = date("Y-m-d");


        $log = Zend_Registry::get("log");
        try {
            $suc = $sucursalModel->getById($user->sucursal);
            $session = new Zend_Session_Namespace(App_Util_Statics::$SESSION);
            $idCiudadDestino = $session->ciudadID;
            $totalFactura = $encomienda['total'];
            $fecha = date("Y-m-d");
            $hora = date("H:i:s");
            $cabecera = array(
                "numeroSuc" => $suc->numero,
                "nombSuc" => $suc->nombre,
                "telefono" => $suc->telefono,
                "direccion" => $suc->direccion,
                "direccion2" => $suc->direccion2,
                "ciudad" => $nombreCiudadVendedor,
                "usuario" => $user->nombres,
                "autoimpresor" => "",
                "leyendaActividad" => App_Util_Statics::$leyendaActividad,
                "leyendaSucursal" => $suc->leyenda,
                "tipoFactura" => "Encomienda",
                "ciudadCapital" => $suc->capital,
                "ciudad2" => $suc->nombre2,
                "municipio" => "$suc->municipio"
            );
            $empresa = array(
                "title" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_1],
                "nombre" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_2],
                "nit" => $configuraciones[App_Util_Statics::$nitEmpresa],
            );
            $datosFactura = null;
            /*             * *************************   FACTURACION ******************** */
            $log->info("Recuperando la dosificacion");
            $dosificacion = $dosificacionModel->getLastAutomaticoBySucursal($user->sucursal);

            if (!isset($dosificacion) || !$dosificacion) {
                throw new Zend_Db_Exception("No existe una dosificacion Activa");
            }
            if ($dosificacion->fecha_limite < $hoy) {
                throw new Zend_Db_Exception(" La Fecha limite de facturacion ha expirado por favor registre una nueva dosificacion");
            }

            $nit = $encomienda['nit'];
            $nombre = strtoupper($encomienda['nombreFactura']);
            $facturacionBean = new App_Util_Facturacion();
            $factura["id_factura"] = "-1";
            $factura["vendedor"] = $user->id_persona;
            $factura["dosificacion"] = $dosificacion->id_datos_factura;
            $factura["nombre"] = $nombre;
            $factura["nit"] = $nit;
            $factura["fecha"] = $hoy;
            $factura["hora"] = $hora;
            $factura["monto"] = $totalFactura;
            $factura["texto_factura"] = "En proceso de codigo de control";
            $factura["fecha_limite"] = $dosificacion->fecha_limite;
            $factura["tipo"] = "Automatico";
            $factura["estado"] = "Activo";
            $factura["impresion"] = 1;


            $log->info("Insertando la factura computarizada");
            $facModel->insert($factura);
            $whereFA [] = "vendedor='" . $user->id_persona . "'";
            $whereFA [] = "monto=" . $totalFactura;
            $whereFA [] = "tipo='Automatico'";
            $whereFA [] = "fecha='$hoy'";
            $whereFA [] = "texto_factura='En proceso de codigo de control'";
            $facturaA = $facModel->fetchRow($whereFA);

            if (!isset($facturaA)) {
                $log->info("No se ha podido recuperar la ultima factura");
                throw new Zend_Db_Exception(" No se Pudo Recuperar la Ultima Factura ");
            }

            $updateFactura ["codigo_control"] = $facturacionBean->generarCodigoControl($dosificacion->autorizacion, $facturaA->numero_factura, $hoy, $totalFactura, $dosificacion->llave, $nit);
            $updateFactura ["texto_factura"] = "Finalizado";
            $whereFCC = array("id_factura='$facturaA->id_factura'");
            $log->info("Actualizando el codigo de control de la factura ");
            if ($facModel->update($updateFactura, $whereFCC) == 0) {
                $log->info("No se pudo Actualizar el codigo de control de la factura ");
                throw new Zend_Db_Exception(" No se Pudo Recuperar la Ultima Factura con codigo de control");
            }
            $ultimaFactura = $facModel->fetchRow($whereFCC);


            $literal = App_Util_Statics::convertNumber($totalFactura);
//            $fechaFactura = new Zend_Date($factura["fecha"], null, 'es_BO');
//            $fechaPrint = $fechaFactura->toString("dd/MMM/YYYY");
            $fechaPrint = DateTime::createFromFormat('Y-m-d', $factura["fecha"])->format('d/m/Y');
            $datosFactura = array(
                "fecha" => $fechaPrint,
                "hora" => $factura["hora"],
                "nombre" => strtoupper($factura["nombre"]),
                "nit" => $factura["nit"],
                "numerofactura" => "$ultimaFactura->numero_factura",
                "autorizacion" => $dosificacion->autorizacion,
                "codigoControl" => $ultimaFactura->codigo_control,
                "fechaLimite" => DateTime::createFromFormat('Y-m-d', $factura["fecha_limite"])->format('d/m/Y'),
                "total" => $factura["monto"],
                "totalLiteral" => App_Util_Statics::num2letras($totalFactura)
            );
            $codigoGuia = $encomienda['guia'];
            $nombreDestino = $encomienda['destino'];

//            die(strtoupper(urldecode($encomienda['remitente'])));
            $encomienda = array(
                "id_encomienda" => "0",
                "fecha" => $fecha,
                "hora" => $hora,
                "remitente" => strtoupper(urldecode($encomienda['remitente'])),
//                "remitente" => 'IMABOL S.R.L.',
                "destinatario" => strtoupper($encomienda['destinatario']),
                "receptor" => strtoupper($encomienda['receptor']),
                "carnet" => strtoupper($encomienda['carnet']),
                "telefono_remitente" => $encomienda['telefonoRemitente'],
                "telefono_destinatario" => $encomienda['telefonoDestinatario'],
                "sucursal_or" => $user->sucursal,
                "sucursal_de" => $user->sucursal,
                "ciudad_de" => $idCiudadDestino,
                "nombre_destino" => $nombreCiudadVendedor,
                "nombre_ciudad_destino" => $nombreCiudadVendedor,
                "guia" => $codigoGuia,
                "tipo" => "NORMAL",
                "total" => $totalFactura,
                "detalle" => $encomienda['detalle'],
                "factura" => $facturaA->id_factura,
                "estado" => "ENTREGADO",
                "is_porpagar_entregada" => "1"
            );
            $log->info("Registrando la encomienda...... ");
            $this->insert($encomienda);
            $sql = $db->select();
            $sql->from($this->_name);
            $where[] = "fecha='" . $fecha . "'";
            $where[] = "hora='" . $hora . "'";
            $where[] = "remitente='" . strtoupper(urldecode($encomienda['remitente'])) . "'";
            $where[] = "sucursal_de='" . $user->sucursal . "'";
            $where[] = "tipo='NORMAL'";
            $encomiendaR = $this->fetchRow($where);

            $movimiento = array(
                "id_movimiento" => "0",
                "fecha" => $hoy,
                "hora" => $hora,
                "movimiento" => "ENTREGADO",
                "usuario" => $user->id_persona,
                "encomienda" => $encomiendaR->id_encomienda,
                "sucursal" => $user->sucursal,
                "observacion" => "ENTREGADO en sucursal " . $encomienda['sucEntrega'],
            );

            $log->info("Registrando El movimiento...... ");
            $movimientoModel->insert($movimiento);
            $i = 0;
            if (count($items)) {
                foreach ($items as $item) {
                    if ($item["c"] != "" && $item["v"] != "0") {
                        $item["v"] = round($item["v"], 0);
                        $newItem = array(
                            "id_item_encomienda" => $i--,
                            "cantidad" => $item["c"],
                            "detalle" => $item["d"],
                            "monto" => $item["v"],
                            "peso" => $item["v"],
                            "encomienda" => $encomiendaR->id_encomienda
                        );
                        $log->info("Registrando los items ($i)...... ");
                        $itemM->insert($newItem);
                    }
                }
            }
            $db->commit();
            $session = Zend_Registry::get(App_Util_Statics::$SESSION);
            $datosEncomienda = array(
                "idEncomienda" => base64_encode($encomiendaR->id_encomienda),
                "remitente" => $encomienda["remitente"],
                "destinatario" => $encomienda["destinatario"],
                "origen" => $session->sucursalName,
                "destino" => $nombreDestino,
                "telefonoRemitente" => $encomienda['telefono_remitente'],
                "telefonoDestinatario" => $encomienda['telefono_destinatario'],
                "detalle" => $encomienda["detalle"],
                "guia" => $encomienda["guia"],
                "total" => $encomienda["total"]
            );

            $resp = array();
            $resp["factura"] = $datosFactura;
            $resp["encomienda"] = $datosEncomienda;
            $resp["cabecera"] = $cabecera;
            $resp["empresa"] = $empresa;
            $resp["items"] = $items;

            return $resp;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     * Permite registrar una encomienda por pagar que se entrego
     * para ello registrara una nueva encomienda que no tendra
     * mas que un movimiento en de entrega
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.net.bo
     * @version rc1
     * @date $(2010-06-16)
     */
    function updatePorPagar($encomienda, $items, $user, $nombreCiudadVendedor) {
        $db = $this->getAdapter();
        $db->beginTransaction();

        $facModel = new App_Model_FacturaEncomienda();
        $dosificacionModel = new App_Model_DosificacionModel ( );
        $sucursalModel = new App_Model_SucursalModel();
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $configuracionSM = new App_Model_ConfiguracionSistema();
        $configuraciones = $configuracionSM->getAll();

        $hoy = date("Y-m-d");


        $log = Zend_Registry::get("log");
        try {
            $suc = $sucursalModel->getById($user->sucursal);
            $session = new Zend_Session_Namespace(App_Util_Statics::$SESSION);
            $idCiudadDestino = $session->ciudadID;
            $totalFactura = $encomienda['total'];
            $fecha = date("Y-m-d");
            $hora = date("H:i:s");
            $cabecera = array(
                "numeroSuc" => $suc->numero,
                "nombSuc" => $suc->nombre,
                "telefono" => $suc->telefono,
                "direccion" => $suc->direccion,
                "direccion2" => $suc->direccion2,
                "ciudad" => $nombreCiudadVendedor,
                "usuario" => $user->nombres,
                "autoimpresor" => "",
                "leyendaActividad" => App_Util_Statics::$leyendaActividad,
                "tipoFactura" => "Encomienda",
                "ciudadCapital" => $suc->capital,
                "ciudad2" => $suc->nombre2,
                "municipio" => "$suc->municipio"
            );
            $empresa = array(
                "title" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_1],
                "nombre" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_2],
                "nit" => $configuraciones[App_Util_Statics::$nitEmpresa],
            );
            $datosFactura = null;
            /*             * *************************   FACTURACION ******************** */
            $log->info("Recuperando la dosificacion");
            $dosificacion = $dosificacionModel->getLastAutomaticoBySucursal($user->sucursal);

            if (!isset($dosificacion) || !$dosificacion) {
                throw new Zend_Db_Exception("No existe una dosificacion Activa");
            }
            if ($dosificacion->fecha_limite < $hoy) {
                throw new Zend_Db_Exception(" La Fecha limite de facturacion ha expirado por favor registre una nueva dosificacion");
            }

            $nit = $encomienda['nit'];
            $nombre = strtoupper($encomienda['nombreFactura']);
            $facturacionBean = new App_Util_Facturacion();
            $factura["id_factura"] = "-1";
            $factura["vendedor"] = $user->id_persona;
            $factura["dosificacion"] = $dosificacion->id_datos_factura;
            $factura["nombre"] = $nombre;
            $factura["nit"] = $nit;
            $factura["fecha"] = $hoy;
            $factura["hora"] = $hora;
            $factura["monto"] = $totalFactura;
            $factura["texto_factura"] = "En proceso de codigo de control";
            $factura["fecha_limite"] = $dosificacion->fecha_limite;
            $factura["tipo"] = "Automatico";
            $factura["estado"] = "Activo";
            $factura["impresion"] = 1;


            $log->info("Insertando la factura computarizada");
            $facModel->insert($factura);
            $whereFA [] = "vendedor='" . $user->id_persona . "'";
            $whereFA [] = "monto=" . $totalFactura;
            $whereFA [] = "tipo='Automatico'";
            $whereFA [] = "fecha='$hoy'";
            $whereFA [] = "texto_factura='En proceso de codigo de control'";
            $facturaA = $facModel->fetchRow($whereFA);

            if (!isset($facturaA)) {
                $log->info("No se ha podido recuperar la ultima factura");
                throw new Zend_Db_Exception(" No se Pudo Recuperar la Ultima Factura ");
            }

            $updateFactura ["codigo_control"] = $facturacionBean->generarCodigoControl($dosificacion->autorizacion, $facturaA->numero_factura, $hoy, $totalFactura, $dosificacion->llave, $nit);
            $updateFactura ["texto_factura"] = "Finalizado";
            $whereFCC = array("id_factura='$facturaA->id_factura'");
            $log->info("Actualizando el codigo de control de la factura ");
            if ($facModel->update($updateFactura, $whereFCC) == 0) {
                $log->info("No se pudo Actualizar el codigo de control de la factura ");
                throw new Zend_Db_Exception(" No se Pudo Recuperar la Ultima Factura con codigo de control");
            }
            $ultimaFactura = $facModel->fetchRow($whereFCC);


            $literal = App_Util_Statics::convertNumber($totalFactura);
            $fechaFactura = new Zend_Date($factura["fecha"], null, 'es_BO');
            $fechaPrint = $fechaFactura->toString("dd/MMM/YYYY");
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
                "totalLiteral" => App_Util_Statics::num2letras($totalFactura)
            );
            $codigoGuia = $encomienda['guia'];
            $nombreDestino = $encomienda['destino'];

            $encomienda = array(
//                "id_encomienda" => "0",
                "fecha_recojo" => $fecha,
                "hora_recojo" => $hora,
//                "remitente" => strtoupper(urldecode($encomienda['remitente'])),
//                "remitente" => 'IMABOL S.R.L.',
//                "destinatario" => strtoupper($encomienda['destinatario']),
//                "receptor" => strtoupper($encomienda['receptor']),
//                "carnet" => strtoupper($encomienda['carnet']),
//                "telefono_remitente" => $encomienda['telefonoRemitente'],
//                "telefono_destinatario" => $encomienda['telefonoDestinatario'],
//                "sucursal_or" => $user->sucursal,
//                "sucursal_de" => $user->sucursal,
//                "ciudad_de" => $idCiudadDestino,
//                "nombre_destino" => $nombreCiudadVendedor,
//                "nombre_ciudad_destino" => $nombreCiudadVendedor,
//                "guia" => $codigoGuia,
//                "tipo" => "NORMAL",
//                "total" => $totalFactura,
//                "detalle" => $encomienda['detalle'],
                "detalle" => 'test detalle',
                "factura" => $facturaA->id_factura,
                "estado" => "ENTREGADO",
                "is_porpagar_entregada" => "1"
            );
            $log->info("Registrando la encomienda...... ");
            $whereUpdateEnc[] = "guia='" . $codigoGuia . "'";
            $this->update($encomienda, $whereUpdateEnc);

            $sql = $db->select();
            $sql->from($this->_name);
            $where[] = "guia='" . $codigoGuia . "'";
//            $where[] = "hora_recojo='" . $hora . "'";
//            $where[] = "remitente='" . strtoupper(urldecode($encomienda['remitente'])) . "'";
//            $where[] = "sucursal_de='" . $user->sucursal . "'";
//            $where[] = "tipo='POR PAGAR'";
            $encomiendaR = $this->fetchRow($where);

            $movimiento = array(
                "id_movimiento" => "0",
                "fecha" => $hoy,
                "hora" => $hora,
                "movimiento" => "ENTREGADO",
                "usuario" => $user->id_persona,
                "encomienda" => $encomiendaR->id_encomienda,
                "sucursal" => $user->sucursal,
                "observacion" => "ENTREGADO en sucursal " . $encomienda['sucEntrega'],
            );

            $log->info("Registrando El movimiento...... ");
            $movimientoModel->insert($movimiento);

            $db->commit();
            $session = Zend_Registry::get(App_Util_Statics::$SESSION);
            $datosEncomienda = array(
                "idEncomienda" => base64_encode($encomiendaR->id_encomienda),
                "remitente" => $encomienda["remitente"],
                "destinatario" => $encomienda["destinatario"],
                "origen" => $session->sucursalName,
                "destino" => $nombreDestino,
                "telefonoRemitente" => $encomienda['telefono_remitente'],
                "telefonoDestinatario" => $encomienda['telefono_destinatario'],
                "detalle" => $encomienda["detalle"],
                "guia" => $encomienda["guia"],
                "total" => $encomienda["total"]
            );

            $resp = array();
            $resp["factura"] = $datosFactura;
            $resp["encomienda"] = $datosEncomienda;
            $resp["cabecera"] = $cabecera;
            $resp["empresa"] = $empresa;
            $resp["items"] = $items;

            return $resp;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     * Permite registrar una encomienda por pagar que se entrego con factura Manual
     * para ello registrara una nueva encomienda que no tendra 
     * mas que un movimiento en de entrega
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.net.bo
     * @version rc1
     * @date $(2010-06-16)
     */
    function savePorPagarManual($encomienda, $items, $user, $nombreCiudadVendedor) {
        $db = $this->getAdapter();
        $db->beginTransaction();


        $ciudadM = new App_Model_CiudadModel();
        $itemM = new App_Model_ItemEncomienda();
        $facModel = new App_Model_FacturaEncomienda();
        $dosificacionModel = new App_Model_DosificacionModel ( );
        $sucursalModel = new App_Model_SucursalModel();
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $configuracionSM = new App_Model_ConfiguracionSistema();
        $configuraciones = $configuracionSM->getAll();

        $hoy = date("Y-m-d");


        $log = Zend_Registry::get("log");
        try {
            $suc = $sucursalModel->getById($user->sucursal);
            $session = new Zend_Session_Namespace(App_Util_Statics::$SESSION);
            $idCiudadDestino = $session->ciudadID;
            $totalFactura = $encomienda['total'];
            $fecha = date("Y-m-d");
            $hora = date("H:i:s");
            $cabecera = array(
                "numeroSuc" => $suc->numero,
                "nombSuc" => $suc->nombre,
                "telefono" => $suc->telefono,
                "direccion" => $suc->direccion,
                "direccion2" => $suc->direccion2,
                "ciudad" => $nombreCiudadVendedor,
                "usuario" => $user->nombres,
                "autoimpresor" => ""
            );
            $empresa = array(
                "title" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_1],
                "nombre" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_2],
                "nit" => $configuraciones[App_Util_Statics::$nitEmpresa],
            );
            $datosFactura = null;
            /*             * *************************   FACTURACION ******************** */
            $log->info("Recuperando la dosificacion");
            $iddosificacion = $encomienda["dosificacion"];
            $numeroFactura = $encomienda["numeroFactura"];
            unset($encomienda["numeroFactura"]);
            unset($encomienda["dosificacion"]);

            $dosificacion = $dosificacionModel->findById($iddosificacion);

            if (!isset($dosificacion) || !$dosificacion) {
                throw new Zend_Db_Exception("La dosificacion seleccionada parece no existir por favor verifique la informacion");
            }

            if ($dosificacion->inicio > $numeroFactura || $numeroFactura > $dosificacion->fin) {
                throw new Zend_Db_Exception(" El numero ($numeroFactura) de factura introducida no corresponde a la dosificacion seleccionada");
            }

            $nit = $encomienda['nit'];
            $nombre = strtoupper($encomienda['nombreFactura']);
            $facturacionBean = new App_Util_Facturacion();
            $factura["id_factura"] = "-1";
            $factura["vendedor"] = $user->id_persona;
            $factura["dosificacion"] = $dosificacion->id_datos_factura;
            $factura["nombre"] = $nombre;
            $factura["nit"] = $nit;
            $factura["fecha"] = $hoy;
            $factura["hora"] = $hora;
            $factura["monto"] = $totalFactura;
            $factura["texto_factura"] = "Factura Manual " . $numeroFactura;
            $factura["fecha_limite"] = $dosificacion->fecha_limite;
            $factura["tipo"] = "Manual";
            $factura["numero_factura"] = $numeroFactura;
            $factura["estado"] = "Activo";
            $factura["impresion"] = 1;


            $log->info("Insertando la factura Manual");
            $facModel->insert($factura);

            $whereFA[] = "numero_factura='" . $numeroFactura . "'";
            $whereFA[] = "dosificacion='" . $iddosificacion . "'";
            $facturaA = $facModel->fetchRow($whereFA);


            if (!isset($facturaA)) {
                $log->info("No se ha podido recuperar la ultima factura");
                throw new Zend_Db_Exception(" No se Pudo Recuperar la Ultima Factura ");
            }

            $updateFactura ["texto_factura"] = "Finalizado";
            $whereFCC = array("id_factura='$facturaA->id_factura'");
            if ($facModel->update($updateFactura, $whereFCC) == 0) {
                throw new Zend_Db_Exception(" No se Pudo Recuperar la Ultima Factura con codigo de control");
            }
            $ultimaFactura = $facModel->fetchRow($whereFCC);


            $literal = App_Util_Statics::convertNumber($totalFactura);
            $datosFactura = array(
                "fecha" => DateTime::createFromFormat('Y-m-d', $factura["fecha"])->format('d/m/Y'),
                "hora" => $factura["hora"],
                "nombre" => strtoupper($factura["nombre"]),
                "nit" => $factura["nit"],
                "numerofactura" => "$ultimaFactura->numero_factura",
                "autorizacion" => $dosificacion->autorizacion,
                "codigoControl" => $ultimaFactura->codigo_control,
                "fechaLimite" => DateTime::createFromFormat('Y-m-d', $factura["fecha_limite"])->format('d/m/Y'),
                "total" => $factura["monto"],
                "totalLiteral" => App_Util_Statics::num2letras($totalFactura)
            );
//            }
            $codigoGuia = $encomienda['guia'];
            $nombreDestino = $encomienda['destino'];
            $encomienda = array(
                "id_encomienda" => "0",
                "fecha" => $fecha,
                "hora" => $hora,
                "remitente" => strtoupper(urlencode($encomienda['remitente'])),
//                "remitente" => 'eeee',
                "destinatario" => strtoupper($encomienda['destinatario']),
                "receptor" => strtoupper($encomienda['receptor']),
                "carnet" => strtoupper($encomienda['carnet']),
                "telefono_remitente" => $encomienda['telefonoRemitente'],
                "telefono_destinatario" => $encomienda['telefonoDestinatario'],
                "sucursal_or" => $user->sucursal,
                "sucursal_de" => $user->sucursal,
                "ciudad_de" => $idCiudadDestino,
                "nombre_destino" => $nombreCiudadVendedor,
                "nombre_ciudad_destino" => $nombreCiudadVendedor,
                "guia" => $codigoGuia,
                "tipo" => "NORMAL",
                "total" => $totalFactura,
                "detalle" => $encomienda['detalle'],
                "factura" => $facturaA->id_factura,
                "estado" => "ENTREGADO",
                "is_porpagar_entregada" => "1"
            );
            $log->info("Registrando la encomienda...... ");
            $this->insert($encomienda);
            $sql = $db->select();
            $sql->from($this->_name);
            $where[] = "fecha='" . $fecha . "'";
            $where[] = "hora='" . $hora . "'";
            $where[] = "remitente='" . strtoupper(urldecode($encomienda['remitente'])) . "'";
            $where[] = "sucursal_de='" . $user->sucursal . "'";
            $where[] = "tipo='NORMAL'";
            $encomiendaR = $this->fetchRow($where);

            $movimiento = array(
                "id_movimiento" => "0",
                "fecha" => $hoy,
                "hora" => $hora,
                "movimiento" => "ENTREGADO",
                "usuario" => $user->id_persona,
                "encomienda" => $encomiendaR->id_encomienda,
                "sucursal" => $user->sucursal,
                "observacion" => "ENTREGADO en sucursal " . $encomienda['sucEntrega'],
            );

            $log->info("Registrando El movimiento...... ");
            $movimientoModel->insert($movimiento);
            $i = 0;
            if (count($items)) {
                foreach ($items as $item) {
                    if ($item["c"] != "" && $item["v"] != "0") {
                        $item["v"] = round($item["v"], 0);
                        $newItem = array(
                            "id_item_encomienda" => $i--,
                            "cantidad" => $item["c"],
                            "detalle" => $item["d"],
                            "monto" => $item["v"],
                            "peso" => $item["v"],
                            "encomienda" => $encomiendaR->id_encomienda
                        );
                        $log->info("Registrando los items ($i)...... ");
                        $itemM->insert($newItem);
                    }
                }
            }
            $db->commit();
            $session = Zend_Registry::get(App_Util_Statics::$SESSION);
            $datosEncomienda = array(
                "idEncomienda" => base64_encode($encomiendaR->id_encomienda),
                "remitente" => $encomienda["remitente"],
                "destinatario" => $encomienda["destinatario"],
                "origen" => $session->sucursalName,
                "destino" => $nombreDestino,
                "telefonoRemitente" => $encomienda['telefono_remitente'],
                "telefonoDestinatario" => $encomienda['telefono_destinatario'],
                "detalle" => $encomienda["detalle"],
                "guia" => $encomienda["guia"],
                "total" => $encomienda["total"]
            );

            $resp = array();
            $resp["factura"] = $datosFactura;
            $resp["encomienda"] = $datosEncomienda;
            $resp["cabecera"] = $cabecera;
            $resp["empresa"] = $empresa;
            $resp["items"] = $items;

            return $resp;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    function validarDosificacion($dosificacion) {
        if (!isset($dosificacion) || !$dosificacion) {
            throw new Zend_Db_Exception("No existe una dosificacion Activa");
        }
        if ($dosificacion->fecha_limite < $hoy) {
            throw new Zend_Db_Exception(" La Fecha limite de facturacion ha expirado por favor registre una nueva dosificacion");
        }
    }

    /**
     * Permite registrar una encomienda con factura manual en el sistema
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.net.bo
     * @version beta
     * @date $(2010-06-16)
     */
    function txSaveManual($datos, $items, $user, $idCiudadOrigen) {
        $db = $this->getAdapter();
        $db->beginTransaction();


        $ciudadM = new App_Model_CiudadModel();
        $itemM = new App_Model_ItemEncomienda();
        $facModel = new App_Model_FacturaEncomienda();
        $dosificacionModel = new App_Model_DosificacionModel ( );
        $sucursalModel = new App_Model_SucursalModel();
        $movimientoModel = new App_Model_MovimientoEncomienda();
        $movimientoVendedorModel = new App_Model_MovimientoVendedor();
        $configuracionSM = new App_Model_ConfiguracionSistema();
        $configuraciones = $configuracionSM->getAll();

        $hoy = date("Y-m-d");
        if (isset($datos["fecha"])) {
            $hoy = $datos["fecha"];
        }
        $idCiudadDestino = base64_decode($datos['ciudadDest']);
        $ciudadDestino = $ciudadM->findByid($idCiudadDestino);
        try {
            $suc = $sucursalModel->getById($user->sucursal);

            $totalFactura = $datos['total'];
            $fecha = $datos["fecha"];
            $hora = date("H:i:s");
            // TODO: Esto deberia ser el numero de la factura manual o lo que se este guardando manual
            $codigoGuia = $this->getCodigo($ciudadDestino['abreviacion'], $suc, $configuraciones[App_Util_Statics::$NUMERACION_GUIA]);

            /*             * *************************   FACTURACION ******************** */
            $tipoEncomienda = base64_decode($datos["tipoEncomienda"]);
            $numeroFactura = "";
            if ($tipoEncomienda == "NORMAL" || $tipoEncomienda == "GIRO") {

                $dosificacion = $dosificacionModel->findById($datos["dosificacion"]);
                $this->validarDosificacion($dosificacion);

                $nit = $datos['Nit'] == "" ? 0 : $datos['Nit'];
                $nombre = $datos['nombreFactura'] == "" ? "SIN NOMBRE" : strtoupper($datos['nombreFactura']);


                $factura = array();
                $factura["id_factura"] = "0";
                $factura["vendedor"] = $user->id_persona;
                $factura["dosificacion"] = $dosificacion->id_datos_factura;
                $factura["nombre"] = $nombre;
                $factura["nit"] = $nit;
                $factura["fecha"] = $fecha;
                $factura["hora"] = $hora;
                $factura["monto"] = $totalFactura;
                $factura["numero_factura"] = $datos["numeroFactura"];
                $factura["texto_factura"] = "Factura Manual " . $datos["numeroFactura"];
                $factura["fecha_limite"] = $dosificacion->fecha_limite;
                $factura["tipo"] = "Manual";
                $factura["estado"] = "Activo";
                $factura["impresion"] = 0;


                $facModel->insert($factura);
                $whereFA[] = "numero_factura='" . $datos["numeroFactura"] . "'";
                $whereFA[] = "dosificacion='" . $dosificacion->id_datos_factura . "'";
                $facturaA = $facModel->fetchRow($whereFA);
                $literal = App_Util_Statics::convertNumber($totalFactura);
                $codigoGuia = $datos["numeroFactura"];
            }
            /*             * *************************   FIN  FACTURACION ******************** */

            if ($datos['declarado'] != "" && $datos['declarado'] != 0 && $datos['declarado'] != "0") {
                $declarado = $datos['declarado'];
            }

            $encomienda = array(
                "id_encomienda" => "0",
                "fecha" => $fecha,
                "hora" => $hora,
                "remitente" => strtoupper($datos['remitente']),
                "destinatario" => strtoupper($datos['destinatario']),
                "telefono_remitente" => $datos['remitenteTelf'],
                "telefono_destinatario" => $datos['destinatarioTelf'],
                "sucursal_or" => $user->sucursal,
                "sucursal_de" => base64_decode($datos['destinoSuc']),
                "ciudad_de" => $idCiudadDestino,
                "nombre_destino" => $datos['nombreDestino'],
                "nombre_ciudad_destino" => $datos['nombreCiudadDestino'],
                "guia" => $codigoGuia,
                "tipo" => base64_decode($datos['tipoEncomienda']),
                "total" => $datos['total'],
                "detalle" => $datos['detalle'],
                "valor_declarado" => $declarado,
                "estado" => "RECIBIDO",
                "puerta_puerta" => (isset($datos['puertaPuerta']))? $datos['puertaPuerta'] : 0
            );
            if ($tipoEncomienda == "NORMAL" || $tipoEncomienda == "GIRO") {
                $encomienda["factura"] = $facturaA->id_factura;
            }

            $this->insert($encomienda);
            $sql = $db->select();
            $sql->from($this->_name);

            $where[] = "fecha='" . $fecha . "'";
            $where[] = "hora='" . $hora . "'";
            $where[] = "remitente='" . strtoupper($datos['remitente']) . "'";
            $where[] = "sucursal_de='" . base64_decode($datos['destinoSuc']) . "'";
            $where[] = "tipo='" . base64_decode($datos['tipoEncomienda']) . "'";
            $encomiendaR = $this->fetchRow($where);

            $movimiento = array(
                "id_movimiento" => "0",
                "fecha" => $fecha,
                "hora" => $hora,
                "movimiento" => "RECIBIDO",
                "usuario" => $user->id_persona,
                "encomienda" => $encomiendaR->id_encomienda,
                "sucursal" => $user->sucursal,
                "observacion" => "Recepcion normal",
            );

            $movimientoModel->insert($movimiento);
            $i = 0;
            foreach ($items as $item) {
                if ($item["cantidad"] != "" && $item["valor"] != "0") {
                    $newItem = array(
                        "id_item_encomienda" => $i--,
                        "cantidad" => $item["cantidad"],
                        "detalle" => $item["detalle"],
                        "monto" => $item["valor"],
                        "peso" => $item["peso"],
                        "encomienda" => $encomiendaR->id_encomienda
                    );
                    $itemM->insert($newItem);
                }
            }
            $movimientoVendedorModel->createMovimientoRecepcionEncomienda($user->id_persona, $hoy, $hora, $tipoEncomienda, $numeroFactura, $datos['total'], "Manual");
            $db->commit();
            $log = Zend_Registry::get("log");
            $log->info("Guarndando la encomienda");
            $result["encomienda"] = array(
                "idEncomienda" => base64_encode($encomiendaR->id_encomienda),
                "guia" => $encomiendaR->guia,
                "detalle" => $encomiendaR->detalle,
                "destino" => base64_encode($idCiudadDestino)
            );
            return $result;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($zdbe);
            $mensaje = $zdbe->getMessage();
            if (strstr($mensaje, "duplicate key") != false) {
                $mensaje = "La factura ya fue registrada por favor ingrese un numero de factura no registrado";
            }
            throw new Zend_Db_Exception($mensaje, 125);
        }
    }

    /**
     * Muestra el dialogo de venta para los asientos seleccionados
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getMaxGuia($sucursal) {
        $db = $this->getAdapter();

        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('COUNT(*)'));
        $select->where("e.sucursal_or = '$sucursal'");
//        echo $select->__toString();
        return $db->fetchOne($select);
    }

    /**
     * Lista todas las encomiendas que esten registradas con el destino y el estado
     * pasados como argumento, tomando en cuenta que el destino se lo tiene almacenado en
     * el campo ciudad_de que no necesariamente hace refencia a ninguna ciudad de la base de datos actual
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getByEstadoDestino($estado, $destino) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('id_encomienda', 'total', 'detalle', 'guia', 'sucursal_de', 'nombre_destino AS destino'));
//        $select->joinRight(array('s' => 'sucursal'), "e.sucursal_de=s.id_sucursal ", array('nombre AS destino'));
        $select->where('UPPER(e.estado)=?', strtoupper($estado));
        $select->where('ciudad_de=?', $destino);
        $select->order('sucursal_de');
        $select->order('fecha');
//        echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * recupera todas las encomiendas que un usuario haya recepcionado en una fecha 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getByUserDateSucursal($user, $date, $sucursal) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $recepcion = App_Util_Statics::$ESTADOS_ENCOMIENDA['Recibido'];
        $anulada = App_Util_Statics::$ESTADOS_ENCOMIENDA['Anulado'];
//        $traspaso = App_Util_Statics::$ESTADOS_ENCOMIENDA['Traspaso'];
        $entregado = App_Util_Statics::$ESTADOS_ENCOMIENDA['Entrega'];
        $select->from(array('e' => 'encomienda'), array('id_encomienda', 'total', 'detalle', 'guia', 'sucursal_de', 'nombre_destino AS destino', 'tipo', 'estado'));
        $select->join(array('m' => 'movimiento_encomienda'), "m.encomienda=e.id_encomienda AND (movimiento='$recepcion' OR movimiento='$entregado' OR movimiento='$anulada')", array("fecha"));
        $select->where('e.sucursal_or=?', $sucursal);
        $select->where('m.usuario=?', $user);
        $select->where('m.fecha=?', $date);
        $select->order('e.estado');
        $select->order('sucursal_de');
        $select->order('m.fecha');
//        echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * Lista todas las encomiendas que esten registradas con el estado
     * pasado como argumento, tomando en cuenta que el destino se lo tiene almacenado en
     * 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getByEstadoOrigen($estado, $origen) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('id_encomienda', 'total', 'guia', 'sucursal_de', 'nombre_destino AS destino', 'ciudad_de', 'tipo', 'nombre_ciudad_destino', 'puerta_puerta'));
        $select->join(array('i' => 'item_encomienda'), "i.encomienda=e.id_encomienda", array('id_item_encomienda', 'cantidad', 'detalle', 'monto', 'peso', 'estado'));
        $select->where('UPPER(e.estado)=?', strtoupper($estado));
        $select->where('e.sucursal_or=?', $origen);
        $select->order('tipo');
        $select->order('sucursal_de');
        $select->order('puerta_puerta');
        $select->order('guia');
        $select->order('fecha');
//        echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * recupera la informacion de las encoienda que se encuentran en un manifiesto
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-11
     */
    function getByManfiesto($idManifiesto) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('id_encomienda', 'detalle', 'total', 'guia', 'sucursal_de', 'nombre_destino AS destino', 'tipo', 'puerta_puerta'));
        $select->where('manifiesto=?', $idManifiesto);
        $select->order('tipo');
        $select->order('fecha');
        $select->order('nombre_destino');
        $select->order('guia');
//        echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * Actualiza la informacion del manifiesto de una encomienda
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-09-07
     */
    function updateManifiesto($idencomienda, $costoEncomienda, $idManifiesto) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $manModel = new App_Model_ManifiestoModel();
        //			print_r($dosificacion);
        $where [] = "id_encomienda='" . $idencomienda . "'";
        $enc ['manifiesto'] = $idManifiesto;

        if ($this->update($enc, $where) <= 0) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info("No se pudo mover la encomienda al manifiesto ");
            throw new Zend_Db_Exception(" No se pudo mover la encomienda al manifiesto ", 125);
        }
        $infoM = $manModel->findById($idManifiesto);
        $whereM[] = "id_manifiesto='$idManifiesto'";
        $dataMan["total"] = $infoM->total + $costoEncomienda;
        if ($manModel->update($dataMan, $whereM) <= 0) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info("No se pudo actualizar el monto del manifiesto");
            throw new Zend_Db_Exception(" No se pudo actualizar el monto del manifiesto al mover la encomienda", 125);
        }
        $db->commit();
    }

    /**
     * Guarda un movimiento para todas las encomiendas de un manifiesto
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-09-07
     * 
     * TODO:  todavia no se esta registrando el bus en el que se esta enviando la encomienda
     */
    function saveMovimientoEncomienda($movimiento, $manifiesto, $user, $bus) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $manModel = new App_Model_ManifiestoModel();
        $movModel = new App_Model_MovimientoEncomienda();
        //			print_r($dosificacion);
        try {
            $where [] = "manifiesto='" . $manifiesto . "'";
            $dataEncomienda ['estado'] = $movimiento;

            if ($this->update($dataEncomienda, $where) <= 0) {
                throw new Zend_Db_Exception(" No se pudo enviar el manifiesto ", 125);
            }
            $hoy = date("Y-m-d");
            $hora = date("H:i:s");
            $encomiendas = $this->getByManfiesto($manifiesto);
            foreach ($encomiendas as $encomienda) {
                $movimiento = array(
                    "id_movimiento" => $encomienda->id_encomienda,
                    "fecha" => $hoy,
                    "hora" => $hora,
                    "movimiento" => App_Util_Statics::$ESTADOS_ENCOMIENDA['Envio'],
                    "usuario" => $user->id_persona,
                    "encomienda" => $encomienda->id_encomienda,
                    "sucursal" => $user->sucursal,
                    "bus" => $bus,
                    "observacion" => "Envio de encomiendas"
                );
                $movModel->insert($movimiento);
            }
            $infoM = $manModel->findById($manifiesto);
            $db->commit();
        } catch (Zend_Db_Exception $ex) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($ex);
            throw new Zend_Db_Exception($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Recupera todas las encomiendas registradas a un manifiesto para un viaje y en una sucursal
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-09-18 14:28
     */
    function getByViajeSucursal($viaje, $sucursal) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('i' => 'item_encomienda'), array('id_item_encomienda', 'detalle', 'monto', 'peso', 'cantidad'));
        $select->joinInner(array('e' => 'encomienda'), "i.encomienda=e.id_encomienda", array('id_encomienda', 'fecha', 'hora', 'total', 'guia'));
        $select->joinInner(array('m' => 'manifiesto'), "m.id_manifiesto=e.manifiesto", null);
        $select->where("viaje=?", $viaje);
        $select->where("sucursal_or=?", $sucursal);
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * Recupera todos los items de una encomiendas 
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 2012-09-26 14:39
     */
    function getItems($encomienda) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('i' => 'item_encomienda'), array('id_item_encomienda', 'detalle', 'cantidad', 'monto', 'peso', 'estado'));
        $select->where("encomienda=?", $encomienda);
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * @Parameter id  id de la encomienda a la que pertenece la factura
     */
    function getFactura($id) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('fe' => 'factura_encomienda'), array('id_factura', 'nit', 'fecha', 'nombre', 'monto', 'numero_factura', 'tipo'));
        $select->join(array("e" => "encomienda"), "e.factura=fe.id_factura", null);
        $select->join(array("p" => "persona"), "fe.vendedor=p.id_persona", array("nombres", "identificador"));
        $select->where('id_encomienda=?', $id);

        return $db->fetchRow($select);
    }

    function updateToFacturaManual($encomienda, $data, $person) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $facturaModel = new App_Model_FacturaEncomienda();
            $fact = $facturaModel->getById($data['idfactura']);
            $facManual = array();
            $facManual['id_factura'] = "-1";
            $facManual['vendedor'] = $person->id_persona;
            $facManual['dosificacion'] = $data['dosificacion'];
            $facManual['nit'] = $fact['nit'];
            $facManual['fecha'] = $fact['fecha'];
            $facManual['hora'] = $fact['hora'];
            $facManual['nombre'] = $fact['nombre'];
            $facManual['monto'] = $data['monto'];
            $facManual['numero_factura'] = $data['numeroFactura'];
            $facManual['tipo'] = 'Manual';
            $facManual['estado'] = 'Activo';
            $facManual['encomienda_deleted'] = $encomienda;

            $idNewFactura = $facturaModel->insert($facManual);
            $where [] = "id_encomienda='" . $encomienda . "'";
            $dataEncomienda ['factura'] = $idNewFactura;
            if ($this->update($dataEncomienda, $where) <= 0) {
                throw new Zend_Db_Exception(" No se pudo Cambiar la encomienda de factura", 125);
            }
            $db->commit();
        } catch (Zend_Db_Exception $ex) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($ex);
            throw new Zend_Db_Exception($ex->getMessage(), $ex->getCode());
        }
    }

    /**
     * Funcion encargada de cambiar el estado de una encomienda y su factura 
     * a posible anulacion (POR ANULAR)
     * @param type $encomiendaId Identificador de la encomienda
     * @throws Zend_Db_Exception
     */
    function moveToCancel($encomiendaId) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $facturaModel = new App_Model_FacturaEncomienda();
            $fact = $this->getFactura($encomiendaId);

            $estado = "POR ANULAR";
            $factura['estado'] = $estado;
            $encomienda['estado'] = $estado;


            if ($fact) {
                $whereFact [] = " id_factura ='" . $fact->id_factura . "'";
                if ($facturaModel->update($factura, $whereFact) <= 0) {
                    throw new Zend_Db_Exception(" No se pudo Cambiar la factura", 125);
                }
            }
            $where [] = " id_encomienda = '" . $encomiendaId . "'";
            if ($this->update($encomienda, $where) <= 0) {
                throw new Zend_Db_Exception(" No se pudo Cambiar la encomienda", 125);
            }

            $db->commit();
            $message = "La encomienda mas su factura han sido movidas para posibles anulaciones";
            $error = false;
        } catch (Zend_Db_Exception $ex) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($ex);
            $message = $ex->getMessage();
            $error = true;
            throw new Zend_Db_Exception($ex->getMessage(), $ex->getCode());
        }
        return array("message" => $message, "error" => $error);
    }

    /**
     * Recupera la informacion necesaria de la persona 
     * que recepciono la encomienda
     * @param type $id
     */
    function getReceptor($id) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('fecha'));
        $select->join(array("me" => "movimiento_encomienda"), "e.id_encomienda=me.encomienda and movimiento='RECIBIDO'", array('sucursal'));
        $select->join(array("p" => "persona"), "me.usuario=p.id_persona", array("nombres", "identificador", 'id_persona'));
        $select->where('id_encomienda=?', $id);

        return $db->fetchRow($select);
    }

    function getByIds($ids) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('fecha', 'hora', 'total', 'guia', 'detalle', 'tipo'));
        $select->where('id_encomienda in (?)', $ids);
        return $db->fetchAll($select);
    }

    function updateFacturaToEncomiendas($encomiendaList, $encomienda) {
        $this->update($encomienda, array('id_encomienda in (?)'=> $encomiendaList));
    }

}
