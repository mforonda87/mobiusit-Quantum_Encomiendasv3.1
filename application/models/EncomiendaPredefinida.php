<?php

/**
 * SucursalModel
 *
 * @author Administrador
 * @version
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_EncomiendaPredefinida extends Zend_Db_Table_Abstract {
    /**
     * The default table name
     */
    protected $_name = 'encomienda_predefinida';
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
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('ed'=>'encomienda_predefinida'),array('id_predefinida','descripcion','preciounitario','peso','estado','tipo'));
        $select->where('ed.estado=?','Activo');
        $select->order('descripcion');
        $results = $db->fetchAll($select);
        return $results;
    }
}
