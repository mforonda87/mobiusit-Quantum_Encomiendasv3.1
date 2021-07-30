<?php

/**
 * Asiento
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Equipaje extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'equipaje';

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 24-03-2010
     */
    function findById($idequipaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'equipaje'), array('id_equipaje', "nro_ticket", "asiento", "detalle", "fecha", "hora", "estado", "peso", "franquicia", "factura", "vendedor"));

        $select->where("id_equipaje=?", $idequipaje);
        $results = $db->fetchRow($select);
        return $results;
    }

    /**
     * @param Array $data array con todos los asientos a los cuales se les asigna equipajes
     * @param Int   $franquicia  precio libre pre establecido
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 12-03-2010
     */
    function saveTx($data, $franquicia, $nombre, $nit, $user, $idViaje, $idCiudadDestino, $chofer, $destino) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $equipaje;
        $facturaEM = new App_Model_FacturaEncomienda();
        $configModel = new App_Model_ConfiguracionGral();
        $dosificacionModel = new App_Model_DosificacionModel ( );
        $sucursalModel = new App_Model_SucursalModel();
        $manifModel = new App_Model_ManifiestoModel();
        $suc = $sucursalModel->getById($user->sucursal);
        $dosificacion = $dosificacionModel->getLastAutomaticoBySucursal($user->sucursal);
