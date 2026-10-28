-- =====================================================================
--  ACTUALIZACIÓN 01 · Opiniones editables y CRUD admin
--  Úsalo SOLO si ya habías importado mictlan.sql ANTES de esta versión
--  (así no pierdes tus datos). Si vas a importar mictlan.sql de nuevo,
--  NO lo necesitas: ya incluye estos permisos.
--  Ejecutar en phpMyAdmin > pestaña SQL, con la base "mictlan" seleccionada.
-- =====================================================================
GRANT DELETE ON mictlan.resenas TO 'mictlan_app'@'localhost';
GRANT UPDATE (calificacion, comentario, fecha) ON mictlan.resenas TO 'mictlan_app'@'localhost';
FLUSH PRIVILEGES;
