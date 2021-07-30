<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Viaje extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'viaje';
    protected $_primary = 'id_viaje';
    protected $_sequence = false;    

    public function getViajesFecha($fecha) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje', 'fecha', "hora", "estado"));
        $select->joinInner(array('b' => 'bus'), 'v.bus=b.id_bus', array('numero as interno'));
        $select->joinInner(array('pb' => 'persona_bus'), 'pb.bus=b.id_bus', null);
        $select->joinInner(array('p' => 'persona'), 'p.id_persona=pb.persona', array('nombres as propietario'));
        $select->joinInner(array('d' => 'destino'), 'd.id_destino=v.destino', null);
        $select->joinInner(array('c' => 'ciudad'), 'c.id_ciudad=d.salida', array('nombre as origen'));
        $select->joinInner(array('cd' => 'ciudad'), 'cd.id_ciudad=d.llegada', array('cd.nombre as destino'));
        $select->where('v.fecha=?', $fecha);
        $select->order('v.id_viaje');
        $results = $db->fetchAll($select);
        return $results;
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
     * @date creation 30/07/2009
     */
    public function getViajesByFechas($desde, $hasta) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje', 'fecha', hora));
        $select->joinInner(array('b' => 'bus'), 'v.bus=b.id_bus', array('numero as interno'));
        $select->where("v.fecha between '$desde' and '$hasta'");
        $select->where('v.estado=?', "Activo");
        $select->order('v.id_viaje');
        $results = $db->fetchAll($select);
        return $results;
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
     * @date creation 30/07/2009
     */
    public function getViajesByFechasPorcenteros($desde, $hasta, $prop1, $prop2, $prop3) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje', 'fecha', hora));
        $select->joinInner(array('b' => 'bus'), 'v.bus=b.id_bus', null);
        $select->joinInner(array('pb' => 'persona_bus'), "b.id_bus=pb.bus AND (pb.persona='$prop1' OR pb.persona='$prop1' OR pb.persona='$prop1')", null);
        $select->where("v.fecha between '$desde' and '$hasta'");
        $select->where('v.estado=?', "Activo");
        $select->order('v.id_viaje');
        echo $select->__toString();
        $results = $db->fetchAll($select);
        return $results;
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
     * @date creation 26/05/2009
     */
    public function getViajesFechas($desde, $hasta, $filters = null) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('p' => 'persona'), array('nombres as propietario', 'id_persona'));
        $select->joinInner(array('pb' => 'persona_bus'), 'p.id_persona = pb.persona', null);
        $select->joinInner(array('b' => 'bus'), 'pb.bus = b.id_bus', array('numero as interno'));
        $select->joinInner(array('v' => 'viaje'), 'b.id_bus = v.bus', array('fecha', 'hora', 'id_viaje', 'destino', 'oficina'));
