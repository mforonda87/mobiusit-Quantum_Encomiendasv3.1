<?php

class App_Util_Statics {

    public static $MainFrameTitle = "Sistema de Encomiendas";
    public static $loginTitle = "Inicio de Session-Quantum";
    public static $labelButtonLogin = "Ingresar";
    public static $SESSION = "EncomiendaSystem";
    public static $SYSTEM = "ENCOMIENDA";
    public static $ESTADOS_ENCOMIENDA = array("Recibido" => 'RECIBIDO', 'Envio' => "ENVIADO", "Arribo" => "ARRIBO", "Entrega" => "ENTREGADO", "Traspaso" => "TRASPASO",'Anulado'=>'ANULADO');
    public static $detailMainFrame = "<div class='white'><h3>%s</h3>
                        <p>Sistema de control de recepcion , envio, arribo y entrega de encomiendas y equipajes </p><br/>
                        <p>Para ingresar al sistema por favor identifiquese</p><br/>
                        <p>debe ingresar su nombre de usuario y contrase&ntilde;a, presione el boton <b style='background-color: #3B5998;color: white;padding: 3px;'>INGRESAR</b></p>
                        </div>";
    public static $EstadosViaje = array('Activo' => 'Activo', 'Baja' => 'Baja',
        'Transito' => 'Transito');
    public static $EstadosDosificacion = array('Espera' => 'Espera', 'Activo' => 'Activo',
        'Inactivo' => 'Inactivo');
    public static $EstadosGenerales = array('Activo' => 'Activo', 'Inactivo' => 'Inactivo');
    public static $TiposFactura = array('Automatica' => 'Automatica', 'Manual' => 'Manual');
    // todo bus que se crea por defecto tiene este grupo.
    public static $groupDefault = array('id_grupo' => 'gru-100', 'nombre' => 'predeterminado');
    public static $DefaulFormDecorator = array('ViewHelper',
        array('ViewScript',
            array('viewScript' => 'formDecorator.phtml', 'placement' => false)));
    public static $highliterDecorator = array('ViewHelper',
        array('ViewScript',
            array('viewScript' => 'highliterformDecorator.phtml', 'placement' => false)));
    public static $DefaultButtonDecorators = array('ViewHelper',
        array(array('data' => 'HtmlTag'),
            array('tag' => 'td', 'class' => 'elementButtons', 'colspan' => '3')),
        array(array('row' => 'HtmlTag'), array('tag' => 'tr')));
    public static $DefaultSelectDecorators = array('ViewHelper',
        array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
        array(array('label' => 'HtmlTag'),
            array('tag' => 'td', 'placement' => 'prepend')),
        array(array('row' => 'HtmlTag'), array('tag' => 'tr')));
    public static $UPLOAD_DIRECTORY = "/var/www/Quantum/public/temp/";
    public static $root = '/Encomiendas/public';
    public static $configuracion = array('promedioViajes' => "mensual"); // mensual | dia
    public static $TIPO_RECIBOS = array('oficina' => 'oficina', 'viaje' => 'viaje',
        'propietario' => 'propietario');
    
    public static $SISTEM_DEFAULTS = array ('Ciudad_Sistema' => 'Cochabamba',
            'Id_Ciudad_Sistema' => '100');
    
    public static $nitEmpresa = "NIT_EMPRESA";
    public static $nombreEmpresa = "NOMBRE_EMPRESA";
    public static $tamañoImpresion = "TAMAÑO_IMPRESION";
    public static $CABECERA = "CABECERA";
    public static $lemaEmpresa = "LEMA";
    public static $impresionTamaño = "IMPRESION_POR_TAMAÑO";
    public static $TIPO_MENU_EGRESOS = "TIPO_MENU"; /// aplicado al sistema de escritorio
    public static $TITULO_FACTURA_1 = "TITULO_FACTURA_1";
    public static $TITULO_FACTURA_2 = "TITULO_FACTURA_2";
    
    
    
    public static $DB_LOCAL = "BASE_DE_DATOS_LOCAL"; /// aplicado al sistema de escritorio para el autocompletado de nombres de los pasajeros
    public static $DISPOSICION_ASIENTOS = "DISPOSICION_ASIENTOS"; /// aplicado al sistema de escritorio para cuando esta activo el autocompletado
    public static $AUTOCOMPLETADO = "HABILITAR_AUTOCOMPLETADO"; /// aplicado al sistema de escritorio autocompletado
    public static $TIPOEMPRESA = "TIPO_EMPRESA"; /// permite mostrar una interfaz de venta de asientos de acuerdo al tipo de empresa( NACIONAL | INTERNACIONAL ).
    public static $BUSQUEDABUS = "BUSQUEDA_BUS"; /// permite mostrar una interfaz de venta de asientos de acuerdo al tipo de empresa( NACIONAL | INTERNACIONAL ).
    public static $NUMERACION_GUIA = "NUMERACION_GUIA"; /// permite configurar si el numero de la factura sera la guia o se generara en base a la cantidad de encomiendas de una sucursal.
    
