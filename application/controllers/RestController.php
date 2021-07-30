<?php

class RestController extends Zend_Controller_Action {

//Rest API action

    public function restAction() {

        $this->_helper->Layout->disableLayout();
        $this->_helper->ViewRenderer->setNoRender();

        $params = $this->_request->getParams();
        unset($params['controller']);
        unset($params['action']);
        unset($params['module']);

        $server_prefix = 'App_Rest_';

        if (array_key_exists('context', $params)) {
            switch ($params['context']) {
                case 'viaje':
                    $params["fecha"]=date("Y-m-d");
                    $params["destino"]=  base64_decode($params["destino"]);
                    $params["origen"]=App_Util_Statics::$SISTEM_DEFAULTS["Id_Ciudad_Sistema"];
                    $server_class = $server_prefix . 'Viaje';
                    break;
                default:
                    throw new Exception('Invalid rest context');
            }
        } else
            $server_class = $server_prefix . 'ServerDefault';

//        print_r($params);
        $server = new App_Rest_Handler();
        $server->setClass($server_class);
        $server->returnResponse(true);

        $responseXML = $server->handle($params);

        if (!array_key_exists('format', $params)) {
            if ($this->_request->isXmlHttpRequest())
                $format = 'json';
            else
                $format = 'xml';
        } else
            $format = $params['format'];

        switch ($format) {
            case 'xml':
                $this->_response->setHeader('Content-Type', 'text/plain')->setBody($responseXML);
                break;
            case 'json':
                $this->_response->setHeader('Content-Type', 'application/json')->setBody(Zend_Json::fromXML($responseXML));
                break;
        }
    }

}