//        $select->joinLeft ( array ('m' => 'manifiesto' ), "m.viaje = v.id_viaje and m.estado='Activo' and m.total>0",
//            array ("COALESCE(m.total,0) as totalEncomienda" ) );
        $select->joinLeft(array('f' => 'factura'), "f.viaje = v.id_viaje and f.estado='Activo'", array('COALESCE(SUM(f.monto),0) as total'));
        $select->joinInner(array('d' => "destino"), 'd.id_destino=v.destino', null);
        $select->joinInner(array('c' => 'ciudad'), 'c.id_ciudad=d.llegada', array('nombre as ciudad'));
        $select->joinInner(array('g' => 'grupo'), 'g.id_grupo=v.grupo', array('cantidad_asientos', 'destino as destinoGrupo', 'id_grupo as grupoViaje', 'nombre as nombreGrupo'));
        $select->joinLeft(array('ge' => 'grupoencomienda'), 'ge.id_grupoe=v.grupoe', array('ge.cantidad_asientos as ComparteEncomienda', 'ge.destino as destinoGrupoE', 'id_grupoe as grupoEncomienda', 'nombre as nombreGrupoE'));
        $select->where("v.fecha between '$desde' AND '$hasta'");
        $select->where("v.estado=?", 'Activo');
        if (!is_null($filters)) {
            switch ($filters ['tipo']) {
                case "propietario" :
                    $select->where("p.id_persona=?", $filters ['campo']);
                    break;

                default :
                    ;
                    break;
            }
        }
        $select->group(
                array('p.nombres', 'p.id_persona', 'b.numero', 'v.fecha', 'v.hora',
                    'v.id_viaje', 'v.destino', 'v.oficina', 'c.nombre',
                    'g.cantidad_asientos', 'g.destino', 'id_grupo', 'g.nombre', //'m.total',
                    'ge.cantidad_asientos', 'ge.destino', 'ge.id_grupoe', 'ge.nombre'));
        $select->order('v.fecha');
        $select->order('v.hora');
        $select->order('b.numero');
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     *
     * @param Identificador $idviaje     identificador del viaje
     * @return Array()
     */
    public function findByIdAllData($idviaje) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje', 'fecha', 'hora', 'pasaje', 'numero_salida', 'estado', 'grupo', 'grupoe', 'carril', 'oficina'));
        $select->joinInner(array('b' => 'bus'), 'v.bus=b.id_bus', array('id_bus', 'numero as interno', 'modelo', 'placa'));
        $select->joinInner(array('m' => 'modelo'), "b.modelo=m.id_modelo", array('descripcion'));
        $select->joinInner(array('pb' => 'persona_bus'), 'pb.bus=b.id_bus', null);
        $select->joinInner(array('p' => 'persona'), 'p.id_persona=pb.persona', array('nombres as propietario'));
        $select->joinInner(array('d' => 'destino'), 'd.id_destino=v.destino', array('salida', 'llegada'));
        $select->joinInner(array('c' => 'ciudad'), 'c.id_ciudad=d.salida', array('c.nombre as origen'));
        $select->joinInner(array('cd' => 'ciudad'), 'cd.id_ciudad=d.llegada', array("cd.id_ciudad as idDestino", 'cd.nombre as destino'));
        $select->where('v.id_viaje=?', $idviaje);
        $results = $db->fetchAll($select);
        return $results;
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
     * @date creation 02/06/2009
     */
    public function getDestinoByViaje($viaje) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('destino'));
        $select->where('v.id_viaje=?', $viaje);
        $results = $db->fetchOne($select);
        return $results;
    }

    public function getChoferesViaje($idViaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje'));
        $select->joinInner(array('cv' => 'chofer_viaje'), 'v.id_viaje=cv.viaje', array("cargo"));
        $select->joinInner(array('ch' => 'chofer'), 'cv.chofer=ch.id_chofer', array('ch.id_chofer', 'ch.numero_licencia', 'ch.nombre_chofer', 'ch.telefono',
            'ch.fecha_exp'));
        $select->where('v.id_viaje=?', $idViaje);
        $select->where('ch.estado=?', 'Activo');
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * Inserta un nuevo viaje en la base de datos
     */
    public function insertTX($viaje, $ciudadOrigenId, $ciudadDestinoID) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $destinoModel = new App_Model_DestinoModel ( );
            $grupoBusModel = new App_Model_GrupoBusModel ( );
            $modeloModel = new App_Model_ModeloModel ( );
            $choferBusModel = new App_Model_ChoferBus ( );
            $choferViajeModel = new App_Model_ChoferViaje ( );
            $pisoModel = new App_Model_PisoModel ( );
            $itemModel = new App_Model_ItemModel ( );
            $asientoModel = new App_Model_Asiento ( );
            $manifiestoModel = new App_Model_ManifiestoModel ( );
            $sucursalModel = new App_Model_SucursalModel();

            $session = Zend_Registry::get(App_Util_Statics::$SESSION);
            $sucursal = $sucursalModel->getById($session->person->sucursal);
            
            $idPersona = $session->person->id_persona;
            $destino = $destinoModel->getByOrigenDestino($ciudadOrigenId, $ciudadDestinoID);
            $idBus = base64_decode($viaje ['bus']);

            $viaje ['bus'] = $idBus;
            $viaje ['destino'] = $destino->id_destino;
            $viaje ['oficina'] = $destino->oficina;
            $grupoId = $grupoBusModel->getIdGrupoForBus($idBus);
            $viaje ['grupo'] = $grupoId;
            $modelo = $modeloModel->getModelByBus($idBus);
            $pasajeModelo = strtolower(str_replace(" ", "", $modelo->descripcion));
            $viaje ['carril'] = $sucursal->carril;
            $viaje ['pasaje'] = $ciudadDestinoID->$pasajeModelo;
            $viaje ['encargado'] = $idPersona;
            $viaje ['descripcion'] = '';
            $this->insert($viaje);
            
            $idViaje = $db->lastSequenceId('seq_viaje');
            

            $choferes = $choferBusModel->getChoferesViaje($idBus);

            $dataManifiesto ['id_manifiesto'] = 'nuevo';
            $dataManifiesto ['fecha'] = date('Y-m-d');
            $dataManifiesto ['hora'] = date('H:i');
            $dataManifiesto ['despachador'] = $idPersona;
            $dataManifiesto ['viaje'] = 'via-'.$idViaje;
            $dataManifiesto ['bus'] = $idBus;
            $first = next($choferes);
            $dataManifiesto ['chofer'] = $first ['chofer'];
            $dataManifiesto ['total'] = 0;
            $dataManifiesto ['origen'] = $destino->salida;
            $dataManifiesto ['destino'] = $destino->llegada;
            $dataManifiesto ['estado'] = 'Activo';
            $dataManifiesto ['tipo'] = 'Encomienda';
            $manifiestoModel->insert($dataManifiesto);
            foreach ($choferes as $chofer) {
                $newChofer ['viaje'] = 'via-'.$idViaje;
                $newChofer ['chofer'] = $chofer ['id_chofer'];
                $newChofer ['cargo'] = $chofer ['cargo'];
                $choferViajeModel->insert($newChofer);
            }

            $piso = $pisoModel->getByTipo($modelo->tipo);
            $items = $itemModel->getAllByPisoTipoNombre($piso->id_piso, 'Asiento');
            foreach ($items as $item) {
                $asiento ['id_asiento'] = $item->id_item;
                $asiento ['viaje'] = 'via-'.$idViaje;
                $asiento ['item'] = $item->id_item;
                $asiento ['estado'] = 'Vacante';
                $asiento ['pasaje'] = 0;
                $asiento ['factura'] = NULL;
                $asiento ['vendedor'] = 'per-100';
                $asiento ['sucursal'] = 'suc-100';
                $asiento ['numero_factura'] = 0;
                $asientoModel->insert($asiento);
                unset($asiento);
            }
            $db->commit();
            return array("error" => false, "viajeId" => 'via-'.$idViaje, "message" => "Registro de viaje Exitoso");
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->err($zdbe);
            return array("error" => true, "message" => "No se pudo registrar el Viaje");
        }
    }

    /**
     * Funcion encargada de recuperar el total de ingreso por concepto de pasajes en un viaje
     * seleccionado por fecha, ademas de el total de egresos
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 29/04/2009
     */
    public function getArqueoFecha($fechaSeleccionada) {
        $db = $this->getAdapter();
        //		$db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje', 'v.hora', 'v.oficina'));
        $select->joinInner(array('b' => 'bus'), 'v.bus=b.id_bus', array('numero'));
        $select->joinLeft(array('f' => 'factura'), "v.id_viaje=f.viaje and f.estado='Activo'", array('COALESCE(SUM(f.monto),0) as ingresos'));
        //		$select->joinLeft ( array ('e' => 'egreso' ), "v.id_viaje=e.viaje AND e.estado='Activo'",
        //				array ('COALESCE(e.monto,0) as egreso' ) );
        $select->group(array('v.id_viaje', 'v.hora', 'v.oficina', 'b.numero'));
        $select->where('f.fecha=?', $fechaSeleccionada);
        $select->where('v.estado=?', 'Activo');
        $select->order('b.numero');
        //				echo $select->__toString();
        $results = $db->fetchAll($select);
        return $results;
    }

    /* Funcion encargada de recuperar el total de ingreso por concepto de pasajes en un viaje 
     * seleccionado por fecha, ademas de el total de egresos
     * 
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 29/04/2009
     */

    public function getEgresosByFecha($fechaSeleccionada) {
        $db = $this->getAdapter();
        //		$db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje', 'v.hora', 'v.oficina'));
        $select->joinInner(array('b' => 'bus'), 'v.bus=b.id_bus', array('numero'));
        $select->joinLeft(array('e' => 'egreso'), "v.id_viaje=e.viaje AND e.estado='Activo'", array('sum(COALESCE(e.monto,0)) as egresos'));
        $select->group(array('v.id_viaje', 'v.hora', 'v.oficina', 'b.numero'));
        $select->where('e.fecha=?', $fechaSeleccionada);
        $select->where('v.estado=?', 'Activo');
        $select->order('b.numero');
        //				echo $select->__toString();
        $results = $db->fetchAll($select);
        return $results;
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
     * @date creation 29/04/2009
     */
    public function getRetencionesOficiaFecha($fechaSeleccionada) {
        $db = $this->getAdapter();
        //		$db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('COALESCE(SUM(oficina),0)'));
        $select->where('v.fecha=?', $fechaSeleccionada);
        $select->where('v.estado=?', 'Activo');
        //		echo $select->__toString();
        $results = $db->fetchOne($select);
        return $results;
    }

    /**
     * Recupera la sumatoria del monto por concepto de retencion oficina
     * en cada viaje en un rango de fechas
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date  now()
     *
     *
     */
    function getRetencionesOficiaMes($desde, $hasta) {
        $db = $this->getAdapter();
        //		$db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('COALESCE(SUM(oficina),0)'));
        $select->where("v.fecha BETWEEN '$desde' AND '$hasta'");
        $select->where('v.estado=?', 'Activo');
        //		echo $select->__toString();
        $results = $db->fetchOne($select);
        return $results;
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
     * @date creation 30/04/2009
     */
    public function getViajesFechaWhithDestino($fecha) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $SISTEM_DEFAULTS = Statics::$SISTEM_DEFAULTS;

        $select->from(array('v' => 'viaje'), array('id_viaje', 'fecha', 'hora'));
        $select->joinInner(array('b' => 'bus'), 'v.bus=b.id_bus', array('id_bus', 'numero as interno'));
        $select->joinInner(array('d' => 'destino'), 'd.id_destino=v.destino', null);
        $select->joinInner(array('co' => 'ciudad'), "co.id_ciudad=d.salida AND co.id_ciudad='" . $SISTEM_DEFAULTS ['Id_Ciudad_Sistema'] .
                "'", array('nombre as origen', 'id_ciudad as idOrigen'));
        $select->joinInner(array('cd' => 'ciudad'), "cd.id_ciudad=d.llegada ", array('nombre as destino', 'id_ciudad as idDestino'));
        $select->where('v.fecha=?', $fecha);
        $select->where('v.estado=?', "Activo");
        $select->order('d.llegada');
        $select->order('v.hora');
        $select->order('b.numero');
        //		echo $select->__toString();
        $results = $db->fetchAll($select);

        return $results;
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
     * @date creation 30/04/2009
     */
    public function getViajesByOrigenFecha($origen, $fecha) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('id_viaje', 'fecha', 'hora', 'destino as idDestino', 'bus'));
        $select->joinInner(array('b' => 'bus'), 'v.bus=b.id_bus', array('numero as interno'));
        $select->joinInner(array('m' => 'modelo'), 'b.modelo=m.id_modelo', array('descripcion as modelo'));
        $select->joinInner(array('d' => 'destino'), 'd.id_destino=v.destino', null);
        $select->joinInner(array('co' => 'ciudad'), "co.id_ciudad=d.salida AND co.id_ciudad='" . $origen . "'", array('nombre as origen'));
        $select->joinInner(array('cd' => 'ciudad'), "cd.id_ciudad=d.llegada ", array('nombre AS destino', 'id_ciudad AS idCiudadDestino'));
        $select->where("v.estado='Activo' and v.fecha=?", $fecha);
        $select->order('cd.nombre');
        $select->order('v.hora');
        $select->order('b.numero');
        //				echo $select->__toString();
        $results = $db->fetchAll($select);

        return $results;
    }

    /**
     * retorna la sumatoria de egresos
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 28/05/2009
     */
    public function getMultasForViaje($viaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'egreso'), array('COALESCE(SUM(e.monto),0) as multa'));
        $select->joinInner(array('te' => 'tipo_egreso'), "upper(te.nombre) = upper(e.tipo) and e.estado='Activo'", null);
        $select->where("e.viaje =?", $viaje);
        $select->where("te.compartido =?", true);
        $select->where("te.automatico =false");
        //$select->where ( "te.estado =?", "Activo" );
        $results = $db->fetchRow($select);
        //		echo $select->__toString();
        return $results;
    }

    /**
     * retorna la sumatoria de egresos activo que no sean compartidos
     * sin importar si el tipo de egreso es activo o inactivo
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 28/05/2009
     */
    public function getEgresosForViaje($viaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('e' => 'egreso'), array('COALESCE(SUM(e.monto),0) as egreso'));
        $select->joinInner(array('te' => 'tipo_egreso'), "upper(te.nombre) = upper(e.tipo) and e.estado='Activo'", null);
        $select->where("e.viaje =?", $viaje);
        $select->where("te.compartido=false");
        $select->where("te.automatico=false");
        //$select->where ( "te.estado =?", "Activo");
        //		echo $select->__toString();
        $results = $db->fetchRow($select);
        return $results;
    }

    /**
     * .. Busca todos los egresos de viajes que sean Automaticos
     * @param $viaje
     * @return unknown_type
     */
    public function getEgresosAutomaticoForViaje($viaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->from(array('e' => 'egreso'), array('e.tipo', 'COALESCE(SUM(e.monto),0) as egreso'));
        $select->joinInner(array('te' => 'tipo_egreso'), "upper(te.nombre) = upper(e.tipo) and e.estado='Activo'", null);
        $select->where("e.viaje =?", $viaje);
        $select->where("te.automatico=true");
        $select->where("te.estado =?", "Activo");
        $select->group("e.tipo");
        //		echo $select->__toString();
        $results = $db->fetchAll($select);
        return $results;
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
     * @date creation 28/05/2009
     */
    public function getNumeroViajesFecha($fecha) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('COUNT(id_viaje) as numViajes'));
        $select->where("v.fecha =?", $fecha);
        $results = $db->fetchOne($select);
        return $results;
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
     * @date creation 28/05/2009
     */
    public function getMultasForFecha($fecha) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('COUNT(*) as numViajes'));
        $select->joinLeft(array('e' => 'egreso'), 'e.viaje=v.id_viaje', array('COALESCE(SUM(e.monto),0) as multaTotal'));
        $select->joinInner(array('te' => 'tipo_egreso'), 'te.nombre = e.tipo', null);
        $select->where("v.fecha =?", $fecha);
        $select->where("te.compartido =?", true);
        $results = $db->fetchRow($select);
        return $results;
    }

    /**
     * recupera el monto maximo establecido para este viaje
     * recibe como parametros el identificador del viaje y retorna el precio maximo
     * @param $id_viaje identificador del viaje
     * @return numeric el monto maximo permitido para este viaje
     */
    public function getPresioMax($id_viaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();

        $select->from(array('v' => 'viaje'), array('pasaje'));
        $select->where('v.id_viaje=?', $id_viaje);
        $results = $db->fetchOne($select);

        return $results;
    }

    /**
     * Recupera el numero de asientos con un estado (Venta,Reserva,Libre,Vacante)
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 25/06/2009
     */
    public function countAsientosByEstadoViaje($idviaje, $estado) {
        $db = $this->getAdapter();
        //		$db->getFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('a' => 'asiento'), array('count(*)'));
        $select->where('viaje=?', $idviaje);
        $select->where('estado=?', $estado);

        $results = $db->fetchOne($select);
        return $results;
    }

    /**
     * Recupera el numero de asientos Disponibles (Reserva o Vacante)
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 25/06/2009
     */
    public function asientosDisponibles($idviaje) {
        $db = $this->getAdapter();
        //		$db->getFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('a' => 'asiento'), array('count(*)'));
        $select->where('viaje=?', $idviaje);
        $select->where("(estado='Vacante' OR estado='Reserva')");
        $results = $db->fetchOne($select);
        return $results;
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
     * @date creation 25/06/2009
     */
    public function getNumeroAsientos($idviaje) {
        $db = $this->getAdapter();
        //		$db->getFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('i' => 'item'), array('max(numero)'));
        $select->joinInner(array('a' => 'asiento'), "a.item=i.id_item and a.viaje='$idviaje'", null);
        $results = $db->fetchOne($select);
        return $results;
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
     * @date creation 02/07/2009
     */
    public function updateGrupo($viaje, $grupo) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $where [] = "id_viaje='$viaje'";
            $data['grupo'] = $grupo;
            $resp = $this->update($data, $where);
            if ($resp > 0) {
                $db->commit();
            } else {
                throw new Zend_Db_Exception(
                        "No se pudo actualizar el Grupo del Viaje 0", 125);
            }
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception(
                    "No se pudo actualizar el Grupo del Viaje", 125);
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
     * @date creation 21/07/2009
     */
    public function countViajesFechas($desde, $hasta) {
        $db = $this->getAdapter();
        //		$db->getFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('count(*)'));
        $select->where("fecha between '$desde' and '$hasta' ");
        $select->where('estado=?', 'Activo');

        $results = $db->fetchOne($select);
        return $results;
    }

    /**
     * retorna la sumatoria de los egresos de todo un mes pasados
     * como fechas  inicio y fin sin importar que el tipode egreso sea activo o inactivo
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 21/07/2009
     */
    public function getMultasFechas($dateIni, $dateFin) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('e' => 'egreso'), array('SUM(e.monto) as multa'));
        $select->joinInner(array('te' => 'tipo_egreso'), "upper(te.nombre) = upper(e.tipo) and e.estado='Activo'", null);
        $select->joinInner(array('v' => 'viaje'), "v.id_viaje = e.viaje and v.estado='Activo'", null);
        $select->where("v.fecha between '$dateIni' and '$dateFin'");
        $select->where("te.compartido =?", true);
        //$select->where ( "te.estado =?", "Activo" );
        $results = $db->fetchOne($select);
        //		echo $select->__toString();
        return $results;
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
     * @date creation 27/07/2009
     */
    public function getOficinaFechas($desde, $hasta) {
        $db = $this->getAdapter();
        //		$db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('v' => 'viaje'), array('SUM(oficina)'));
        $select->where("v.fecha between '$desde' and '$hasta'");
        $select->where('v.estado=?', 'Activo');
        //		echo $select->__toString();
        $results = $db->fetchOne($select);
        return $results;
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1
     * @date creation 20/08/2009
     */
    function getTotalEncomienda($viaje) {
        $db = $this->getAdapter();
        //		$db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('m' => 'manifiesto'), array('COALESCE(SUM(m.total),0) as total'));
        $select->where("m.viaje=?", $viaje);
        $select->where("m.estado=?", 'Activo');
        // 		echo $select->__toString();
        return $db->fetchOne($select);
    }

    /**
     * ...
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date  now()
     */
    function updateMontoOficinaPorcentaje($desde, $hasta, $prop1, $prop2, $prop3) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
//            $where [] = "v.fecha BETWEEN '$desde' AND '$hasta'";
//            $where [] = "v.id_viaje IN (SELECT id_viaje
//                                        FROM viaje v,persona_bus pb
//                                        WHERE v.bus=pb.bus AND (pb.persona='$prop1' OR pb.persona='$prop2' OR pb.persona='$prop3'))";
//            $data['oficina']="(select sum(a.pasaje) from asiento a where v.id_viaje=a.viaje and estado='Venta')";
            $sql = "update viaje v
                set oficina=(select sum(a.pasaje)*0.10 from asiento a where v.id_viaje=a.viaje and estado='Venta')
                where v.fecha between '$desde' and '$hasta' and v.id_viaje
                IN (	Select id_viaje
                        FROM viaje v,persona_bus pb
                        WHERE v.bus=pb.bus AND (pb.persona='$prop1' OR pb.persona='$prop2' OR pb.persona='$prop3'))";
            $sql = "update viaje v
                    set oficina=(
                                 (select sum(a.pasaje) from asiento a where v.id_viaje=a.viaje and estado='Venta')+
                                 (select sum(m.total) from manifiesto m where v.id_viaje=m.viaje and m.estado='Activo')
                            )*0.10
                    where v.fecha between '$desde' and '$hasta' and
                          v.id_viaje IN (
                            SELECT id_viaje
                            FROM viaje v,persona_bus pb
                            WHERE v.bus=pb.bus AND (pb.persona='$prop1' OR pb.persona='$prop2' OR pb.persona='$prop3'))";
            $resp = $db->query($sql); //update (array("v"=>"viaje"), $data,$where );
            if ($resp > 0) {
                $db->commit();
            } else {
                throw new Zend_Db_Exception(
                        "No se pudo actualizar el porcentaje de oficna a los viajes 1", 125);
            }
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception(
                    "No se pudo actualizar el porcentaje de oficna a los viajes", 125);
        }
    }

    /**
     * ...
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date  now()
     */
    function updateMontoOficinaPorcentaje2($viaje, $monto) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $where [] = "id_viaje ='$viaje'";
            $data['oficina'] = $monto;
            $resp = $this->update($data, $where);
            if ($resp > 0) {
                $db->commit();
            } else {
                throw new Zend_Db_Exception(
                        "No se pudo actualizar el porcentaje de oficna a los viajes 1", 125);
            }
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception(
                    "No se pudo actualizar el porcentaje de oficna a los viajes", 125);
        }
    }

    /**
     * ...
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.1
     * @date  now()
     */
    function getByViajeInactivoFechas($desde, $hasta) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('v' => 'viaje'), array(fecha, hora, id_viaje));
        $select->joinInner(array('b' => 'bus'), "b.id_bus=v.bus", array("numero AS interno"));
        $select->joinLeft(array('e' => 'egreso'), "e.viaje=v.id_viaje and e.estado='Activo' and v.estado<>'Activo'", array("COALESCE(monto,0) AS egreso", "id_egreso"));
        $select->joinLeft(array('a' => 'asiento'), "a.viaje=v.id_viaje and a.estado='Venta' and v.estado<>'Activo'", null);
        $select->joinLeft(array('i' => 'item'), "i.id_item=a.item ", array(numero));
        $select->where("v.fecha BETWEEN '$desde' AND '$hasta'");
        $select->group("v.id_viaje");
        $select->group("v.fecha");
        $select->group("v.hora");
        $select->group("v.hora");
        $select->group("v.estado");
        $select->group("b.numero");
        $select->group("e.monto");
        $select->group("i.numero");
        $select->group("e.id_egreso");
        $select->having("e.monto>0 OR i.numero is not null and v.estado<>'Activo'");
        $select->order(array(v => id_viaje));
