<?php

/**
 * ChoferBus
 *  
 * @author Administrador
 * @version 
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ChoferModel extends Zend_Db_Table_Abstract {

    /**
     * The default table name 
     */
    protected $_name = 'chofer';

    public function getTopTenByTermn($term) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->from(array('ch' => 'chofer'), array('id_chofer', 'numero_licencia', 'nombre_chofer', 'fecha_exp', 'telefono', 'estado'));
        $select->where('ch.estado=?', 'Activo');
        $select->where('ch.nombre_chofer like ?', "%".strtoupper($term)."%");
        $select->limit(10, 0);
        $log = Zend_Registry::get("log");
        $log->info("el query es ".$select->__toString());        
        $results = $db->fetchAll($select);
        return $results;
    }

}
