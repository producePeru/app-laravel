-- ============================================================
-- Diagnostico UGSECO
-- Banco de preguntas y registro de respuestas (sin puntaje)
-- Ejecutar una sola vez sobre la base de datos del aplicativo.
-- ============================================================

CREATE TABLE IF NOT EXISTS `ugseco_diag_preguntas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `componente` varchar(100) NOT NULL,
  `numero` tinyint(3) unsigned NOT NULL,
  `pregunta` text NOT NULL,
  `orden` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ugseco_diag_preguntas_numero_unique` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ugseco_diag_opciones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pregunta_id` bigint(20) unsigned NOT NULL,
  `letra` char(1) NOT NULL,
  `texto` text NOT NULL,
  `orden` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ugseco_diag_opciones_pregunta_letra_unique` (`pregunta_id`, `letra`),
  CONSTRAINT `ugseco_diag_opciones_pregunta_foreign` FOREIGN KEY (`pregunta_id`)
    REFERENCES `ugseco_diag_preguntas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ugseco_diagnosticos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `empresario_id` bigint(20) unsigned DEFAULT NULL,
  `tipo_documento_id` bigint(20) unsigned DEFAULT NULL,
  `numero_documento` varchar(12) NOT NULL,
  `ruc` char(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ugseco_diagnosticos_empresario_id_index` (`empresario_id`),
  KEY `ugseco_diagnosticos_numero_documento_index` (`numero_documento`),
  KEY `ugseco_diagnosticos_ruc_index` (`ruc`),
  CONSTRAINT `ugseco_diagnosticos_empresario_foreign` FOREIGN KEY (`empresario_id`)
    REFERENCES `empresarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ugseco_diagnosticos_tipo_documento_foreign` FOREIGN KEY (`tipo_documento_id`)
    REFERENCES `typedocuments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ugseco_diag_respuestas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `diagnostico_id` bigint(20) unsigned NOT NULL,
  `pregunta_id` bigint(20) unsigned NOT NULL,
  `opcion_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ugseco_diag_respuestas_diagnostico_pregunta_unique` (`diagnostico_id`, `pregunta_id`),
  KEY `ugseco_diag_respuestas_pregunta_id_index` (`pregunta_id`),
  KEY `ugseco_diag_respuestas_opcion_id_index` (`opcion_id`),
  CONSTRAINT `ugseco_diag_respuestas_diagnostico_foreign` FOREIGN KEY (`diagnostico_id`)
    REFERENCES `ugseco_diagnosticos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ugseco_diag_respuestas_pregunta_foreign` FOREIGN KEY (`pregunta_id`)
    REFERENCES `ugseco_diag_preguntas` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `ugseco_diag_respuestas_opcion_foreign` FOREIGN KEY (`opcion_id`)
    REFERENCES `ugseco_diag_opciones` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Preguntas
INSERT IGNORE INTO `ugseco_diag_preguntas` (`id`, `componente`, `numero`, `pregunta`, `orden`, `created_at`, `updated_at`) VALUES
(1, 'Formalización', 1, '¿Cuál es la situación de formalización de su negocio?', 1, NOW(), NOW()),
(2, 'Gestión Empresarial', 2, '¿Cuál de estas situaciones describe mejor la gestión de su negocio?', 2, NOW(), NOW()),
(3, 'Digitalización', 3, '¿Cómo usa las herramientas digitales en su negocio?', 3, NOW(), NOW()),
(4, 'Financiamiento', 4, '¿Cuál de estas situaciones describe mejor el manejo financiero de su negocio?', 4, NOW(), NOW()),
(5, 'Desarrollo Productivo', 5, '¿Cómo organiza el trabajo o la producción en su negocio?', 5, NOW(), NOW());

-- Opciones
INSERT IGNORE INTO `ugseco_diag_opciones` (`id`, `pregunta_id`, `letra`, `texto`, `orden`, `created_at`, `updated_at`) VALUES
(1, 1, 'a', 'No cuenta con RUC', 1, NOW(), NOW()),
(2, 1, 'b', 'Tiene RUC, pero no cuenta con licencia municipal ni otros permisos', 2, NOW(), NOW()),
(3, 1, 'c', 'Está formalizado como persona natural con negocio (RUC), pero no se ha constituido como empresa', 3, NOW(), NOW()),
(4, 1, 'd', 'Está constituido como empresa (EIRL, SRL, SAC u otra) inscrita en SUNARP, con RUC y licencias', 4, NOW(), NOW()),
(5, 2, 'a', 'No conoce sus costos y fija sus precios empiricamente o copiando a la competencia', 1, NOW(), NOW()),
(6, 2, 'b', 'Lleva sus cuentas, pero tiene dudas sobre sus obligaciones tributarias (comprobantes de pago, IGV, régimen tributario, REMYPE)', 2, NOW(), NOW()),
(7, 2, 'c', 'Tiene sus cuentas y tributos en orden, pero necesita vender más y conocer mejor a sus clientes y su mercado', 3, NOW(), NOW()),
(8, 2, 'd', 'Lleva sus cuentas, cumple sus obligaciones tributarias y planifica sus ventas', 4, NOW(), NOW()),
(9, 3, 'a', 'No usa herramientas digitales en su negocio (solo efectivo)', 1, NOW(), NOW()),
(10, 3, 'b', 'Usa WhatsApp y cobra con Yape o Plin, pero no promociona su negocio en redes sociales', 2, NOW(), NOW()),
(11, 3, 'c', 'Promociona su negocio en redes sociales y cobra de forma digital, pero no emite comprobantes electrónicos', 3, NOW(), NOW()),
(12, 3, 'd', 'Promociona en redes, usa WhatsApp Business, cobra de forma digital y emite comprobantes electrónicos', 4, NOW(), NOW()),
(13, 4, 'a', 'Nunca ha accedido a un crédito formal, financia su negocio con recursos propios o préstamos de familiares o prestamistas', 1, NOW(), NOW()),
(14, 4, 'b', 'Ha accedido a crédito formal, pero con dificultades o sin calcular cuánto puede pagar de cuota', 2, NOW(), NOW()),
(15, 4, 'c', 'Accede a crédito formal y calcula su capacidad de pago, pero no compara condiciones (TCEA, plazos) ni conoce instrumentos como la carta fianza', 3, NOW(), NOW()),
(16, 4, 'd', 'Accede a crédito formal comparando condiciones entre entidades y sabe cuándo usar una carta fianza', 4, NOW(), NOW()),
(17, 5, 'a', 'Trabaja según se presente, sin procesos ordenados', 1, NOW(), NOW()),
(18, 5, 'b', 'Tiene una forma de trabajo ordenada, pero no aplica medidas de seguridad y salud (equipos de protección, señalización, botiquín)', 2, NOW(), NOW()),
(19, 5, 'c', 'Tiene procesos ordenados y medidas de seguridad, pero no mide sus resultados con indicadores (ventas, producción, mermas)', 3, NOW(), NOW()),
(20, 5, 'd', 'Tiene procesos ordenados, medidas de seguridad y mide sus resultados con indicadores para mejorar', 4, NOW(), NOW());



// NUEVO

-- ============================================================
-- Asistencia por día para actividades de varios días
-- Permite registrar la asistencia de cada mype por cada fecha
-- del evento (actividades_pnte.fechas).
-- Ejecutar una sola vez sobre la base de datos del aplicativo.
-- ============================================================

-- CREATE TABLE IF NOT EXISTS `empresario_actividad_asistencias` (
--   `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
--   `empresario_actividad_id` bigint(20) unsigned NOT NULL,
--   `slug` varchar(255) DEFAULT NULL,
--   `numero_dni` varchar(12) DEFAULT NULL,
--   `fecha` date NOT NULL,
--   `fecha_hora` datetime DEFAULT NULL,
--   `created_at` timestamp NULL DEFAULT NULL,
--   `updated_at` timestamp NULL DEFAULT NULL,
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `ea_asistencias_registro_fecha_unique` (`empresario_actividad_id`, `fecha`),
--   KEY `ea_asistencias_slug_index` (`slug`),
--   KEY `ea_asistencias_numero_dni_index` (`numero_dni`),
--   KEY `ea_asistencias_fecha_index` (`fecha`),
--   CONSTRAINT `ea_asistencias_registro_foreign` FOREIGN KEY (`empresario_actividad_id`)
--     REFERENCES `empresario_actividad` (`id`) ON DELETE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico: en eventos de un solo día la asistencia ya registrada se
-- traslada a la única fecha del evento para no perder la información.
INSERT IGNORE INTO `empresario_actividad_asistencias`
  (`empresario_actividad_id`, `slug`, `numero_dni`, `fecha`, `fecha_hora`, `created_at`, `updated_at`)
SELECT
  ea.`id`,
  ea.`slug`,
  ea.`numero_dni`,
  STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ap.`fechas`, '$[0]')), '%Y-%m-%d'),
  COALESCE(ea.`updated_at`, NOW()),
  NOW(),
  NOW()
FROM `empresario_actividad` ea
INNER JOIN `actividades_pnte` ap ON ap.`slug` = ea.`slug`
WHERE ea.`fecha_asistencia` IS NOT NULL
  AND JSON_LENGTH(ap.`fechas`) = 1
  AND STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(ap.`fechas`, '$[0]')), '%Y-%m-%d') IS NOT NULL;