    public static $monedaLarga = "Bolivianos";
    public static $leyendaActividad = "TRANSPORTE DE PASAJEROS INTERDEPARTAMENTAL Y LARGA DISTANCIA";
    
    
    public static $servidores = array("Cochabamba" => "cbba", "Santa Cruz" => "scz", "La Paz" => "lpz");
//    public static $webService = array(
//        "route" => "/test/WebService/public",
//        "cochabamba" => array("ip" => "http://200.87.172.134"),
//        "buenos aires" => array("ip" => "http://200.87.172.134"),
//        "sucre" => array("ip" => "http://200.87.172.134"),
//        "oruro" => array("ip" => "http://200.87.172.134"),
//        "santa cruz" => array("ip" => "http://200.87.224.245"),
//        "beni" => array("ip" => "http://200.87.172.134"),
//        "tarija" => array("ip" => "http://200.87.172.134"),
//        "la paz" => array("ip" => "http://200.87.104.50"),
//        "potosi" => array("ip" => "http://200.87.172.134")
//    );

    public static $webService = array(
        "route" => "/test/WebService/public",
        "cochabamba" => array("ip" => "http://200.87.172.134"),
        "buenos aires" => array("ip" => "http://200.87.172.134"),
        "sucre" => array("ip" => "http://200.87.172.134"),
        "oruro" => array("ip" => "http://200.87.172.134"),
        "santa cruz" => array("ip" => "http://200.87.172.134"),
        "beni" => array("ip" => "http://200.87.172.134"),
        "tarija" => array("ip" => "http://200.87.172.134"),
        "la paz" => array("ip" => "http://200.87.172.134"),
        "potosi" => array("ip" => "http://200.87.172.134")
    );

    public static $docPdfSizeH = 800;
    public static $docPdfSizeW = 205;
    public static $printFontH2 = 'font-size: 15px;';
    public static $printFontH5 = 'font-size: 14px;';
    public static $printFontH3 = 'font-size: 15px;';
    public static $printFontCabeceraP = 'font-size: 12px; padding: 4px 3px; margin: 1;';
    public static $printFontDatosP = 'font-size: 15px;font-weight:900; padding: 8px 6px; margin: 2;';
    public static $printFontQuantumP = 'font-size: 10px; text-align: center; font-weight: 700;';
    public static $printFontTableHeadP = 'font-size: 12px; padding: 8px 8px; margin: 3;';
    public static $printFontTableBodyP = 'font-size: 13px; padding: 6px 4px; margin: 5;';
    public static $printFontTableFootP = 'font-size: 13px; font-weight: normal; padding: 2px 1px; margin: 0;';
    /**
     * Convierte el numero de bus  formato de interno es decir
     * si el bus es el 5 lo conviente en 005
     * si el bus es el 14 lo convierte en 014
     *
     * @param Number $number
     */
    public static function convertNumber($number) {
        $numberRet = 0;
        if (intval($number) < 10) {
            $numberRet = "00" . $number;
        }
        if (intval($number) >= 10 && intval($number) < 100) {
            $numberRet = "0" . $number;
        }
        if (intval($number >= 100)) {
            $numberRet = $number;
        }
        return $numberRet;
    }

    /**
     * Recupera el ultimo dia del mes segun el a�o al que pertnesca
     *
     * @param number $anho
     * @param number $mes
     * @return number	El ultimo dia del mes
     */
    public static function ultimoDia($anho, $mes) {
        if (((fmod($anho, 4) == 0) and (fmod($anho, 100) != 0)) or (fmod($anho, 400) == 0)) {
            $dias_febrero = 29;
        } else {
            $dias_febrero = 28;
        }
        switch ($mes) {
            case "01" :
                return 31;
                break;
            case "02" :
                return $dias_febrero;
                break;
            case "03" :
                return 31;
                break;
            case "04" :
                return 30;
                break;
            case "05" :
                return 31;
                break;
            case "06" :
                return 30;
                break;
            case "07" :
                return 31;
                break;
            case "08" :
                return 31;
                break;
            case "09" :
                return 30;
                break;
            case "10" :
                return 31;
                break;
            case "11" :
                return 30;
                break;
            case "12" :
                return 31;
                break;
        }
    }

    /**
     * ..... description
     *
     * @access public
     * @author Poloche
     * @author polochepu@gmail.com
     * @copyright Mobius IT S.R.L.
     * @copyright http://www.mobius.com.bo
     * @version beta rc1
     * @date creation 30/06/2009
     */
    public static function getIp() {
        if (getenv("HTTP_CLIENT_IP")) {
            $ip = getenv("HTTP_CLIENT_IP");
        } elseif (getenv("HTTP_X_FORWARDED_FOR")) {
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        } else {
            $ip = getenv("REMOTE_ADDR");
        }
        return $ip;
    }