//      echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 24-02-2010
     */
    function getItinerario($fecha, $ciudadOrigen, $ciudadDestino) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('h' => 'hora_salida'), array("hora as hora_salida"));
        $select->joinInner(array('d' => 'destino'), "h.destino=d.id_destino", null);
        $select->joinLeft(array('v' => 'viaje'), "(d.id_destino=v.destino and h.hora=v.hora and v.fecha='$fecha')", array("id_viaje", "fecha", "estado", "numero_salida", "pasaje"));
        $select->joinLeft(array('b' => 'bus'), "v.bus=b.id_bus", array("numero"));
        $select->joinLeft(array('m' => 'modelo'), "b.modelo = m.id_modelo", array('m.descripcion'));
        $select->where("d.salida='$ciudadOrigen' and d.llegada='$ciudadDestino' ");
        $select->order('h.hora');
//      echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     *  recupera el itinerario con salidas reales
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 24-02-2010
     */
    function getItinerarioActual($fecha, $ciudadOrigen, $ciudadDestino) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('v' => 'viaje'), array("id_viaje", "to_char(hora,'HH24:MI') as hora", "pasaje"));
        $select->joinInner(array('d' => 'destino'), "v.destino=d.id_destino", null);
        $select->joinInner(array('b' => 'bus'), "v.bus=b.id_bus", array("numero"));
        $select->joinInner(array('m' => 'modelo'), "b.modelo = m.id_modelo", array('m.descripcion'));
        $select->where("d.salida='$ciudadOrigen' and d.llegada='$ciudadDestino' and v.fecha='$fecha'");
        $select->order('hora');
        $select->order('m.descripcion');
        $select->order('v.id_viaje');
        $select->order('v.pasaje');
        $select->order('b.numero');
