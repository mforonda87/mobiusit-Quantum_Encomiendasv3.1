--/********************   PAOLO 12/04/2014 ******************/
--/********************   MODIFICACION DE TABLA ITEM ENCOMIENDA, AÑADIMOS UNA COLUMNA LA TABLA    ******************/
ALTER TABLE configuracion_sistema ADD COLUMN descripcion CHARACTER VARYING(255);
ALTER TABLE configuracion_sistema ADD COLUMN valores CHARACTER VARYING(255);

ALTER TABLE sucursal ADD COLUMN municipio CHARACTER VARYING(255);
ALTER TABLE sucursal ADD COLUMN capital CHARACTER VARYING(255);

ALTER TABLE item_encomienda ADD COLUMN volumen double precision;
ALTER TABLE item_encomienda ALTER COLUMN volumen SET DEFAULT -1;


--/********************   PAOLO 12/04/2014 ******************/
--/********************   MODIFICACION DE TABLA ENCOMIENDA, AÑADIMOS UNA COLUMNA detalle de entrega    ******************/

ALTER TABLE encomienda ADD COLUMN detalle_entrega TEXT;
ALTER TABLE encomienda ALTER COLUMN detalle_entrega SET DEFAULT 'Normal';

--/********************   PAOLO 19/04/2014 ******************/
--/********************   MODIFICACION DE TABLA ENCOMIENDA, AÑADIMOS UNA COLUMNA observacion    ******************/

ALTER TABLE encomienda ADD COLUMN observacion TEXT;
ALTER TABLE encomienda ALTER COLUMN observacion SET DEFAULT 'Sin valor declarado';

ALTER TABLE encomienda ADD COLUMN valor_declarado DOUBLE PRECISION;


--/********************   PAOLO 19/04/2014 ******************/
--/********************   Registro de configuracion del sistema    ******************/
--/********************   Habilita el cobro de encomiendas auto ajustado por referencia de kilo valor    ******************/
insert into configuracion_sistema("key","value","descripcion","valores") values('AUTO_AJUSTE_ENCOMIENDA'       ,'SI'  ,'Permite ajustar el precio de una encomienda en base al peso y viceversa','{"1":"SI","2":"NO"}'); 
insert into configuracion_sistema("key","value","descripcion","valores") values('NUMERACION_GUIA'       ,'INDEPENDIENTE'  ,'Permite ajustar si el numero de guia sera el numero de la factura o se contabilizara independiente de la factura','{"1":"INDEPENDIENTE","2":"FACTURA"}'); 

--/********************   PAOLO 26/04/2014 ******************/
--/********************   Alteramos el tipo de dato que almacena la columna detalle de la encomienda y agrandamos el detalle del item de la encomienda    ******************/

ALTER TABLE encomienda ALTER COLUMN detalle TYPE TEXT;
ALTER TABLE item_encomienda ALTER COLUMN detalle TYPE CHARACTER VARYING(100);
--/********************   PAOLO 04/05/2014 ******************/
--/********************   creamos una nueva columna en la tabla sucursal abreviacion    ******************/

ALTER TABLE sucursal ADD COLUMN abreviacion CHARACTER VARYING(10);


--/********************   PAOLO 13/12/2014 ******************/
--/********************   agregamos campo para determinar si es por pagar entregada    ******************/

ALTER TABLE encomienda ADD COLUMN is_porpagar_entregada BOOLEAN not null default FALSE;

--/********************   PAOLO 19/02/2019 ******************/
--/********************   agregamos campo que cada sucursal pueda agregar una leyenda de ley    ******************/

ALTER TABLE sucursal ADD COLUMN leyenda CHARACTER VARYING(300) default 'LEY 453: \"Tienes derecho a recibir informacion sobre las caracteristicas y contenidos de los servicios que utilices\"';

ALTER TABLE ciudad ADD COLUMN nombre2 CHARACTER VARYING(100);