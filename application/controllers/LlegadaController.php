<?php
/** Clase que controla el movimiento de llegada de la encomienda desde una ciudad
 * la recepcion exitosa, ademas controla la entrega de encomiendas a sus destinatarios
 * correspondientes.
 *
 */
class LlegadaController extends Zend_Controller_Action {

    public function init() {
        $this->view->headTitle('Entrada de Encomiendas');
    }

    public function indexAction() {
        $baseUrl = $this->_request->getBaseUrl();
        $this->view->form = new App_Form_LoginForm(array('action' => $baseUrl . '/index/submit/', 'method' => 'post'));
//        $this->render('login');
    }

     public function submitAction () {
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        $options= $this->getInvokeArg('bootstrap')->getOptions();
        $form = new App_Form_LoginForm();
        $request = $this->getRequest();
        if (!$form->isValid($request->getPost())) {
            if (count($form->getErrors('token')) > 0) {
                return $this->_forward('csrf-forbidden', 'error');
            }
            $this->view->form = $form;
            return $this->render('login');
        }
        $username = $form->getValue('username');
        $password = $form->getValue('password');
        $db = $this->getInvokeArg('bootstrap')->getResource('db');
        $authAdapter = new Zend_Auth_Adapter_DbTable(
                $db,
                'persona',
                'identificador',
                'contrasenia', "AND estado='Activo'");
        $authAdapter->setIdentity($username)->setCredential(md5($password));
        $result = $authAdapter->authenticate();
        Zend_Session::regenerateId();
        if (!$result->isValid()) {
            $this->_helper->flashMessenger->addMessage("Authentication error.");
            $this->_redirect($this->_request->getBaseUrl()+'/index/login');
        } else {
            $person = $authAdapter->getResultRowObject (null,"contrasenia");
            $session->person = $person;
            $session->username = $result->getIdentity();
            $sucModel = new App_Model_SucursalModel();
            $suc = $sucModel->getById($person->sucursal);
//            print_r($suc);
            $session->sucursalName = $suc->nombre;
//            $funcionModel = new App_Model_Funcion();
//            $funcionesPersona = $funcionModel->getFuncionesByPersonaVisible($person->id_persona);
//            $function=new App_Util_UtilsVenta();
//            $function->setFunciones($funcionesPersona);
//            $session->functions= $function;
//$this->render("index");
            $this->_redirect('/recepcion/index');
        }
    }

    /**
     * logout
     */
    public function logoutAction ()
    {
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        if (Zend_Session::sessionExists()) {
            $session = new Zend_Session_Namespace(App_Util_Statics::$SESSION);
            $session->unsetAll();

            $this->_redirect('/index/');
        }
    } 
}

