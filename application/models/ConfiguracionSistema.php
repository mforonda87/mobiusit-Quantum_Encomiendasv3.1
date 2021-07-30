<?php

/**
 * CiudadModel
 *  
 * @author Administrador
 * @version 
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ConfiguracionSistema extends Zend_Db_Table_Abstract {

    /**
     * The default table name 
     */
    protected $_name = 'configuracion_sistema';

    
    public function getAll() {

        return $this->_db->fetchPairs(
                "select key, value from configuracion_sistema");
    }

    public function getByKey($key) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('cs' => 'configuracion_sistema'), array('key', 'value'));
        $select->where('cs.key=?', $key);
        return $db->fetchRow($select);
    }

    /**
     * Registra una nueva configuracion del sistema
     * 
     * 
     * @param array $conf       datos de la configuracion     
     * @author Poloche
     * @version V1.1
     */
    public function insetTX($conf) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $this->insert($conf);
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception("No se pudo guardar la Configuracion para(" . $conf['key'] . ") ", 125);
        }
    }

    /**
     * Guarda la configuracion basica en una sola vez
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.2
     * @date creation 2011-08-05
     */
    function saveDefault($nit,$empresa) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $data1[StaticsVenta::$nitEmpresa]=$nit; 
            $data1[StaticsVenta::$nombreEmpresa]=$empresa; 
            $this->insert($conf);
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception("No se pudo guardar la Configuracion para(" . $conf['key'] . ") ", 125);
        }
    }
    /**
     * Actualiza los datos de un configuraciones del sistema
     * @param array $key 	array key,value de datos de un configuracion sistema
     * @param String $key       llave para modificar los valores de la configuracion
     * @author Poloche
     * @version V1.1
     * @example
     *
     */
    public function updateTX($key, $value) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $data ['value'] = "$value";
            $whereConf [] = "key='$key'";
            if ($this->update($data, $whereConf) <= 0) {
                throw new Zend_Db_Exception("No se pudo actualizar el Bus  ", 125);
            }
            $db->commit();
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            Initializer::log_error($zdbe);
            throw new Zend_Db_Exception("No se pudo actualizar el Bus  ", 125);
        }
    }

    function getEmpresaHeader(){
        $configuraciones = $this->getAll();
        $empresa = array(
            "title" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_1],
            "nombre" => $configuraciones[App_Util_Statics::$TITULO_FACTURA_2],
            "nit" => $configuraciones[App_Util_Statics::$nitEmpresa]
        );
        return $empresa;
    }
}