    public static function getOs() {
        $os = "";

        if (!empty($_SERVER['HTTP_USER_AGENT'])) {
            $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
        }
        // 1. Platform
        if (strstr($HTTP_USER_AGENT, 'Win')) {
            $os = 'Win';
        } else if (strstr($HTTP_USER_AGENT, 'Mac')) {
            $os = 'Mac';
        } else if (strstr($HTTP_USER_AGENT, 'Linux')) {
            $os = 'Linux';
        } else if (strstr($HTTP_USER_AGENT, 'Unix')) {
            $os = 'Unix';
        } else {
            $os = 'Other';
        }
        return $os;
    }

    // Determina el salto de linea para varias plataformas de SO.
    public static function which_crlf() {
        if (!defined('USR_OS')) {
            if (!empty($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) {
                $HTTP_USER_AGENT = $HTTP_SERVER_VARS['HTTP_USER_AGENT'];
            }
            // 1. Platform
            if (strstr($HTTP_USER_AGENT, 'Win')) {
                define('USR_OS', 'Win');
            } else if (strstr($HTTP_USER_AGENT, 'Mac')) {
                define('USR_OS', 'Mac');
            } else if (strstr($HTTP_USER_AGENT, 'Linux')) {
                define('USR_OS', 'Linux');
            } else if (strstr($HTTP_USER_AGENT, 'Unix')) {
                define('USR_OS', 'Unix');
            } else {
                define('USR_OS', 'Other');
            }
            // 2. browser and version
            if (ereg('MSIE ([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
                define('USR_BROWSER_VER', $log_version[1]);
                define('USR_BROWSER_AGENT', 'IE');
            } else if (ereg('Opera(/| )([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
                define('USR_BROWSER_VER', $log_version[2]);
                define('USR_BROWSER_AGENT', 'OPERA');
            } else if (ereg('Mozilla/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
                define('USR_BROWSER_VER', $log_version[1]);
                define('USR_BROWSER_AGENT', 'MOZILLA');
            } else if (ereg('Konqueror/([0-9].[0-9]{1,2})', $HTTP_USER_AGENT, $log_version)) {
                define('USR_BROWSER_VER', $log_version[1]);
                define('USR_BROWSER_AGENT', 'KONQUEROR');
            } else {
                define('USR_BROWSER_VER', 0);
                define('USR_BROWSER_AGENT', 'OTHER');
            }
        }
        $the_crlf = "\n";

        // Win case
        if (USR_OS == 'Win') {
            $the_crlf = "\r\n";
        }
        // Mac case
        else if (USR_OS == 'Mac') {
            $the_crlf = "\r";
        }
        // Others
        else {
            $the_crlf = "\n";
        }

        return $the_crlf;
    }

    /*
     * @function num2letras ()
     * @abstract Dado un n?mero lo devuelve escrito.
     * @param $num number - N?mero a convertir.
     * @param $fem bool - Forma femenina (true) o no (false).
     * @param $dec bool - Con decimales (true) o no (false).
     * @result string - Devuelve el n?mero escrito en letra.
     */

    public static function num2letras($num, $fem = true, $dec = true,$moneda="Bs.") {
        $num = number_format($num, 2, '.', '');
        //		echo "is monto".$monto;
        list ( $num, $centavos ) = explode('.', $num);
        //		$entera = intval($monto);
        $centavos = "$centavos/100 " .$moneda;
        //if (strlen($num) > 14) die("El n?mero introducido es demasiado grande");
        $matuni [2] = "dos";
        $matuni [3] = "tres";
        $matuni [4] = "cuatro";
        $matuni [5] = "cinco";
        $matuni [6] = "seis";
        $matuni [7] = "siete";
        $matuni [8] = "ocho";
        $matuni [9] = "nueve";
        $matuni [10] = "diez";
        $matuni [11] = "once";
        $matuni [12] = "doce";
        $matuni [13] = "trece";
        $matuni [14] = "catorce";
        $matuni [15] = "quince";
        $matuni [16] = "dieciseis";
        $matuni [17] = "diecisiete";
        $matuni [18] = "dieciocho";
        $matuni [19] = "diecinueve";
        $matuni [20] = "veinte";
        $matunisub [2] = "dos";
        $matunisub [3] = "tres";
        $matunisub [4] = "cuatro";
        $matunisub [5] = "quin";
        $matunisub [6] = "seis";
        $matunisub [7] = "sete";
        $matunisub [8] = "ocho";
        $matunisub [9] = "nove";

        $matdec [2] = "veint";
        $matdec [3] = "treinta";
        $matdec [4] = "cuarenta";
        $matdec [5] = "cincuenta";
        $matdec [6] = "sesenta";
        $matdec [7] = "setenta";
        $matdec [8] = "ochenta";
        $matdec [9] = "noventa";
        $matsub [3] = 'mill';
        $matsub [5] = 'bill';
        $matsub [7] = 'mill';
        $matsub [9] = 'trill';
        $matsub [11] = 'mill';
        $matsub [13] = 'bill';
        $matsub [15] = 'mill';
        $matmil [4] = 'millones';
        $matmil [6] = 'billones';
        $matmil [7] = 'de billones';
        $matmil [8] = 'millones de billones';
        $matmil [10] = 'trillones';
        $matmil [11] = 'de trillones';
        $matmil [12] = 'millones de trillones';
        $matmil [13] = 'de trillones';
        $matmil [14] = 'billones de trillones';
        $matmil [15] = 'de billones de trillones';
        $matmil [16] = 'millones de billones de trillones';

        $num = trim((string) @$num);
        if ($num [0] == '-') {
            $neg = 'menos ';
            $num = substr($num, 1);
        } else
            $neg = '';
        while ($num [0] == '0')
            $num = substr($num, 1);
        if ($num [0] < '1' or $num [0] > 9)
            $num = '0' . $num;
        $zeros = true;
        $punt = false;
        $ent = '';
        $fra = '';
        for ($c = 0; $c < strlen($num); $c++) {
            $n = $num [$c];
            if (!(strpos(".,'''", $n) === false)) {
                if ($punt)
                    break;
                else {
                    $punt = true;
                    continue;
                }
            } elseif (!(strpos('0123456789', $n) === false)) {
                if ($punt) {
                    if ($n != '0')
                        $zeros = false;
                    $fra .= $n;
                } else
                    $ent .= $n;
            } else
                break;
        }
        $ent = '     ' . $ent;
        if ($dec and $fra and !$zeros) {
            $fin = ' coma';
            for ($n = 0; $n < strlen($fra); $n++) {
                if (($s = $fra [$n]) == '0')
                    $fin .= ' cero';
                elseif ($s == '1')
                    $fin .= $fem ? ' una' : ' un';
                else
                    $fin .= ' ' . $matuni [$s];
            }
        } else
            $fin = '';
        if ((int) $ent === 0)
            return 'Cero ' . $fin;
        $tex = '';
        $sub = 0;
        $mils = 0;
        $neutro = false;
        while (($num = substr($ent, - 3)) != '   ') {
            $ent = substr($ent, 0, - 3);
            if (++$sub < 3 and $fem) {
                $matuni [1] = 'un';
                $subcent = 'as';
            } else {
                $matuni [1] = $neutro ? 'un' : 'uno';
                $subcent = 'os';
            }
            $t = '';
            $n2 = substr($num, 1);
            if ($n2 == '00') {
                
            } elseif ($n2 < 21)
                $t = ' ' . $matuni [(int) $n2];
            elseif ($n2 < 30) {
                $n3 = $num [2];
                if ($n3 != 0)
                    $t = 'i' . $matuni [$n3];
                $n2 = $num [1];
                $t = ' ' . $matdec [$n2] . $t;
            } else {
                $n3 = $num [2];
                if ($n3 != 0)
                    $t = ' y ' . $matuni [$n3];
                $n2 = $num [1];
                $t = ' ' . $matdec [$n2] . $t;
            }
            $n = $num [0];
            if ($n == 1) {
                if ($num [1] == 0 && $num [2] == 0)
                    $t = ' cien' . $t;
                else
                    $t = ' ciento' . $t;
            } elseif ($n == 5) {
                $t = ' ' . $matunisub [$n] . 'ient' . $subcent . $t;
            } elseif ($n != 0) {
                $t = ' ' . $matunisub [$n] . 'cient' . $subcent . $t;
            }
            if ($sub == 1) {
                
            } elseif (!isset($matsub [$sub])) {
                if ($num == 1) {
                    $t = ' mil';
                } elseif ($num > 1) {
                    $t .= ' mil';
                }
            } elseif ($num == 1) {
                $t .= ' ' . $matsub [$sub] . '?n';
            } elseif ($num > 1) {
                $t .= ' ' . $matsub [$sub] . 'ones';
            }
            if ($num == '000')
                $mils++;
            elseif ($mils != 0) {
                if (isset($matmil [$sub]))
                    $t .= ' ' . $matmil [$sub];
                $mils = 0;
            }
            $neutro = true;
            $tex = $t . $tex;
        }
        $tex = $neg . substr($tex, 1) . $fin;
        return ucfirst($tex) . " " . $centavos;
    }

}

//echo Statics::getIp();
?>
