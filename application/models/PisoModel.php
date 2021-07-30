<?php

/**
 * Piso
 *  
 * @author Administrador
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_PisoModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'piso';
	protected $_primary = 'id_piso';

	public function getByTipo($tipo){
		$db = $this->getAdapter ();
		$db->setFetchMode(Zend_Db::FETCH_OBJ);
		$select = $db->select ();
		$select->from ( array ('p' => 'piso' ), array ('id_piso','tipo', 'numero','estado' ) );
		$select->where ( 'p.tipo=?', $tipo);
		$results = $db->fetchRow( $select );
		return $results;
	}

        /**
         * Retorna el identificador del piso que tiene el tipo y nuemro
         * de piso pasados como parametros
         * @param String $idTipo  identificador del tipo
         * @param Numbre $numero  numero del piso
         * @access public
         * @author Poloche
         * @author polochepu@gmail.com
         * @copyright Mobius IT S.R.L.
         * @copyright http://www.mobius.com.bo
         * @version 1.1
         * @date creation 20/08/2009
         */
        function getByTipoNumero($idTipo,$numero) {
            $db = $this->getAdapter ();
		//$db->setFetchMode(Zend_Db::FETCH_OBJ);
		$select = $db->select ();
		$select->from ( array ('p' => 'piso' ), array ('id_piso') );
		$select->where ( 'p.tipo=?', $idTipo);
		$select->where ( 'p.numero=?', $numero);
		return $db->fetchOne( $select );
        }
}
