<?php

/**
 * Asiento
 *
 * @author Administrador
 * @version
 */
require_once 'Zend/Db/Table/Abstract.php';

class App_Model_Accion extends Zend_Db_Table_Abstract {

    /**
     * The default table name
     */
    protected $_name = 'accion';

    public function getByUser($userId) {
        $db = $this->getAdapter();
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        $select = $db->select();
        $select->distinct(true);
        $select->from(array('ac' => 'accion'), array('ac.id_accion as id', 'ac.nombre', 'ac.url_accion as accion', 'ac.titulo', 'ac.img'));
        $select->joinInner(array('sm' => 'sub_menu'), 'sm.id_submenu=ac.submenu', array("id_submenu", "nombre as submenu"));
        $select->joinInner(array('ar' => 'accion_rol'), 'ar.accion=ac.id_accion', null);
        $select->joinInner(array('pr' => 'persona_rol'), 'pr.rol=ar.rol', null);
        $select->where('pr.persona=?', $userId);
        $select->where("sm.estado='Activo'"); //AND ac.estado='Activo'
        $select->order(array("id_submenu"));

        $results = $db->fetchAll($select);

        $menu = array();
        $submenu = "";
        foreach ($results as $item) {
            if ($item->submenu != $submenu) {
                $submenu = $item->submenu;
                $menu[$submenu] = array("actions"=>array());
            }
            $accion = array("a" => $item->accion, "i" => $item->img, "t" => $item->titulo, "id" => $item->id);
            $menu[$submenu]["actions"][] = $accion;
        }
        return $menu;
    }

}
