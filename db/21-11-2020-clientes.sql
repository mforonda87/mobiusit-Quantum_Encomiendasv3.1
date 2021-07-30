CREATE SEQUENCE seq_cliente    INCREMENT 1  MINVALUE 100    MAXVALUE 999999999  START 100    CACHE 1;

--  ********* triger Clietes   **********
CREATE TRIGGER tgcliente BEFORE INSERT
ON cliente FOR EACH ROW
EXECUTE PROCEDURE execute_cliente();

-- adding a new column type to manage clients normal and coorporate
alter table cliente add column tipo varchar default 'normal';

-- updating package type to allow 15 chars
ALTER TABLE encomienda ALTER COLUMN tipo type character varying(15);

-- adding new column to identifythe debt of a client
alter table cliente add column deuda INTEGER DEFAULT 0;

-- Migrating data from factura encomienda to be a clients
insert into cliente(id_cliente, nit, nombre)  
SELECT row_number() over(), fe.nit, fe.nombre
FROM factura_encomienda fe 
where id_factura in (
    SELECT max(id_factura) 
    FROM factura_encomienda
    where nit !='0' and nit !=''
    group BY nit
    order by nit
) 
 

CREATE SEQUENCE seq_cliente    INCREMENT 1  MINVALUE 100    MAXVALUE 999999999  START 100    CACHE 1;

--  ********* triger Clietes   **********
CREATE TRIGGER tgcliente BEFORE INSERT
ON cliente FOR EACH ROW
EXECUTE PROCEDURE execute_cliente();

alter table cobro_encomienda RENAME TO encomienda_coorporativa;
alter table encomienda_coorporativa RENAME column fecha to fecha_recepcion;
alter table encomienda_coorporativa add column fecha_pago TIMESTAMP;
alter table encomienda_coorporativa DROP column ciudad_origen;
alter table encomienda_coorporativa DROP column ciudad_destino; 
alter table encomienda_coorporativa add column client character varying(10);
ALTER TABLE encomienda_coorporativa ADD CONSTRAINT fk_cliente_encomienda_cooporativa FOREIGN KEY (client) 
REFERENCES cliente (id_cliente);


CREATE SEQUENCE seq_encomienda_coorporativa    INCREMENT 1  MINVALUE 100    MAXVALUE 999999999  START 100    CACHE 1;

--  ************** execute_coorporativa  encomienda ********************

CREATE OR REPLACE FUNCTION execute_coorporativa() RETURNS trigger AS $$

BEGIN

 IF ( TG_OP = 'INSERT' ) THEN  
  NEW.id_cobro_enc := 'cbe-' || nextval('seq_encomienda_coorporativa');  
 END IF;
 
 RETURN NEW;
END;
$$ LANGUAGE plpgsql;


--  ********* triger Clietes   **********
CREATE TRIGGER tgcoorporativa BEFORE INSERT
ON encomienda_coorporativa FOR EACH ROW
EXECUTE PROCEDURE execute_coorporativa();

SELECT "ec"."encomienda", "ec"."guia", "ec"."monto", "ec"."fecha_recepcion", "e"."detalle", "me"."fecha" AS "fecha_entrega" 
FROM "encomienda_coorporativa" AS "ec" 
INNER JOIN "encomienda" AS "e" ON e.id_encomienda=ec.encomienda 
LEFT JOIN "movimiento_encomienda" AS "me" ON me.encomienda=e.id_encomienda AND (me.movimiento='ENTREGADO')
WHERE (ec.fecha_pago is null) AND (ec.client='0') 