//        print_r($dosificacion);
        $date = new Zend_Date();
        $hoy = date('Y-m-d');
        if (!isset($dosificacion)) {
            throw new Zend_Db_Exception("No existe una dosificacion Activa");
        }
        if ($dosificacion->fecha_limite < $hoy) {
            throw new Zend_Db_Exception(" La Fecha limite de facturacion ha expirado por favor registre una nueva dosificacion");
        }

        $pesoAsiento = $configModel->getByKey("franquicia_equipaje");
        $precio_X_kilo = $configModel->getByKey("precio_X_kilo");
        $dataManif = $manifModel->getByUserViaje($user->id_persona, $idViaje);
        try {

            if ($dataManif == null) {
                $ddM["fecha"] = date("Y-m-d");
                $ddM["hora"] = date("H:i");
                $ddM["despachador"] = $user->id_persona;
                $ddM["viaje"] = $idViaje;
                $ddM["bus"] = $idViaje;
                $ddM["chofer"] = $chofer;
                $ddM["total"] = 0;
                $ddM["destino"] = $idCiudadDestino;
                $ddM["origen"] = $user->ciudad;
                $ddM["estado"] = "Activo";
                $ddM["tipo"] = "Equipaje";
                $manifModel->insert($ddM);
                $dataManif = $manifModel->getByUserViaje($user->id_persona, $idViaje);
            }
            $i = 0;
            $totalPeso = 0;
            $totalFactura = 0;
            $precioValor = 0;
            $pesoLibre = $pesoAsiento * count($data);
            $tickets = "";
            $porValor = false;
            $items = array();
            foreach ($data as $dataAsiento) {
                if ($dataAsiento["detalleTikect"] != "" && $dataAsiento["pesoK"] != "0") {
                    $totalPeso+=intval($dataAsiento["pesoK"]);
                }
                if ($dataAsiento["tipoEquipaje"] != "peso") {
                    $porValor = true;
                    $precioValor += intval($dataAsiento["pesoK"]);
                    $items[] = array("cantidad" => "1", "detalle" => $dataAsiento['detalleTikect'], "precio" => $dataAsiento['pesoK']);
                }
            }

            if ($totalPeso > $pesoLibre) {
                $items[] = array("cantidad" => "1", "detalle" => "Excedente de equipaje", "precio" => $dataAsiento['pesoK']);
            }
            $totalFactura = $totalPeso - $pesoLibre;
            if ($totalFactura < 0) {
                $totalFactura = 0;
            }
            $totalFactura+=$precioValor;
            $mens = array();
            $facturaA = null;
            $configuracion = null;
            $datosFactJava = null;
            if ($totalPeso > $pesoLibre || $porValor) {
                $date = new Zend_Date();
                $hoy = date("Y-m-d");
                $facturacionBean = new App_Util_Facturacion();
                $factura["id_factura"] = "nuevo";
                $factura["vendedor"] = $user->id_persona;
                $factura["dosificacion"] = $dosificacion->id_datos_factura;
                $factura["nit"] = $nit;
                $factura["fecha"] = $hoy;
                $factura["nombre"] = $nombre;
                $factura["monto"] = $totalFactura;
                $factura["hora"] = date("H:i:s");
                $factura["numero_factura"] = $dosificacion->numero_factura;
                $factura["texto_factura"] = "nuevo";
                $factura["codigo_control"] = $facturacionBean->generarCodigoControl($dosificacion->autorizacion, $dosificacion->numero_factura, $hoy, $totalFactura, $dosificacion->llave, $nit);
                $factura["fecha_limite"] = $dosificacion->fecha_limite;
                $factura["tipo"] = "Automatico";
                $factura["impresion"] = 1;
                $factura["estado"] = "Activo";
//                $factura["asientos"] = $tickets;
//                $factura["fecha_viaje"] = $fechaViaje;
//                $factura["hora_viaje"] = $horaViaje;
//                $factura["destino"] = $destino;
//                $factura["numero_bus"] = $interno;
//                $factura["modelo"] = "--";
//                ;
                $facturaEM->insert($factura);
                $whereFA[] = "numero_factura='" . $dosificacion->numero_factura . "'";
                $whereFA[] = "dosificacion='" . $dosificacion->id_datos_factura . "'";
                $facturaA = $facturaEM->fetchRow($whereFA);
                $literal = App_Util_Statics::convertNumber($totalFactura);
                $datosFactJava = array("numeroSuc" => $suc->numero, "telf" => $suc->telefono, "impresor" => $dosificacion->autoimpresor,
                    "dir" => $suc->direccion, "dir2" => $suc->direccion2, "ciudad" => $suc->ciudad,
                    "numFac" => $facturaA->numero_factura, "autorizacion" => $dosificacion->autorizacion,
                    "fecha" => $hoy, "nombFact" => $nombre, "nitFact" => $nit, "total" => $totalFactura,
                    "literal" => $literal, "control" => $facturaA->codigo_control, "fechaLimite" => $dosificacion->fecha_limite,
                    "usuario" => $user->nombres, "destino" => "La paz", "fechaViaje" => "hoy",
                    "horaViaje" => 'hora_viaje', "salida" => "0", "carril" => "0", "modelo" => "No importa");
                $configuracion = array("nitE" => App_Util_Statics::$nitEmpresa, "empresa" => App_Util_Statics::$nombreEmpresa . " " . App_Util_Statics::$nombreEmpresa2);
            }
            $idFactura = null;
            if (is_object($facturaA)) {
                $idFactura = $facturaA->id_factura;
            }
            foreach ($data as $dataAsiento) {
//                $idViaje = $db->lastSequenceId ( 'equipaje_id_equipaje_seq' );
//                $asiento['id_equipaje']=$i;
                if ($dataAsiento['idEquipaje'] == "") {
                    if ($dataAsiento["detalleTikect"] != "" && $dataAsiento["pesoK"] != "0") {
                        $tickets.=$dataAsiento["ticketN"] . ",";
                        $equipaje['nro_ticket'] = $dataAsiento["ticketN"];
                        $equipaje['asiento'] = $dataAsiento["idAsiento"];
                        $equipaje['detalle'] = $dataAsiento["detalleTikect"];
                        $equipaje['fecha'] = $date->toString("YYYY-MM-dd");
                        $equipaje['hora'] = $date->toString("HH:mm:ss");
                        $equipaje['estado'] = "Activo";
                        $equipaje['peso'] = $dataAsiento["pesoK"];
                        $equipaje['franquicia'] = $franquicia;
                        $equipaje['factura'] = $idFactura;
                        $equipaje['manifiesto'] = $dataManif->id_manifiesto;
                        $equipaje['vendedor'] = $user->id_persona;
                        $equipaje["tipo_cobro"] = $dataAsiento['tipoEquipaje'];
                        $this->insert($equipaje);
                        $totalPeso+=intval($dataAsiento["pesoK"]);
                        $i++;
                    }
                } else {
                    $whereEq = array();
                    $whereEq[] = "id_equipaje='" . $dataAsiento['idEquipaje'] . "'";
                    $asientoU['nro_ticket'] = $dataAsiento["ticketN"];
                    $asientoU['asiento'] = $dataAsiento["idAsiento"];
                    $asientoU['detalle'] = $dataAsiento["detalleTikect"];
                    $asientoU['peso'] = $dataAsiento["pesoK"];
                    $asientoU['franquicia'] = $franquicia;
                    $asientoU['factura'] = $idFactura;
                    $asientoU['vendedor'] = $user->id_persona;
                    if ($this->update($asientoU, $whereEq) == 0) {
//                        print_r($whereEq);
//                        print_r($asientoU);
                        throw new Zend_Db_Exception("No se pudo actualizar l informacion del con tikect [" . $dataAsiento['ticketN'] . "]", 0);
                    }
                }
            }
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
        $mens["configuracion"] = $configuracion;
        $mens["sucursal"] = $datosFactJava;
        $mens["items"] = $items;
        return $mensajes = $mens;
    }

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 23-03-2010
     */
    function deleteTx($objEquipajes) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $asiento;

        try {

            foreach ($objEquipajes as $equipaje) {
//                $idViaje = $db->lastSequenceId ( 'equipaje_id_equipaje_seq' );
//                $asiento['id_equipaje']=$i;
                if ($equipaje['idEquipaje'] != "") {
                    $where[] = "id_equipaje='" . base64_decode($equipaje['idEquipaje']) . "'";
                    $this->delete($where);
                }
            }
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $logger = Zend_Registry::get("log");
            $logger->info($zdbe);
            throw new Zend_Db_Exception($zdbe, 125);
        }
