<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of App_Rest_Server
 *
 * @author poloche
 */
class App_Rest_Response {

    protected $_xml = null;
    protected $_status = null;

    public function __construct($pStatus = true, array $pResponse = array()) {
        $this->_xml = simplexml_load_string('<?xml version="1.0" encoding="utf-8"?><response></response>');
        $this->appendStatus($pStatus);

        if (count($pResponse) > 0)
            $this->appendResponse($this->_xml, $pResponse);
    }

    public function appendStatus($pStatus = true) {
        if (is_null($this->_status)) {
            if ($pStatus === true) {
                $this->_xml->addChild('status', 'success');
            } else if ($pStatus === false) {
                $this->_xml->addChild('status', 'fail');
            } else {
                throw new Exception('Invalid response status');
            }
        } else {
            throw Exception('Response already has status');
        }
    }

    public function appendResponse($pXml, array $pResponse) {
        foreach ($pResponse as $key => $val) {
            if (is_array($val)) {
                $child = $pXml->addChild($key);
                $this->appendResponse($child, $val);
            } else {
                $pXml->addChild($key, $val);
            }
        }
    }

    public function __get($pAtt) {
        switch ($pAtt) {
            case 'xml':
                return $this->_xml;
        }
    }

    public static function Generate(array $pResponse, $pStatus = true) {
        $response = new App_Rest_Response($pStatus, $pResponse);
        return $response->xml;
    }

}

?>
