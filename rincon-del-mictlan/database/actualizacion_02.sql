-- =====================================================================
--  ACTUALIZACIÓN 02 · Carrito, checkout y pedidos
--  Úsalo SOLO si ya habías importado mictlan.sql ANTES de esta versión
--  (conserva tus datos). Si vas a importar mictlan.sql de nuevo, NO lo
--  necesitas: ya incluye estas columnas.
--  Ejecútalo UNA sola vez: phpMyAdmin > base "mictlan" > pestaña SQL.
--  (Si lo corres dos veces dará "Duplicate column name": es inofensivo.)
-- =====================================================================
USE mictlan;

ALTER TABLE pedidos
  ADD COLUMN referencia_pago VARCHAR(30)  NULL AFTER metodo_pago,
  ADD COLUMN ultimos4        CHAR(4)      NULL AFTER referencia_pago,
  ADD COLUMN fecha_pago      DATETIME     NULL AFTER ultimos4,
  ADD COLUMN envio_nombre    VARCHAR(100) NULL AFTER fecha_pago,
  ADD COLUMN envio_direccion VARCHAR(200) NULL AFTER envio_nombre,
  ADD COLUMN envio_ciudad    VARCHAR(80)  NULL AFTER envio_direccion,
  ADD COLUMN envio_cp        VARCHAR(10)  NULL AFTER envio_ciudad,
  ADD COLUMN envio_telefono  VARCHAR(20)  NULL AFTER envio_cp;
