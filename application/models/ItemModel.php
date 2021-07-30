<?php

/**
 * ItemModel
 *  
 * @author Administrador
 * @version 
 */

require_once 'Zend/Db/Table/Abstract.php';

class App_Model_ItemModel extends Zend_Db_Table_Abstract {
	/**
	 * The default table name 
	 */
	protected $_name = 'item';
	protected $_primary = 'id_item';

	public function getAllByPisoTipoNombre($idPiso,$nombreTipo){
		$db = $this->getAdapter ();
		$db->getFetchMode(Zend_Db::FETCH_OBJ);
		$select = $db->select ();
		$select->from ( array ('i' => 'item' ), array ('id_item','piso', 'posicion_x','posicion_y', 'tipo_item', 'numero', 'estado' ) );
		$select->joinInner(array ('ti' => 'tipo_item' ), 'i.tipo_item=ti.id_tipo_item',null);
		$select->where ( 'ti.nombre=?', $nombreTipo);
		$select->where ( 'i.piso=?', $idPiso);
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
		 * @date creation 29/05/2009
		 */
	public function getItemsByBus($bus) {
		$db = $this->getAdapter ();
//		$db->getFetchMode(Zend_Db::FETCH_OBJ);
		$select = $db->select ();
		$select->from ( array ('i' => 'item' ), array ('id_item','piso', 'posicion_x as x','posicion_y as y', 'tipo_item', 'numero') );
		$select->joinInner(array ('ti' => 'tipo_item' ), 'i.tipo_item=ti.id_tipo_item',array('nombre'));
		$select->joinInner(array('p'=>'piso'),'p.id_piso=i.piso',null);
		$select->joinInner(array('t'=>'tipo'),'t.id_tipo=p.tipo',null);
		$select->joinInner(array('m'=>'modelo'),'m.tipo=t.id_tipo',array(descripcion));
		$select->joinInner(array('b'=>'bus'),'b.modelo=m.id_modelo',array('numero as interno'));
		$select->where('b.id_bus=?',$bus);
//		echo $select->__toString();
		$results = $db->fetchAll ( $select );
		foreach ($results as $item) {
//			echo $item['x']." ".$item['y']."<br>";
			$model[$item['y']][$item['x']]=$item;
		}
		return $model;
	}

	/**
		 *..... description
		 * @param $viaje id_viaje es la llave primaria del viaje
		 * @param $interno numero del bus 
		 * @access public
	 	 * @author Poloche
		 * @author polochepu@gmail.com
		 * @copyright Mobius IT S.R.L.
		 * @copyright http://www.mobius.com.bo
		 * @version beta rc1
		 * @date creation 04/06/2009
		 */
	public function getItemsByViajeBus($viaje,$interno) {
		$db = $this->getAdapter ();
		$db->setFetchMode(Zend_Db::FETCH_ASSOC);
		$select = $db->select ();
		$select->from ( array ('i' => 'item' ), array ('id_item','piso', 'posicion_x as x','posicion_y as y', 'tipo_item', 'numero') );
		$select->joinInner(array ('ti' => 'tipo_item' ), 'i.tipo_item=ti.id_tipo_item',array('nombre'));
		$select->joinLeft(array('p'=>'piso'),'p.id_piso=i.piso',null);
		$select->joinLeft(array('t'=>'tipo'),'t.id_tipo=p.tipo',null);
		$select->joinInner(array('m'=>'modelo'),'m.tipo=t.id_tipo',array("descripcion"));
		$select->joinInner(array('b'=>'bus'),"b.modelo=m.id_modelo and b.numero='$interno'",array('numero as interno','id_bus'));
		$select->joinLeft(array('a'=>'asiento'),"a.item=i.id_item and a.viaje='$viaje'",array('id_asiento as idAsiento','estado','pasaje','nombre as pasajero',"nit","numero_factura","vendedor"));
		$select->joinLeft(array('v'=>'viaje'),"a.viaje=v.id_viaje and v.id_viaje='$viaje' ",array('fecha','hora','oficina'));
		$select->joinLeft(array('b1'=>'bus'),"b1.id_bus=v.bus",null);
		$select->joinLeft(array('pe'=>'persona'),"a.vendedor=pe.id_persona and a.viaje='$viaje'",array('nombres as vendedor'));
		$select->joinLeft(array('eq'=>'equipaje'),"eq.asiento=a.id_asiento ",array('id_equipaje', 'detalle as equipaje','nro_ticket','peso'));
		//$select->where('v.id_viaje=?',$viaje);
//                echo $select->__toString();
		$results = $db->fetchAll ( $select );
		foreach ($results as $item) {
//			echo $item['x']." ".$item['y']."<br>";
			$model[$item['y']][$item['x']]=$item;
		}
		return $model;
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
		 * @date creation 29/05/2009
		 */
	public function getRowsColsByBus($bus) {
		$db = $this->getAdapter ();
		$db->getFetchMode(Zend_Db::FETCH_OBJ);
		$select = $db->select ();
		$select->from ( array ('i' => 'item' ), array ('MAX(posicion_x) as columnas','MAX(posicion_y) as filas') );
		$select->joinInner(array ('ti' => 'tipo_item' ), 'i.tipo_item=ti.id_tipo_item',null);
		$select->joinInner(array('p'=>'piso'),'p.id_piso=i.piso',null);
		$select->joinInner(array('t'=>'tipo'),'t.id_tipo=p.tipo',null);
		$select->joinInner(array('m'=>'modelo'),'m.tipo=t.id_tipo',null);
		$select->joinInner(array('b'=>'bus'),'b.modelo=m.id_modelo',null);
		$select->where('b.id_bus=?',$bus);
		$results = $db->fetchRow ( $select );
		return $results;
//		SELECT MAX(i.posicion_x) AS columnas, MAX(i.posicion_y) AS filas 
//			FROM public.viaje v, public.bus b, public.modelo m, public.tipo t, public.piso p, public.item i, public.tipo_item ti 
//			WHERE (v.id_viaje = '$viaje') AND (v.bus = b.id_bus) AND (b.modelo = m.id_modelo) AND (m.tipo = t.id_tipo) AND (t.id_tipo = p.tipo) AND (p.id_piso = i.piso) AND (i.tipo_item = ti.id_tipo_item)
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
		 * @date creation 28/07/2009
		 */
	public function getDatosViaje($viaje,$interno) {
		$db = $this->getAdapter ();
//		$db->getFetchMode(Zend_Db::FETCH_OBJ);
		$select = $db->select ();
		$select->from ( array ('i' => 'item' ), array ('id_item','piso', 'posicion_x as x','posicion_y as y', 'tipo_item', 'numero') );
		$select->joinLeft(array('v'=>'viaje'),"v.bus=b.id_bus and v.id_viaje='$viaje' ",array('fecha','hora','oficina'));
		$select->joinInner(array('b'=>'bus'),"b.id_bus=v.id_modelo and b.numero='$interno'",array('numero as interno','id_bus'));
		$select->joinLeft(array('a'=>'asiento'),"a.item=i.id_item and a.viaje='$viaje'",array('id_asiento as idAsiento','estado','pasaje','nombre as pasajero',nit,numero_factura,fecha_venta,hora_venta,estado));
		$select->joinLeft(array('pe'=>'persona'),"pe.id_persona=a.vendedor",array('nombres as vendedor'));
		return $db->fetchAll ( $select );
	}
}