//        return $mensajes["impresion"]=$mens;
    }

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 24-03-2010
     */
    function deleteFacturaTx($factura, $vendedor) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $facEM = new App_Model_FacturaEquipaje();

        try {
            $where[] = "factura='" . $factura . "'";
            $data['factura'] = null;
            $resp = $this->update($data, $where);
            if ($resp <= 0) {
                throw new Zend_Db_Exception("No se ha podido actualizar los equipajes");
            }
            $where2[] = "id_factura='" . $factura . "'";
            $data2['estado'] = "Anulado";
            $data2['vendedor'] = $vendedor;
            $resp2 = $facEM->update($data2, $where2);
            if ($resp2 <= 0) {
                throw new Zend_Db_Exception("No se ha podido anular la factura de excedentes");
            }
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $logger = Zend_Registry::get("log");
            $logger->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 26-03-2010
     */
    function getEquipajesLibreDeViaje($viaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'equipaje'), array('id_equipaje', "detalle", "nro_ticket as ticket"));
        $select->join(array('a' => 'asiento'), "a.id_asiento=e.asiento and a.viaje='$viaje'", null);
        $select->where("e.manifiesto IS NULL");
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * Recupera todos los equipajes que se asignaron al manifiesto
     * pasado como parametro
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 31-03-2010
     */
    function getEquipajesAsignados($manifiesto) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'equipaje'), array('id_equipaje', "detalle", "nro_ticket as ticket"));
        $select->where("manifiesto=?", $manifiesto);
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * Asigna un manifiesto al equipaje seleccionado
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 31-03-2010
     */
    function asignarManifiesto($equipaje, $manifiesto) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $where[] = "id_equipaje=$equipaje";
            $data['manifiesto'] = $manifiesto;
            $resp = $this->update($data, $where);
            if ($resp <= 0) {
                throw new Zend_Db_Exception("No se ha podido actualizar los equipajes");
            }
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $logger = Zend_Registry::get("log");
            $logger->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     * pone en nulo el campo manifiesto dejando de esta manera un equipaje
     * sin manifeisto asignado
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 31-03-2010
     */
    function quitarManifiesto($equipaje, $manifiesto) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $where[] = "id_equipaje=$equipaje";
            $data['manifiesto'] = null;
            $resp = $this->update($data, $where);
            if ($resp <= 0) {
                throw new Zend_Db_Exception("No se ha podido actualizar los equipajes");
            }
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $logger = Zend_Registry::get("log");
            $logger->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

}
