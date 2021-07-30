<?php

/**
 * Viaje
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Itinerario extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'itinerario';
    protected $_primary = 'id_itinerario';

    /**
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 24-02-2010
     */
    function getItinerario($ciudadOrigen, $ciudadDestino) {
        $db = $this->getAdapter();
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('h' => 'hora_salida'), array("hora as hora_salida","id_hora_salida"));
        $select->joinInner(array('d' => 'destino'), "h.destino=d.id_destino", null);
        $select->where("d.salida='$ciudadOrigen' and d.llegada='$ciudadDestino' ");
        $select->order('h.hora');
        return $db->fetchAll($select, null, Zend_Db::FETCH_OBJ);
    }

    /**
     *  recupera el itinerario con salidas reales
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version V1.0
     * @date 24-02-2010
     */
    function getItinerarioActual($fecha, $ciudadOrigen, $ciudadDestino) {
        $db = $this->getAdapter();
//        $db->setFetchMode ( Zend_Db::FETCH_OBJ );
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('v' => 'viaje'), array("id_viaje", "to_char(hora,'HH24:MI') as hora", "pasaje"));
        $select->joinInner(array('d' => 'destino'), "v.destino=d.id_destino", null);
        $select->joinInner(array('b' => 'bus'), "v.bus=b.id_bus", array("numero"));
        $select->joinInner(array('m' => 'modelo'), "b.modelo = m.id_modelo", array('m.descripcion'));
        $select->where("d.salida='$ciudadOrigen' and d.llegada='$ciudadDestino' and v.fecha='$fecha'");
        $select->order('hora');
        $select->order('m.descripcion');
        $select->order('v.id_viaje');
        $select->order('v.pasaje');
        $select->order('b.numero');
//      echo $select->__toString();
        return $db->fetchAll($select);
    }

}
