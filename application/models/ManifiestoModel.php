<?php

/**
 * Bus
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ManifiestoModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'manifiesto';
    protected $_primary = 'id_manifiesto';
    protected $_sequence = false;

    /**
     * 
     * @param type $idManifiesto
     * @return Object or Array with id_manifiesto, despachador, viaje(id_viaje), fecha and hora del viaje
     */
    public function findById($idManifiesto) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->from(array('m' => 'manifiesto'), array('id_manifiesto', 'despachador', 'viaje',
            'bus', 'chofer', 'total', 'destino', 'origen', 'estado', 'tipo'));
        $select->join(array("v" => "viaje"), "v.id_viaje=m.viaje", array('fecha', 'hora',));
        $select->join(array("d" => "destino"), "d.id_destino=v.destino", null);
        $select->join(array("c" => "ciudad"), "c.id_ciudad=d.llegada", array("nombre as ciudadDestino"));
        $select->joinLeft(array("ch" => "chofer"), "ch.id_chofer=m.chofer", "nombre_chofer");
        $select->joinLeft(array("b" => "bus"), "b.id_bus=m.bus", "numero");
        $select->where("id_manifiesto=?", $idManifiesto);
//        echo $select->__toString();
        $results = $db->fetchRow($select);
        return $results;
    }

    /**
     * Registra o actualiza un manifiesto
     * registra el manifiesto a las encomiendas que se pasa como arrays
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    public function saveTx($viaje, $chofer, $bus, array $encomiendas, $user, $destino, $origen, $fecha) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $encModel = new App_Model_EncomiendaModel();
        $movEncModel = new App_Model_MovimientoEncomienda();
        $movVendedorModel = new App_Model_MovimientoVendedor();

        try {
            $total = 0;
            if (!isset($fecha)) {
                $fecha = date("Y-m-d");
            }

            $man = $this->getByUserViaje($user->id_persona, $viaje);
            if ($man == false) {
                $manifiesto = array(
                    "id_manifiesto" => "0",
                    "chofer" => $chofer,
                    "bus" => $bus,
                    "viaje" => $viaje,
                    "despachador" => $user->id_persona,
                    "fecha" => $fecha,
                    "hora" => date("H:i:s"),
                    "destino" => $destino,
                    "origen" => $origen, //$user->sucursal,
                    "tipo" => "Encomienda"
                );
                $this->insert($manifiesto);
                $where[] = "fecha='" . $fecha . "'";
                $where[] = "despachador='" . $user->id_persona . "'";
                $where[] = "viaje='" . $viaje . "'"; //$user->sucursal
                $where[] = "destino='" . $destino . "'";
                $man = $this->fetchRow($where);
            } else {
                $this->update(array("chofer" => $chofer), "id_manifiesto='$man->id_manifiesto'");
            }
            $total = $man->total;
            $totalAumentado = 0;
            $i = 0;
            $encs = array();
            foreach ($encomiendas as $enc => $items) {
                $dataEnc["manifiesto"] = $man->id_manifiesto;
                $dataEnc["estado"] = App_Util_Statics::$ESTADOS_ENCOMIENDA['Envio'];
                $whereEnc[] = "id_encomienda='$enc'";
                $i--;
                if ($encModel->update($dataEnc, $whereEnc) == 0) {
                    $error = error_get_last();
                    throw new Zend_Db_Exception("No se pudo Actualizar las encomiendas [$enc] [$man->id_manifiesto] -- " . $error["message"], 126);
                }

                $dataItems = $encModel->getItems($enc);
                $observacion = "Envio a destino normal en manifiesto($man->id_manifiesto)";
                if (count($dataItems) != $items['size']) {
                    $observacion = "No se enviaron todos los items de la encomienda";
                }
                $hoy = date("Y-m-d");
                $ahora = date("H:i:s");
                $movimiento = array(
                    "id_movimiento" => "$i",
                    "fecha" => $hoy,
                    "hora" => $ahora,
                    "movimiento" => "ENVIADO",
                    "usuario" => $user->id_persona,
                    "encomienda" => $enc,
                    "sucursal" => $user->sucursal,
                    "bus" => $bus,
                    "observacion" => $observacion,
                );

                $movEncModel->insert($movimiento);
                $encd = $encModel->getById($enc);
                $tipoEncomienda = strtoupper($encd->tipo);
                if ($tipoEncomienda == "NORMAL" || $tipoEncomienda == "GIRO") {
                    $total = $encd->total;
                    $totalAumentado = +$encd->total;
                }
                $encs[] = array("guia" => $encd->guia, "detalle" => $encd->detalle);
                $whereEnc = null;
            }
            $dataMan["total"] = $total;
            $whereMan[] = "id_manifiesto='$man->id_manifiesto'";
            if ($this->update($dataMan, $whereMan) == 0) {
                throw new Zend_Db_Exception("No se pudo actualizar el total del manifiesto");
            }

            $movVendedorModel->createMovimientoAsignacion($user->id_persona, $hoy, $ahora, $totalAumentado, $man->id_manifiesto, "ASIGNACION");
            $db->commit();
            return $man->id_manifiesto;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage(), 125);
        }
    }

    /**
     * Remueve una lista de encomiendas que se le habian asignado 
     * regresandolas a la sucursal de la persona que esta recibiendo las encomiendas
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date 2012-10-01  10:03
     */
    public function removeEncomiendaTx($manifiesto, array $encomiendas, $user, $ciudadResago) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        $encModel = new App_Model_EncomiendaModel();
        $movEncModel = new App_Model_MovimientoEncomienda();
        $movVendedorModel = new App_Model_MovimientoVendedor();

        try {
            $total = 0;
            $man = $this->findById($manifiesto);
            $total = $man['total'];
            $montoDescuento = 0;
            $i = 0;
            $encs = array();

            foreach ($encomiendas as $enc => $items) {
                $dataEnc["manifiesto"] = null;
                $dataEnc["sucursal_or"] = $user->sucursal;
                $dataEnc["estado"] = App_Util_Statics::$ESTADOS_ENCOMIENDA['Recibido'];
                $whereEnc[] = "id_encomienda='$enc'";
                $i--;
                if ($encModel->update($dataEnc, $whereEnc) == 0) {
                    $error = error_get_last();
                    throw new Zend_Db_Exception("No se pudo Actualizar las encomiendas [$enc] -- " . $error["message"], 126);
                }

                $dataItems = $encModel->getItems($enc);
                $observacion = "Se retiro la encomienda del manifiesto ($man->id_manifiesto)";
                if (count($dataItems) != $items['size']) {
                    $observacion = "No se dejaron todos los items de la encomienda";
                }
                $hoy = date("Y-m-d");
                $ahora = date("H:i:s");
                $movimiento = array(
                    "id_movimiento" => "$i",
                    "fecha" => $hoy,
                    "hora" => $ahora,
                    "movimiento" => App_Util_Statics::$ESTADOS_ENCOMIENDA['Traspaso'],
                    "usuario" => $user->id_persona,
                    "encomienda" => $enc,
                    "sucursal" => $user->sucursal,
                    "bus" => $man->bus,
                    "observacion" => $observacion,
                );

                $movEncModel->insert($movimiento);
                $encd = $encModel->getById($enc);
                $total = -$encd->total;
                $montoDescuento = +$encd->total;
                $encs[] = array("guia" => $encd->guia, "detalle" => $encd->detalle);
                $whereEnc = null;
            }
            $dataMan["total"] = $total;
            $whereMan[] = "id_manifiesto='$manifiesto'";
            if ($this->update($dataMan, $whereMan) == 0) {
                throw new Zend_Db_Exception("No se pudo actualizar el total del manifiesto", 125);
            }
            $movVendedorModel->createMovimientoAsignacion($user->id_persona, $hoy, $ahora, $montoDescuento, $manifiesto, "DES-ASIGNACION");
            $db->commit();
            return $man->id_manifiesto;
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe->getMessage() . " $manifiesto (" . $man['total'] . " -----  $total) ", $zdbe->getCode());
        }
    }

    /**
     * recupera todos los manifiestos activos en una fecha dada
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getByDate($date, $user = null) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('m' => 'manifiesto'), array('id_manifiesto', 'total'));
        $select->joinInner(array('p' => 'persona'), "m.despachador=p.id_persona", array('nombres'));
        $select->joinInner(array('co' => 'ciudad'), "m.origen=co.id_ciudad", array('nombre AS origen'));
        $select->joinInner(array('cd' => 'ciudad'), "m.destino=cd.id_ciudad", array('nombre AS destino'));
        $select->joinLeft(array('ch' => 'chofer'), "m.chofer=ch.id_chofer", array('nombre_chofer AS chofer'));
        $select->where("fecha=?", $date);
        if (!is_null($user)) {
            $select->where("despachador=?", $user);
        }
        $select->order("co.nombre");
//        echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * recupera todos los manifiestos activos en una fecha dada con informacion de hora de viaje y numero de bus
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getByDate1($date) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('m' => 'manifiesto'), array('id_manifiesto', 'total'));
        $select->joinInner(array('p' => 'persona'), "m.despachador=p.id_persona", array('nombres'));
        $select->joinInner(array('v' => 'viaje'), "v.id_viaje=m.viaje", array('hora'));
        $select->joinInner(array('b' => 'bus'), "b.id_bus=m.bus", array('numero'));
        $select->joinInner(array('co' => 'ciudad'), "m.origen=co.id_ciudad", array('nombre AS origen'));
        $select->joinInner(array('cd' => 'ciudad'), "m.destino=cd.id_ciudad", array('nombre AS destino'));
        $select->joinLeft(array('ch' => 'chofer'), "m.chofer=ch.id_chofer", array('nombre_chofer AS chofer'));
        $select->where("v.fecha=?", $date);
        $select->order("co.nombre");
        $select->order("v.hora");
        $select->order("cd.nombre");
//        echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * Recupera el manifiesto correspondiente a un viaje creado por un usuario
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getByUserViaje($user, $viaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('m' => 'manifiesto'), array('id_manifiesto', 'total'));
        $select->joinLeft(array('b' => 'bus'), "b.id_bus=m.bus");
        $select->joinLeft(array('ch' => 'chofer'), "ch.id_chofer=m.chofer");
        $select->where("despachador=?", $user);
        $select->where("viaje=?", $viaje);
//        echo $select->__toString();
        return $db->fetchRow($select);
    }

    /**
     * @autor Poloche
     * @version beta rc1
     * @2009
     */
    public function updateTx($dosificacion) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            //			print_r($dosificacion);
            $where [] = "id_datos_factura='" . $dosificacion ['id_datos_factura'] .
                    "'";
            $dosificacion ['inicio'] = intval($dosificacion ['inicio']);
            $dosificacion ['fin'] = intval($dosificacion ['fin']);
            if ($dosificacion ['tipo'] == 'Automatica' && $dosificacion ['estado'] == 'Activo') {
                $whereOtras [] = "estado='Activo'";
                $whereOtras [] = "sucursal='" . $dosificacion ['sucursal'] . "'";
                $whereOtras [] = "llave<>'Manual'";
                $set ['estado'] = 'Inactivo';
                $this->update($set, $whereOtras);
            }
            unset($dosificacion ['tipo']);
            unset($dosificacion ['id_datos_factura']);
            $this->update($dosificacion, $where);
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception(" No se pudo Actualizar la Dosificacion ", 125);
        }
    }

    public function getManualBySucusal($idSucursal) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('d' => 'datos_factura'), array('id_datos_factura', 'autorizacion', 'llave'));
        $select->where("sucursal=?", $idSucursal);
        $select->where("llave=?", "Manual");
        $items = $db->fetchAll($select);
        if (sizeof($items) > 0) {
            foreach ($items as $item) {
                $tipo = 'Manual';
                $dosificaciones [$item->id_datos_factura] = $item->autorizacion . ' - ' . $tipo;
            }

            return $dosificaciones;
        } else {
            return array('todos' => 'No existe Dosif.');
        }
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 23/07/2009
     */
    public function getLastAutomatico() {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->from(array('d' => 'datos_factura'), array('id_datos_factura', 'sucursal', 'autorizacion', 'llave', 'inicio',
            'numero_factura', 'fecha_limite', 'autoimpresor', 'estado', 'fin'));
        $select->where("llave<>?", 'Manual');
        $select->where("fecha_limite>=?", date('Y-m-d'));
        $select->where("estado=?", 'Activo');
        $results = $db->fetchRow($select);
        return $results;
    }

    /**
     * Recupera todas las encomiendas que le pertencen a este manifiesto
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date $(now)
     */
    function getEncomiendasById($idMan) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'encomienda'), array('id_encomienda', 'guia', 'fecha', 'detalle', 'remitente',
            'destinatario', 'total', 'tipo', 'sucursal_de'));
        $select->where("manifiesto=?", $idMan);
        $select->where("estado=?", 'ENVIADO');
        $select->order("sucursal_de");
        $select->order("tipo");
//        echo $select->__toString();
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * recupera todos los manifiestos de encomiendas registrados para un viaje
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-11
     */
    function getByViaje($viaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('m' => 'manifiesto'), array('id_manifiesto', 'fecha', 'hora', 'total', 'despachador'));
        $select->joinInner(array('p' => 'persona'), "p.id_persona=m.despachador", array("nombres"));
        $select->joinInner(array('b' => 'bus'), "b.id_bus=m.bus", array("id_bus", "numero"));
        $select->where("viaje=?", $viaje);
        $results = $db->fetchAll($select);
        return $results;
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
        $select->from(array('m' => 'manifiesto'), array('id_manifiesto', 'fecha', 'hora', 'total', 'despachador'));
        $select->joinInner(array('p' => 'persona'), "p.id_persona=m.despachador", array("nombres"));
        $select->joinInner(array('b' => 'bus'), "b.id_bus=m.bus", array("id_bus", "numero"));
        $select->where("viaje=?", $viaje);
        $select->where("sucursal_or=?", $sucursal);
        $results = $db->fetchAll($select);
        return $results;
    }

}
