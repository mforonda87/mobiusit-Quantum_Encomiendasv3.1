<?php

/**
 * Asiento
 *
 * @author Administrador
 * @version
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ConfiguracionGral extends Zend_Db_Table_Abstract {
    /**
     * The default table name
     */
    protected $_name = 'configuracion_sistema';

    /**
     *  recupera la informacion de la tabla de configuracion
     *  para el sistema en base a un key
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 12-03-2010
     */
    function getByKey($key) {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('c' => 'configuracion_sistema' ), array ('value' ) );
        $select->where ( "c.key=?", $key );
        return $db->fetchOne ( $select );
    }

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 12-03-2010
     */
    function getAll() {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('c' => 'configuracion_sistema' ), array ('key','value' ) );
        return $db->fetchAll ( $select );
    }
}
