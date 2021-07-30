<?php

/**
 * SucursalModel
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Precio extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'precio_predefinido';
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
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('p' => $this->_name), array('precio', 'predefinida'));
        $select->where('p.estado=?', 'Activo');
        $results = $db->fetchAll($select);
        return $results;
    }

    /**
     * Recupera todos los precios de todas las encomiendas segun un destino
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
    function getByDestino($destino) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('p' => $this->_name), array('precio'));
        $select->join(array('pe' => "encomienda_predefinida"),
                "pe.id_predefinida=p.predefinida AND p.destino='$destino'",
                array("descripcion", "peso"));
        $select->order('descripcion');
        $results = $db->fetchAll($select);
        return $results;
    }

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
    function txSave($cliente) {
        $db = $this->getAdapter();
        $db->beginTransaction();
        try {
            $this->insert($cliente);
        } catch (Zend_Db_Exception $zdbe) {
            $db->rollBack();
            $log = Zend_Registry::get("log");
            $log->info($zdbe);
            throw new Zend_Db_Exception($zdbe, 125);
        }
    }

}
