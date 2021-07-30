<?php

/**
 * Bus
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_DosificacionModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'datos_factura';
    protected $_primary = 'id_datos_factura';

    public function findById($idDatosFactura) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('d' => 'datos_factura'), array('id_datos_factura', 'sucursal', 'autorizacion', 'llave', 'inicio',
            'numero_factura', 'fecha_limite', 'autoimpresor', 'estado', 'fin','leyenda_secundaria'));
        $select->where("id_datos_factura=?", $idDatosFactura);
        $results = $db->fetchRow($select);
        return $results;
    }

    /**
     * @autor Poloche
     * @version beta rc1
     * @2009
     */
    public function saveTx($dosificacion) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            unset($dosificacion ['tipo']);
            $dosificacion ['inicio'] = intval($dosificacion ['inicio']);
            $dosificacion ['fin'] = intval($dosificacion ['fin']);
            $dosificacion ['numero_factura'] = $dosificacion ['inicio'];

            $this->insert($dosificacion);
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception(" No se pudo Registrar La Dosificacion ", 125);
        }
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

    public function getDosificacionesForDropDown($idSucursal) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('d' => 'datos_factura'), array('id_datos_factura', 'autorizacion', 'llave'));
        $select->where("sucursal=?", $idSucursal);
        $items = $db->fetchAll($select);
        if (sizeof($items) > 0) {
            foreach ($items as $item) {
                $tipo = 'Manual';
                if ($item->llave != 'Manual')
                    $tipo = 'Automatico';
                $dosificaciones [$item->id_datos_factura] = $item->autorizacion . ' - ' . $tipo;
            }

            return $dosificaciones;
        } else {
            return array('todos' => 'No existe Dosif.');
        }
    }

    public function getManualBySucusalSistema($idSucursal, $sistema) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('d' => 'datos_factura'), array('id_datos_factura', 'autorizacion', 'llave'));
        $select->where("sucursal=?", $idSucursal);
        $select->where("UPPER(sistema)=?", strtoupper($sistema));
        $select->where("llave=?", "Manual");
        $select->where("fecha_limite>=?", date("Y-m-d"));
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
     * Recupera la Unica dosificacion activa para una sucursal
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version v1.0
     * @date creation 23/02/2010
     */
    public function getLastAutomaticoBySucursal($sucursal) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('d' => 'datos_factura'), array('id_datos_factura', 'sucursal', 'autorizacion', 'llave', 'inicio',
            'numero_factura', 'fecha_limite', 'autoimpresor', 'estado', 'fin', 'leyenda_secundaria'));
        
        $select->where("llave<>?", 'Manual');
        $select->where("fecha_limite>=?", date('Y-m-d'));
        $select->where("estado=?", 'Activo');
        $select->where("sucursal=?", $sucursal);
        $select->where("sistema=?", "ENCOMIENDA");
        $log = Zend_Registry::get("log");
        $log->info($select->__toString());
        $results = $db->fetchRow($select);
        return $results;
    }

    /**
     * Busca Todas las Dosificaciones( datos_factura ) de tipo manual
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 24/08/2009
     */
    public function getAllManual() {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('d' => 'datos_factura'), array('id_datos_factura', 'sucursal', 'autorizacion', 'llave', 'inicio',
            'numero_factura', 'fecha_limite', 'autoimpresor', 'estado', 'fin'));
        $select->where("llave=?", 'Manual');
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * Obtiene las dosificaciones que vayan a expirar en la fecha indicada
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 20/08/2009
     */
    function getByExpireFecha($fecha) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('d' => 'datos_factura'), array('id_datos_factura', 'sucursal', 'autorizacion', 'llave', 'inicio',
            'numero_factura', 'fecha_limite', 'autoimpresor', 'estado', 'fin'));
        $select->joinInner(array(s => sucursal), "s.id_sucursal=d.sucursal", array("nombre as sucursal"));
        $select->joinInner(array(c => ciudad), "c.id_ciudad=s.ciudad", array("nombre as ciudad"));
        $select->where("d.estado='Activo' AND fecha_limite<=?", $fecha);
        //$results[]= $select->__toString();
        $results = $db->fetchAll($select);
        return $results;
    }

}
