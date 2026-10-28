-- =====================================================================
--  EL RINCÓN DEL MICTLÁN · Base de datos
--  Compatible con MySQL 8.x / 9.x (y MariaDB 10.4+ de XAMPP)
--  Ejecutar COMPLETO con una cuenta administradora de MySQL (root).
--  ATENCIÓN: borra y recrea la base "mictlan" (útil para reinstalar).
-- =====================================================================
DROP DATABASE IF EXISTS mictlan;
CREATE DATABASE mictlan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mictlan;

-- ---------------------------------------------------------------------
-- 1. ROLES  (1 = admin, 2 = cliente)
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id_rol      TINYINT UNSIGNED PRIMARY KEY,
  nombre      VARCHAR(20)  NOT NULL UNIQUE,
  descripcion VARCHAR(120) NOT NULL
) ENGINE=InnoDB;

INSERT INTO roles VALUES
 (1,'admin',  'Gestiona inventario, usuarios y asigna roles'),
 (2,'cliente','Navega, compra, guarda favoritos y reseña');

-- ---------------------------------------------------------------------
-- 2. USUARIOS (clientes y administradores viven aquí; los distingue id_rol)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id_usuario       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre           VARCHAR(60)  NOT NULL,
  apellidos        VARCHAR(80)  NOT NULL,
  email            VARCHAR(120) NOT NULL UNIQUE,
  password_hash    VARCHAR(255) NOT NULL,               -- RNF01: nunca texto plano
  id_rol           TINYINT UNSIGNED NOT NULL DEFAULT 2, -- por defecto: cliente
  activo           TINYINT(1)   NOT NULL DEFAULT 1,
  rol_asignado_por INT UNSIGNED NULL,                   -- qué admin definió el rol
  fecha_rol        DATETIME     NULL,
  fecha_registro   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_acceso    DATETIME     NULL,
  CONSTRAINT fk_usr_rol  FOREIGN KEY (id_rol)           REFERENCES roles(id_rol),
  CONSTRAINT fk_usr_asig FOREIGN KEY (rol_asignado_por) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- Bitácora: quién cambió el rol de quién y cuándo
