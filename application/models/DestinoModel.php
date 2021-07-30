<?php

/**
 * Bus
 *
 * @author Administrador
 * @version
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_DestinoModel extends Zend_Db_Table_Abstract {
    /**
     * The default table name
     */
    protected $_name = 'destino';
    protected $_primary = 'id_destino';

    public function getByOrigenDestino($origen, $destino) {
        $db = $this->getAdapter ();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select ();
        $select->from ( array ('d' => 'destino' ),
                array ('id_destino', 'salida', 'llegada', 'oficina', 'busnormal', 'bussemicama',
                'buscama', 'estado' ) );
        $select->where ( "d.salida=?", $origen );
        $select->where ( "d.llegada=?", $destino );
//        echo $select->__toString();
        $results = $db->fetchRow ( $select );
        return $results;
    }
    
    public function getByOrigen($origen) {
        $db = $this->getAdapter ();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select ();
        $select->from ( array ('d' => 'destino' ),
                array ('id_destino', 'salida', 'llegada', 'oficina', 'busnormal', 'bussemicama',
                'buscama', 'estado' ) );
        $select->joinInner ( array (destino => ciudad ), "destino.id_ciudad=d.llegada",
                array ('nombre as destino' ) );
        
        $select->where ( "d.salida=?", $origen );
        $results = $db->fetchAll ( $select );
        return $results;
    }

    public function getNumberForDropDown() {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('b' => 'bus' ), array ('id_bus', 'numero' ) );
        $select->where ( 'b.estado=?', "Activo" );
        $select->order ( 'b.numero' );
        //		echo $select->__toString();
        $results = $db->fetchPairs ( $select );
        return $results;
    }

    public function findById($id) {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('b' => 'bus' ),
                array ('id_bus', 'numero', 'placa', 'ciudad_actual', 'descripcion', 'estado',
                'modelo' ) );
        $select->joinInner ( array ('pb' => 'persona_bus' ), 'b.id_bus=pb.bus',
                'persona as propietario' );
        $select->where ( 'b.id_bus=?', $id );
        $select->where ( 'b.estado=?', "Activo" );
        $select->order ( 'b.numero' );
        //		echo $select->__toString();
        $results = $db->fetchRow ( $select );
        return $results;
    }
    /**
     *Recupera el identificador del destino en base a la ciudad de origen y la ciudad de destino
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 17/04/2009
     */
    public function getIdByOrigenDestino($ciudadOrigen, $ciudadDestino) {
        $db = $this->getAdapter ();
        $select = $db->select ();
        $select->from ( array ('d' => 'destino' ), array ('id_destino' ) );
        $select->where ( 'd.salida=?', $ciudadOrigen );
        $select->where ( 'd.llegada=?', $ciudadDestino );
        $select->where ( 'd.estado=?', "Activo" );
        $results = $db->fetchOne ( $select );
        return $results;
    }

    /**
     *..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 25/06/2009
     */
    public function getAll() {
        $db = $this->getAdapter ();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select ();
        $select->from ( array (d => destino ),
                array ("d.id_destino", oficina, busnormal, buscama, bussemicama ) );
        $select->joinInner ( array (origen => ciudad ), "origen.id_ciudad=d.salida",
                array ('nombre as origen' ) );
        $select->joinInner ( array (destino => ciudad ), "destino.id_ciudad=d.llegada",
                array ('nombre as destino' ) );
        $select->where ( 'd.estado=?', "Activo" );
        $select->order ( 'd.salida' );
        $results = $db->fetchAll ( $select );
        return $results;
    }
    public function getAllArray() {
        $db = $this->getAdapter ();
        $db->setFetchMode ( Zend_Db::FETCH_ASSOC );
        $select = $db->select ();
        $select->from ( array (d => destino ),
                array ("origen.nombre as origen", "destino.nombre as destino", "d.id_destino",
                oficina, busnormal, buscama, bussemicama ) );
        $select->joinInner ( array (origen => ciudad ), "origen.id_ciudad=d.salida", null );
        $select->joinInner ( array (destino => ciudad ), "destino.id_ciudad=d.llegada", null );
        $select->where ( 'd.estado=?', "Activo" );
        $select->order ( 'd.salida' );
        $results = $db->fetchAll ( $select );
        return $results;
    }

    /**
     *..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 10/08/2009
     */
    public function saveTx($formDestino) {
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        try {
            $this->insert ( $formDestino );
            $db->commit ();

        } catch ( Zend_Db_Exception $zdbe ) {
            $db->rollBack ();
            Initializer::log_error ( $zdbe );
            throw new Zend_Db_Exception ( "No se pudo Quitar Asiento ", 125 );
        }
    }

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date 2010-01-29
     */
    function updateTX ( $destinoForm ) {
        $db = $this->getAdapter ();
        $db = $this->getAdapter ();
        $db->beginTransaction ();
        $where[]="id_destino='".$destinoForm['id_destino']."'";
        unset($destinoForm['id_destino']);
        unset($destinoForm['sucursal']);
        if($this->update ( $destinoForm,$where )>0)
            $db->commit ();
        else {
            $db->rollBack ();
            Initializer::log_error ( "No se pudo actualizar el destino" );
            throw new Zend_Db_Exception("No se pudo actualizar el destino");
        }

    }

    /**
     *..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 15/08/2009
     */
    public function getById($destinoViaje) {
        $db = $this->getAdapter ();
        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select ();
        $select->from ( array (d => destino ),
                array ("d.id_destino", oficina, busnormal, buscama, bussemicama ) );
        $select->joinInner(array(co=>ciudad), "co.id_ciudad=d.salida", array("co.id_ciudad as salida",'co.nombre as nombreSalida'));
        $select->joinInner(array(cd=>ciudad), "cd.id_ciudad=d.llegada", array("cd.id_ciudad as llegada",'cd.nombre as nombreLlegada'));
        $select->where ( 'd.estado=?', "Activo" );
        $select->where ( 'd.id_destino=?', $destinoViaje );
        $results = $db->fetchRow ( $select );
        return $results;
    }
}
