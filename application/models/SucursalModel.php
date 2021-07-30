<?php

/**
 * SucursalModel
 *
 * @author Administrador
 * @version
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_SucursalModel extends Zend_Db_Table_Abstract {
    /**
     * The default table name
     */
    protected $_name = 'sucursal';
    protected $_sequence = false;
    public function getAll() {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->from(array('s'=>'sucursal'),array('id_sucursal','nombre','direccion','telefono','carril'));
        $select->where('s.estado=?','Activo');
        $select->order('s.nombre');
        $results = $db->fetchAll($select);
        return $results;
    }
    /**
     *
     * @param <String> $idciudad   id de la ciudad de la cual se busca todas las sucursales
     * @return <Array>  retorna un arra con todas las sucrsales de la ciudad 
     */
    public function getSucursalByCiudad($idciudad) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_ASSOC);
        $select = $db->select();
        $select->from(array('s'=>'sucursal'),array('id_sucursal','nombre','direccion','telefono','carril'));
        $select->where('s.estado=?','Activo');
        $select->where('s.ciudad=?',$idciudad);
        $select->order('s.nombre');
        $results = $db->fetchAll($select);
        return $results;
    }
    public function getSucursalByCiudadForDropDown($idCiudad) {
        $resp = $this->_db->fetchPairs (
                "select id_sucursal, nombre from sucursal where estado='Activo' and ciudad='$idCiudad'" );
        if (sizeof($resp)>0) {
            return $resp;
        }else
            return array ('todos'=>'No existe Sucursal');
    }

    public function findByid($idSucursal) {
        return $this->_db->fetchRow(
                "select id_sucursal, nombre, direccion, direccion2, numero, telefono, ciudad, carril, estado
				from sucursal 
				where id_sucursal='$idSucursal'" );
    }
    public function getCarril($idSucursal) {
        return $this->_db->fetchOne("select carril from sucursal
				where id_sucursal='$idSucursal'" );
    }

    /**
     * Recupera la informacion basica de una sucursal por el identificador
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version 1.1 rc1
     * @date creation 29/07/2009
     */
    public function getById($sucursal) {
        $db = $this->getAdapter ();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select ();
        $select->from ( array ('s' => 'sucursal' ),
                array ('id_sucursal', 'nombre', 'direccion', "numero", "direccion2","telefono", "carril","abreviacion","municipio","capital","leyenda") );
        $select->join(array('c'=>'ciudad'), "c.id_ciudad=s.ciudad", array("id_ciudad", "nombre as ciudad","nombre2"));
        $select->where ( "s.estado=?", "Activo");
        $select->where ( "id_sucursal=?", $sucursal);
        return $db->fetchRow( $select );
    }

}
