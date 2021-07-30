<?php

/**
 * SucursalModel
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_MovimientoEncomienda extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'movimiento_encomienda';
    protected $_primary = 'id_movimiento';
    protected $_sequence = false;


}