//      echo $select->__toString();
        return $db->fetchAll($select);
    }

    /**
     * Recupera los datos de un manifiesto para este viaje en esta sucursal
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 21-03-2010
     */
    function getManifiestoForViajeSucursal($viaje, $sucursal) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('m' => 'manifiesto'), array("id_manifiesto", "fecha", "hora", "total"));
        $select->joinLeft(array('v' => 'viaje'), "v.id_viaje=m.viaje ", array("fecha", "estado", "numero_salida", "pasaje"));
        $select->joinInner(array('s' => 'sucursal'), "s.id_sucursal=m.origen", array("s.nombre"));
        $select->joinLeft(array('b' => 'bus'), "b.id_bus=m.bus", array("numero"));
        $select->joinLeft(array('p' => 'persona'), "p.id_persona=m.despachador", array("p.nombres"));
        $select->where("m.viaje='$viaje' and m.origen='$sucursal' ");
//        echo $select->__toString();
        return $db->fetchRow($select);
    }

    /**
     * Recupera la informacion detallada de un viaje
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-10
     */
    function getDetalleViaje($idViaje) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('v' => 'viaje'), array("id_viaje", "fecha", "hora"));
        $select->joinInner(array('b' => 'bus'), "b.id_bus=v.bus", array("numero", "placa"));
        $select->joinInner(array('m' => 'modelo'), "m.id_modelo=b.modelo", array("descripcion"));
        $select->joinInner(array('cv' => 'chofer_viaje'), "cv.viaje=v.id_viaje", array("cargo"));
        $select->joinInner(array('ch' => 'chofer'), "cv.chofer=ch.id_chofer", array("id_chofer", "nombre_chofer"));
        $select->where("v.id_viaje='$idViaje'");
        $select->order("cv.cargo");
//        echo $select->__toString();
        return $db->fetchAll($select);
    }

}
