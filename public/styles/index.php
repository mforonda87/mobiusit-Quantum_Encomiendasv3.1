<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<html>
    <head>

        <!-- You can load the jQuery library from the Google Content Network.
        Probably better than from your own server. -->
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>

        <!-- Load the CloudCarousel JavaScript file -->
        <script type="text/JavaScript" src="./js/cloud-carousel.1.0.5.min.js"></script>
        <script type="text/JavaScript" src="./js/jquery.mousewheel.js"></script>

        <script>
            $(document).ready(function(){
						   
                // This initialises carousels on the container elements specified, in this case, carousel1.
                $("#carousel1").CloudCarousel(		
                {			
                    xPos: 500,
                    yPos: 32,
                    buttonLeft: $("#left-but"),
                    buttonRight: $("#right-but"),
                    altBox: $("#alt-text"),
                    titleBox: $("#title-text"),
                    mouseWheel:true,
                    bringToFront:true
                }
            );
                
        $(".eventCarrousel").click(function(){
            $("#linkSystem").attr("href",$(this).attr("title"));
        });
            });
 
        </script>

    </head>
    <body>
        <!-- This is the container for the carousel. -->
        <div id="title-text"></div>
        <div>
            <div style="display: inline-block;width: 50px;vertical-align: 120px;">        <input id="left-but"  type="button" value="Left" /></div>    
            <div id = "carousel1" style="background: none repeat scroll 0 0 #FFFFFF;
                 height: 50%;
                 overflow: hidden;
                 position: relative;
                 width: 60%;                 
                 display: inline-block;">            
                <!-- All images with class of "cloudcarousel" will be turned into carousel items -->
                <!-- You can place links around these images -->
                <a href="#" class="eventCarrousel" title="./Quantum"><img class = "cloudcarousel" src="./img/IconoQuantum.png" alt="Sistema de administracion de tranposrte interdepartamental de pasajeros" title="Quantum - administracion" /></a>
                <a href="#" class="eventCarrousel" title="./VentasWEB"><img class = "cloudcarousel" src="./img/IconoQuantum.png" alt="Sistema de venta de pasajes interdepartamentales" title="Quantum - Ventas" /></a>
                <a href="#" class="eventCarrousel" title="./Encomiendas"><img class = "cloudcarousel" src="./img/encomiendasTitle.png" alt="Sistema de Recepcion, envio, arribo y entrega de encomiendas y carga" title="Quantum - Encomiendas" /></a>
                <a href="#" class="eventCarrousel" title="./Equipajes"><img class = "cloudcarousel" src="./img/encomiendasTitle.png" alt="Sistema de control de evio y recepcion de equipajes" title="Quantum - Equipajes" /></a>
            </div>

            <!-- Define left and right buttons. -->
            <div style="display: inline-block;width: 50px;vertical-align: 120px;">
                <input id="right-but" type="button" value="Right" />
            </div>
        </div>

        <!-- Define elements to accept the alt and title text from the images. -->
        <div id="alt-text"></div>
        <div>Ver el sistema <a href="#" id="linkSystem">aqui</a></div>
    </body>
</html>