CREATE TABLE bitacora_roles (
  id_bitacora  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_admin     INT UNSIGNED NOT NULL,
  id_usuario   INT UNSIGNED NOT NULL,
  rol_anterior TINYINT UNSIGNED NOT NULL,
  rol_nuevo    TINYINT UNSIGNED NOT NULL,
  fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_admin)   REFERENCES usuarios(id_usuario),
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 3. CATÁLOGO
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id_categoria SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre       VARCHAR(60) NOT NULL,
  slug         VARCHAR(60) NOT NULL UNIQUE,  -- se usa en los anchor links (#cat-slug)
  orden        TINYINT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE productos (
  id_producto  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_categoria SMALLINT UNSIGNED NOT NULL,
  nombre       VARCHAR(100) NOT NULL,
  descripcion  VARCHAR(255) NULL,
  precio       DECIMAL(10,2) NOT NULL CHECK (precio >= 0),
  stock        INT UNSIGNED NOT NULL DEFAULT 0,   -- RF03: 0 = "Agotado"
  talla        ENUM('S','M','L','XL') NULL,       -- solo calaveritas
  imagen       VARCHAR(150) NULL,                 -- archivo en assets/img/productos/
  activo       TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (id_categoria) REFERENCES categorias(id_categoria)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. WISHLIST, PEDIDOS Y RESEÑAS
-- ---------------------------------------------------------------------
CREATE TABLE wishlist (
  id_usuario  INT UNSIGNED NOT NULL,
  id_producto INT UNSIGNED NOT NULL,
  fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_usuario, id_producto),
  FOREIGN KEY (id_usuario)  REFERENCES usuarios(id_usuario)   ON DELETE CASCADE,
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE pedidos (
  id_pedido   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_usuario  INT UNSIGNED NOT NULL,
  total       DECIMAL(10,2) NOT NULL,
  estado      ENUM('pendiente','pagado','enviado','cancelado') NOT NULL DEFAULT 'pendiente',
  metodo_pago ENUM('tarjeta','paypal','google_play') NULL,
  fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB;

CREATE TABLE detalle_pedido (
  id_detalle      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pedido       INT UNSIGNED NOT NULL,
  id_producto     INT UNSIGNED NOT NULL,
  cantidad        INT UNSIGNED NOT NULL CHECK (cantidad > 0),
  precio_unitario DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (id_pedido)   REFERENCES pedidos(id_pedido) ON DELETE CASCADE,
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto)
) ENGINE=InnoDB;

CREATE TABLE resenas (
  id_resena    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_producto  INT UNSIGNED NOT NULL,
  id_usuario   INT UNSIGNED NOT NULL,
  calificacion TINYINT UNSIGNED NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
  comentario   VARCHAR(500) NULL,
  fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_una_resena (id_producto, id_usuario),
  FOREIGN KEY (id_producto) REFERENCES productos(id_producto) ON DELETE CASCADE,
  FOREIGN KEY (id_usuario)  REFERENCES usuarios(id_usuario)   ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. DATOS DE EJEMPLO
--    Contraseña de las 3 cuentas demo: Mictlan2026!   (¡cámbiala!)
-- ---------------------------------------------------------------------
INSERT INTO categorias (id_categoria,nombre,slug,orden) VALUES
 (1,'Calaveritas','calaveritas',1),      (2,'Marcos de fotos','marcos',2),
 (3,'Flores','flores',3),                (4,'Figuras de cerámica','figuras-ceramica',4),
 (5,'Velas','velas',5),                  (6,'Estantes','estantes',6),
 (7,'Papel picado','papel-picado',7),    (8,'Manteles','manteles',8),
 (9,'Copal e inciensos','copal',9);

INSERT INTO productos (id_categoria,nombre,descripcion,precio,stock,talla,imagen) VALUES
 (1,'Calaverita Florida','Cerámica pintada a mano',180,12,'M','calaverita-florida.png'),
 (1,'Calaverita con Sombrero','Cerámica pintada a mano',195,8,'M','calaverita-sombrero.png'),
 (1,'Calaverita Cempasúchil','Corona de cempasúchil',210,5,'L','calaverita-cempasuchil.png'),
 (1,'Calaverita Mini','Ideal para repisas',99,20,'S','calaverita-mini.png'),
 (1,'Calaverita Monumental','Pieza central de altar',349,0,'XL','calaverita-monumental.png'),
 (2,'Marco 5x7 para fotografía','Marco clásico para difuntos',120,15,NULL,'marco-5x7.png'),
 (2,'Marco 8x10 con flores','Decorado con cempasúchil',180,9,NULL,'marco-8x10.png'),
 (3,'Ramo de cempasúchil artificial','Ramo de 12 flores',140,25,NULL,'flores-cempasuchil.png'),
 (3,'Flores de terciopelo','Ramo rojo y morado',160,10,NULL,'flores-terciopelo.png'),
 (4,'Plato de mole con pollo','Figura de cerámica',130,10,NULL,'ceramica-mole.png'),
 (4,'Plato de arroz','Figura de cerámica',110,10,NULL,'ceramica-arroz.png'),
 (4,'Plato de frijoles','Figura de cerámica',110,10,NULL,'ceramica-frijoles.png'),
 (4,'Plato de pozole','Figura de cerámica',130,7,NULL,'ceramica-pozole.png'),
 (4,'Botella de cerveza','Bebida tradicional en cerámica',85,14,NULL,'ceramica-cerveza.png'),
 (4,'Botella de tequila','Bebida tradicional en cerámica',95,14,NULL,'ceramica-tequila.png'),
 (5,'Vela tradicional blanca','Vela clásica de altar',35,50,NULL,'vela-blanca.png'),
 (5,'Vela pan de muerto','Vela de figura',75,18,NULL,'vela-pan.png'),
 (5,'Vela taza de café','Vela de figura',70,18,NULL,'vela-cafe.png'),
 (5,'Vela olla de barro','Vela de figura',85,12,NULL,'vela-olla.png'),
 (5,'Vela Xoloitzcuintle','Vela de figura',95,9,NULL,'vela-xolo.png'),
 (5,'Vela de cempasúchil','Vela de figura',80,16,NULL,'vela-cempasuchil.png'),
 (6,'Estante de madera 3 niveles','Para armar tu altar',649,4,NULL,'estante-3.png'),
 (6,'Estante escalonado 2 niveles','Madera natural',449,6,NULL,'estante-2.png'),
 (7,'Papel picado artesanal (docena)','Colores surtidos',90,40,NULL,'papel-docena.png'),
 (7,'Papel picado de calaveras','Diseño especial',110,22,NULL,'papel-calaveras.png'),
 (8,'Mantel bordado tradicional','Para cubrir el altar',280,8,NULL,'mantel-bordado.png'),
 (9,'Copal blanco en resina','Aroma tradicional',65,30,NULL,'copal-resina.png'),
 (9,'Incienso de sahumerio','Caja con 20 barras',55,30,NULL,'incienso.png');

INSERT INTO usuarios (id_usuario,nombre,apellidos,email,password_hash,id_rol,rol_asignado_por,fecha_rol) VALUES
 (1,'Admin','Mictlán','admin@mictlan.test','$2b$10$CPzG6Kvhth2Mabbo09NHbuAY3mMZr0.j82Ki.Qmel9jbLM/t3rLQG',1,1,NOW()),
 (2,'Cliente','Demo Uno','cliente1@mictlan.test','$2b$10$CPzG6Kvhth2Mabbo09NHbuAY3mMZr0.j82Ki.Qmel9jbLM/t3rLQG',2,NULL,NULL),
 (3,'Cliente','Demo Dos','cliente2@mictlan.test','$2b$10$CPzG6Kvhth2Mabbo09NHbuAY3mMZr0.j82Ki.Qmel9jbLM/t3rLQG',2,NULL,NULL);

-- Compras demo (para que existan reseñas válidas según RN03)
INSERT INTO pedidos (id_pedido,id_usuario,total,estado,metodo_pago) VALUES
 (1,2,585,'pagado','tarjeta'), (2,3,585,'pagado','paypal');
INSERT INTO detalle_pedido (id_pedido,id_producto,cantidad,precio_unitario) VALUES
 (1,1,1,180),(1,2,1,195),(1,3,1,210), (2,1,1,180),(2,2,1,195),(2,3,1,210);
INSERT INTO resenas (id_producto,id_usuario,calificacion,comentario) VALUES
 (1,2,4,'Muy bonita'),(1,3,4,'Bien pintada'),
 (2,2,3,'Algo pequeña'),(2,3,3,NULL),
 (3,2,5,'Mi favorita'),(3,3,3,NULL);

-- ---------------------------------------------------------------------
-- 6. VISTA DEL CATÁLOGO (producto + categoría + promedio de estrellas)
-- ---------------------------------------------------------------------
CREATE VIEW v_catalogo AS
SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.talla, p.imagen,
       c.nombre AS categoria, c.slug, c.orden,
       COALESCE(ROUND(AVG(r.calificacion),1),0) AS promedio,
       COUNT(r.id_resena) AS total_resenas
FROM productos p
JOIN categorias c ON c.id_categoria = p.id_categoria
LEFT JOIN resenas r ON r.id_producto = p.id_producto
WHERE p.activo = 1
GROUP BY p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, p.talla, p.imagen,
         c.nombre, c.slug, c.orden;

-- =====================================================================
-- 7. REGLA ESTRICTA: SOLO UN ADMINISTRADOR DEFINE O CAMBIA ROLES
--    Se crean DESPUÉS de los datos de ejemplo para no estorbar la carga.
--    (Si importas con phpMyAdmin, respeta las líneas DELIMITER.)
-- =====================================================================
DELIMITER //

-- (a) Toda cuenta nueva DEBE nacer como "cliente". Nadie puede insertarse como admin.
CREATE TRIGGER trg_usuarios_bi BEFORE INSERT ON usuarios FOR EACH ROW
BEGIN
  IF NEW.id_rol <> (SELECT id_rol FROM roles WHERE nombre = 'cliente') THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Regla de roles: toda cuenta nueva nace como cliente';
  END IF;
END//

-- (b) Cambiar id_rol solo es posible si @rol_autorizado_por es un admin ACTIVO.
--     Esa variable solo la fija el procedimiento sp_cambiar_rol.
CREATE TRIGGER trg_usuarios_bu BEFORE UPDATE ON usuarios FOR EACH ROW
BEGIN
  IF NEW.id_rol <> OLD.id_rol AND NOT EXISTS (
       SELECT 1 FROM usuarios u JOIN roles r ON r.id_rol = u.id_rol
       WHERE u.id_usuario = @rol_autorizado_por AND r.nombre = 'admin' AND u.activo = 1) THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Regla de roles: solo un administrador puede definir o cambiar roles';
  END IF;
END//

-- (c) Única puerta de entrada para asignar/cambiar roles.
CREATE PROCEDURE sp_cambiar_rol(IN p_admin INT UNSIGNED, IN p_usuario INT UNSIGNED, IN p_rol VARCHAR(20))
SQL SECURITY DEFINER
BEGIN
  DECLARE v_nuevo   TINYINT UNSIGNED;
  DECLARE v_actual  TINYINT UNSIGNED;
  DECLARE v_admin   TINYINT UNSIGNED;
  DECLARE v_activos INT DEFAULT 0;
  DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN SET @rol_autorizado_por = NULL; RESIGNAL; END;

  SELECT id_rol INTO v_admin FROM roles WHERE nombre = 'admin';

  IF NOT EXISTS (SELECT 1 FROM usuarios WHERE id_usuario = p_admin AND id_rol = v_admin AND activo = 1) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Acceso denegado: solo un administrador activo puede cambiar roles';
  END IF;

  SELECT id_rol INTO v_nuevo  FROM roles    WHERE nombre = p_rol;
  SELECT id_rol INTO v_actual FROM usuarios WHERE id_usuario = p_usuario;
  IF v_nuevo  IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El rol indicado no existe'; END IF;
  IF v_actual IS NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El usuario indicado no existe'; END IF;

  IF v_actual = v_admin AND v_nuevo <> v_admin THEN
    SELECT COUNT(*) INTO v_activos FROM usuarios WHERE id_rol = v_admin AND activo = 1;
    IF v_activos <= 1 THEN
      SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No puedes dejar el sistema sin administradores';
    END IF;
  END IF;

  IF v_actual <> v_nuevo THEN
    SET @rol_autorizado_por = p_admin;
    UPDATE usuarios SET id_rol = v_nuevo, rol_asignado_por = p_admin, fecha_rol = NOW()
     WHERE id_usuario = p_usuario;
    SET @rol_autorizado_por = NULL;
    INSERT INTO bitacora_roles (id_admin, id_usuario, rol_anterior, rol_nuevo)
    VALUES (p_admin, p_usuario, v_actual, v_nuevo);
  END IF;
END//

-- (d) RN03: solo reseña quien tiene una compra pagada/enviada de ese producto.
CREATE TRIGGER trg_resenas_bi BEFORE INSERT ON resenas FOR EACH ROW
BEGIN
  IF NOT EXISTS (SELECT 1 FROM pedidos p JOIN detalle_pedido d ON d.id_pedido = p.id_pedido
                 WHERE p.id_usuario = NEW.id_usuario AND d.id_producto = NEW.id_producto
                   AND p.estado IN ('pagado','enviado')) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo puedes reseñar productos que ya compraste';
  END IF;
END//

DELIMITER ;

-- ---------------------------------------------------------------------
-- 8. USUARIO DE MYSQL PARA LA APLICACIÓN (mínimos privilegios)
--    NO tiene UPDATE sobre usuarios.id_rol: solo puede cambiarlo
--    ejecutando sp_cambiar_rol.
-- ---------------------------------------------------------------------
CREATE USER IF NOT EXISTS 'mictlan_app'@'localhost' IDENTIFIED BY 'CambiaEstaClave_2026!';
GRANT SELECT ON mictlan.* TO 'mictlan_app'@'localhost';
GRANT INSERT, UPDATE, DELETE ON mictlan.productos      TO 'mictlan_app'@'localhost';
GRANT INSERT, UPDATE, DELETE ON mictlan.categorias     TO 'mictlan_app'@'localhost';
GRANT INSERT, DELETE         ON mictlan.wishlist       TO 'mictlan_app'@'localhost';
GRANT INSERT, UPDATE         ON mictlan.pedidos        TO 'mictlan_app'@'localhost';
GRANT INSERT                 ON mictlan.detalle_pedido TO 'mictlan_app'@'localhost';
GRANT INSERT, DELETE         ON mictlan.resenas        TO 'mictlan_app'@'localhost';
GRANT UPDATE (calificacion, comentario, fecha)
                             ON mictlan.resenas        TO 'mictlan_app'@'localhost';
GRANT INSERT                 ON mictlan.usuarios       TO 'mictlan_app'@'localhost';
GRANT UPDATE (nombre, apellidos, email, password_hash, activo, ultimo_acceso)
                             ON mictlan.usuarios       TO 'mictlan_app'@'localhost';
GRANT EXECUTE ON PROCEDURE mictlan.sp_cambiar_rol TO 'mictlan_app'@'localhost';
FLUSH PRIVILEGES;
