<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
        $this->view->headTitle('Encomiendas');
    }

    public function indexAction() {
        $infernum = $this->getRequest()->getParam("INFERNUM");
        $sheol = $this->getRequest()->getParam("SHEOL");
        $error = $this->getRequest()->getParam("error");

        if ($infernum != "") {
            // PARA VALIDAR EL USUARIO QUE LLEGA DESDE LA URL
            $code = $infernum;
            $extraParams = "INFERNUM/" . $infernum;
        } elseif ($sheol != "") {
            $sucursalModel = new App_Model_SucursalModel();
            $sucursal = $sucursalModel->getById(base64_decode($sheol));
            $code = $sheol;
            if ($infernum != "")
                $extraParams .= "/";
            $extraParams .= "SHEOL/" . $sheol;
        }

        $baseUrl = $this->_request->getBaseUrl();
        if ($error != "") {
            $this->view->message = $error;
        }
        $this->view->form = new App_Form_LoginForm(array('action' => $baseUrl . '/index/submit/' . $extraParams, 'method' => 'post'));
    }

    public function submitAction() {

        $sheol = $this->getRequest()->getParam('SHEOL');
        $infernum = $this->getRequest()->getParam('INFERNUM');

        $session = Zend_Registry::get(App_Util_Statics::$SESSION);

        $form = new App_Form_LoginForm();
        $request = $this->getRequest();
        $form->populate($request->getPost());
        if (!$form->isValid($request->getPost())) {
            $this->view->form = $form;
            $error = "El nombre de usuario y contrase&ntildea no pueden ser vacios";
            $this->_redirect("index/index/SHEOL/$sheol/error/" . $error);
        }
        $username = $form->getValue('username');
        $password = $form->getValue('password');
        $db = $this->getInvokeArg('bootstrap')->getResource('db');
        $authAdapter = new Zend_Auth_Adapter_DbTable(
                        $db,
                        'persona',
                        'identificador',
                        'contrasenia', "? AND estado='Activo'");

        $authAdapter->setIdentity($username)->setCredential(md5($password));
        $result = $authAdapter->authenticate();
        Zend_Session::regenerateId();
        if (!$result->isValid()) {
            $this->_helper->flashMessenger->addMessage("Authentication error.");
            $error = "El nombre de usuario o  contraseña son incorrectos";
            $this->_redirect("/index/index/SHEOL/$sheol/error/" . $error);
        } else {

            $person = $authAdapter->getResultRowObject(null, "contrasenia");
            $session->person = $person;
            $session->username = $result->getIdentity();

            $sucModel = new App_Model_SucursalModel();
            if ($sheol != "") {
                $suc = $sucModel->getById(base64_decode($sheol));
                $session->person->sucursal = $suc->id_sucursal;
            } else {
                $suc = $sucModel->getById($person->sucursal);
            }
            if ($suc && $suc != "") {
                
                $session->sucursalName = $suc->nombre;
                $session->sucursalAbr = $suc->abreviacion;
                $session->ciudadName = $suc->ciudad;
                $session->ciudadID = $suc->id_ciudad;
                $configModel = new App_Model_ConfiguracionSistema();
                $session->configSYS = $configModel->getAll();
                
//                $this->render("index");
                $this->_redirect('/recepcion/index');
            } else {
                $error = "La sucursal a la que se esta tradando de acceder no es valida <br/> por favor counsulte con el administrador del sistema";
                if (Zend_Session::sessionExists()) {
                    $session = new Zend_Session_Namespace(App_Util_Statics::$SESSION);
                    $session->unsetAll();
                }
                $this->_redirect("/index/index/SHEOL/$sheol/error/" . $error);
            }
        }
    }

    /**
     * logout
     */
    public function logoutAction() {
        $session = Zend_Registry::get(App_Util_Statics::$SESSION);
        if (Zend_Session::sessionExists()) {
            $session = new Zend_Session_Namespace(App_Util_Statics::$SESSION);
            $session->unsetAll();

            $this->_redirect('/index/');
        }
    }

}

