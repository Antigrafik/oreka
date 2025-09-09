USE oreka;
SET NOCOUNT ON;

------------------------------------------------------------
-- 1) USERS
------------------------------------------------------------
INSERT INTO dbo.[user] (name, language, roles) VALUES
 (N'ezequiel_33_1@hotmail.com', 'es', 'admin'),
 (N'Ana',      'es', ''),
 (N'Pedro',    'es', '');

DECLARE @u_ezequiel INT = (SELECT id FROM dbo.[user] WHERE name = N'ezequiel_33_1@hotmail.com');
DECLARE @u_ana      INT = (SELECT id FROM dbo.[user] WHERE name = N'Ana');
DECLARE @u_pedro    INT = (SELECT id FROM dbo.[user] WHERE name = N'Pedro');

------------------------------------------------------------
-- 2) ADMIN_LEGAL
------------------------------------------------------------
DECLARE @legal1 INT, @legal2 INT;
INSERT INTO dbo.admin_legal (status) VALUES ('activo');   SET @legal1 = SCOPE_IDENTITY();
INSERT INTO dbo.admin_legal (status) VALUES ('inactivo'); SET @legal2 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 3) BANNERS
------------------------------------------------------------
DECLARE @banner1 INT, @banner2 INT;
INSERT INTO dbo.banner (prize, date_start, date_finish)
VALUES (N'Sorteo de 50 puntos', '2025-08-01 09:00:00', '2025-09-01 23:59:59');
SET @banner1 = SCOPE_IDENTITY();

INSERT INTO dbo.banner (prize, date_start, date_finish)
VALUES (N'Vale de tienda', '2025-09-10 00:00:00', '2025-10-10 23:59:59');
SET @banner2 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 4) CATEGORIES
------------------------------------------------------------
DECLARE @cat_aula INT, @cat_bienestar INT, @cat_product INT, @cat_nutri INT;

INSERT INTO dbo.category (entity_type, status) VALUES (N'learn', 'publicado'); SET @cat_aula      = SCOPE_IDENTITY();
INSERT INTO dbo.category (entity_type, status) VALUES (N'learn', 'publicado'); SET @cat_bienestar = SCOPE_IDENTITY();
INSERT INTO dbo.category (entity_type, status) VALUES (N'learn', 'publicado'); SET @cat_product   = SCOPE_IDENTITY();
INSERT INTO dbo.category (entity_type, status) VALUES (N'learn', 'publicado'); SET @cat_nutri     = SCOPE_IDENTITY();

-- Traducciones ES/EU
INSERT INTO dbo.category_translation (id_category, lang, name, description, slug) VALUES
 (@cat_aula,      'es', N'Aula',       N'Sección de aprendizaje',      N'aula'),
 (@cat_aula,      'eu', N'Gela',       N'Ikaskuntza atala',            N'gela'),

 (@cat_bienestar, 'es', N'Bienestar',  N'Contenido de bienestar',      N'bienestar'),
 (@cat_bienestar, 'eu', N'Ongizatea',  N'Ongizate edukia',             N'ongizatea'),

 (@cat_product,   'es', N'Productividad', N'Hábitos y foco',           N'productividad'),
 (@cat_product,   'eu', N'Produktibitatea', N'Ohiturak eta fokua',     N'produktibitatea'),

 (@cat_nutri,     'es', N'Nutrición',  N'Alimentación saludable',      N'nutricion'),
 (@cat_nutri,     'eu', N'Nutrizioa',  N'Elikadura osasuntsua',        N'nutrizioa');

-- Relación jerárquica: Aula -> (Bienestar, Productividad, Nutrición)
INSERT INTO dbo.category_relation (id_parent, id_child) VALUES
 (@cat_aula, @cat_bienestar),
 (@cat_aula, @cat_product),
 (@cat_aula, @cat_nutri);

------------------------------------------------------------
-- 5) IMAGES (algunas asociadas a categorías)
------------------------------------------------------------
DECLARE @img_bien INT, @img_prod INT, @img_nutr INT, @img_forum INT;

INSERT INTO dbo.image (id_category, path) VALUES (@cat_bienestar, N'/assets/images/bienestar.png');      SET @img_bien  = SCOPE_IDENTITY();
INSERT INTO dbo.image (id_category, path) VALUES (@cat_product,   N'/assets/images/productividad.png');  SET @img_prod  = SCOPE_IDENTITY();
INSERT INTO dbo.image (id_category, path) VALUES (@cat_nutri,     N'/assets/images/nutricion.png');      SET @img_nutr  = SCOPE_IDENTITY();
INSERT INTO dbo.image (id_category, path) VALUES (NULL,           N'/assets/images/forum.png');          SET @img_forum = SCOPE_IDENTITY();

------------------------------------------------------------
-- 6) LEARN (cursos)
------------------------------------------------------------
DECLARE @learn1 INT, @learn2 INT, @learn3 INT;

INSERT INTO dbo.learn (id_image, url, status, duration)
VALUES (@img_bien, N'https://example.com/respiracion', 'active', 15);
SET @learn1 = SCOPE_IDENTITY();

INSERT INTO dbo.learn (id_image, url, status, duration)
VALUES (@img_prod, N'https://example.com/foco-profundo', 'active', 20);
SET @learn2 = SCOPE_IDENTITY();

INSERT INTO dbo.learn (id_image, url, status, duration)
VALUES (@img_nutr, N'https://example.com/desayunos', 'no active', 12);
SET @learn3 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 7) FORUM (eventos/foros)
------------------------------------------------------------
DECLARE @forum1 INT, @forum2 INT;

INSERT INTO dbo.forum (url, date_start, date_finish, status)
VALUES (N'https://example.com/foro-bienestar', '2025-09-05 18:00:00', '2025-09-05 19:30:00', 'activo');
SET @forum1 = SCOPE_IDENTITY();

INSERT INTO dbo.forum (url, date_start, date_finish, status)
VALUES (N'https://example.com/foro-productividad', '2025-09-12 17:00:00', '2025-09-12 18:30:00', 'borrador');
SET @forum2 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 8) RECOMMENDATION
------------------------------------------------------------
DECLARE @rec1 INT, @rec2 INT;
INSERT INTO dbo.recommendation (author, likes) VALUES (N'Ana', 3);   SET @rec1 = SCOPE_IDENTITY();
INSERT INTO dbo.recommendation (author, likes) VALUES (N'Ezequiel', 7); SET @rec2 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 9) ROUTINE
------------------------------------------------------------
DECLARE @rt1 INT, @rt2 INT;
INSERT INTO dbo.routine (frequency, duration) VALUES (2, 10);    SET @rt1 = SCOPE_IDENTITY();
INSERT INTO dbo.routine (frequency, duration) VALUES (3, 30);   SET @rt2 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 10) TRIAL (retos/pruebas)
------------------------------------------------------------
DECLARE @trial1 INT, @trial2 INT;
INSERT INTO dbo.trial (id_image, content) VALUES (@img_bien, N'Reto 7 días de respiración'); SET @trial1 = SCOPE_IDENTITY();
INSERT INTO dbo.trial (id_image, content) VALUES (@img_prod, N'Reto 3 días sin notificaciones'); SET @trial2 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 11) MEETING (quedadas/citas)
------------------------------------------------------------
DECLARE @meet1 INT, @meet2 INT;
INSERT INTO dbo.meeting (place, date_start, date_finish, email)
VALUES (N'Centro Cívico', '2025-09-20 10:00:00', '2025-09-20 11:00:00', N'contacto@oreka.local');
SET @meet1 = SCOPE_IDENTITY();

INSERT INTO dbo.meeting (place, date_start, date_finish, email)
VALUES (N'Parque Norte', '2025-09-27 18:00:00', '2025-09-27 19:00:00', N'eventos@oreka.local');
SET @meet2 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 12) LINK (hub por entidad)
------------------------------------------------------------
-- Aprende
DECLARE @lk_learn1 INT, @lk_learn2 INT, @lk_learn3 INT;
INSERT INTO dbo.link (id_learn) VALUES (@learn1); SET @lk_learn1 = SCOPE_IDENTITY();
INSERT INTO dbo.link (id_learn) VALUES (@learn2); SET @lk_learn2 = SCOPE_IDENTITY();
INSERT INTO dbo.link (id_learn) VALUES (@learn3); SET @lk_learn3 = SCOPE_IDENTITY();

-- Foros
DECLARE @lk_forum1 INT, @lk_forum2 INT;
INSERT INTO dbo.link (id_forum) VALUES (@forum1); SET @lk_forum1 = SCOPE_IDENTITY();
INSERT INTO dbo.link (id_forum) VALUES (@forum2); SET @lk_forum2 = SCOPE_IDENTITY();

-- Recomendaciones
DECLARE @lk_rec1 INT, @lk_rec2 INT;
INSERT INTO dbo.link (id_recommendation) VALUES (@rec1); SET @lk_rec1 = SCOPE_IDENTITY();
INSERT INTO dbo.link (id_recommendation) VALUES (@rec2); SET @lk_rec2 = SCOPE_IDENTITY();

-- Rutinas
DECLARE @lk_rt1 INT, @lk_rt2 INT;
INSERT INTO dbo.link (id_routine) VALUES (@rt1); SET @lk_rt1 = SCOPE_IDENTITY();
INSERT INTO dbo.link (id_routine) VALUES (@rt2); SET @lk_rt2 = SCOPE_IDENTITY();

-- Retos/Trials
DECLARE @lk_trial1 INT, @lk_trial2 INT;
INSERT INTO dbo.link (id_trial) VALUES (@trial1); SET @lk_trial1 = SCOPE_IDENTITY();
INSERT INTO dbo.link (id_trial) VALUES (@trial2); SET @lk_trial2 = SCOPE_IDENTITY();

-- Meetings
DECLARE @lk_meet1 INT, @lk_meet2 INT;
INSERT INTO dbo.link (id_meeting) VALUES (@meet1); SET @lk_meet1 = SCOPE_IDENTITY();
INSERT INTO dbo.link (id_meeting) VALUES (@meet2); SET @lk_meet2 = SCOPE_IDENTITY();

-- Legales / Banners (opcional)
DECLARE @lk_legal1 INT, @lk_banner1 INT;
INSERT INTO dbo.link (id_admin_legal) VALUES (@legal1); SET @lk_legal1  = SCOPE_IDENTITY();
INSERT INTO dbo.link (id_banner)      VALUES (@banner1); SET @lk_banner1 = SCOPE_IDENTITY();

------------------------------------------------------------
-- 13) CATEGORY_LINK (etiquetar cada link)
------------------------------------------------------------
-- Aprende
INSERT INTO dbo.category_link (id_link, id_category) VALUES
 (@lk_learn1, @cat_bienestar),
 (@lk_learn2, @cat_product),
 (@lk_learn3, @cat_nutri);

-- Foros
INSERT INTO dbo.category_link (id_link, id_category) VALUES
 (@lk_forum1, @cat_bienestar),
 (@lk_forum2, @cat_product);

-- Recomendaciones, Rutinas, Retos, Meetings (elige categorías generales)
INSERT INTO dbo.category_link (id_link, id_category) VALUES
 (@lk_rec1,   @cat_aula),
 (@lk_rec2,   @cat_aula),
 (@lk_rt1,    @cat_product),
 (@lk_rt2,    @cat_bienestar),
 (@lk_trial1, @cat_bienestar),
 (@lk_trial2, @cat_product),
 (@lk_meet1,  @cat_aula),
 (@lk_meet2,  @cat_aula);

------------------------------------------------------------
-- 14) TRANSLATION (títulos y descripciones por link)
------------------------------------------------------------
-- Aprende (ES/EU)
INSERT INTO dbo.translation (id_link, lang, title, content) VALUES
 (@lk_learn1, 'es', N'Respiración consciente', N'Guía breve para mejorar tu respiración.'),
 (@lk_learn1, 'eu', N'Arnasaz kontziente',     N'Arnasketa hobetzeko gida laburra.'),
 (@lk_learn2, 'es', N'Foco profundo',          N'Técnicas para reducir distracciones.'),
 (@lk_learn3, 'es', N'Desayunos saludables',    N'Ideas rápidas y equilibradas.');

-- Foros
INSERT INTO dbo.translation (id_link, lang, title, content) VALUES
 (@lk_forum1, 'es', N'Foro de Bienestar', N'Encuentro para compartir prácticas saludables.'),
 (@lk_forum2, 'es', N'Foro de Productividad', N'Estrategias de enfoque y organización.');

-- Resto
INSERT INTO dbo.translation (id_link, lang, title, content) VALUES
 (@lk_rec1, 'es', N'Libros recomendados', N'Lista curada por la comunidad.'),
 (@lk_rec2, 'es', N'Podcast de la semana', N'Episodio destacado y debate.'),
 (@lk_rt1,  'es', N'Rutina diaria',        N'10 minutos de estiramientos.'),
 (@lk_rt2,  'es', N'Rutina semanal',       N'Plan de 30 minutos para el sábado.'),
 (@lk_trial1,'es',N'Reto de respiración',  N'Completa 7 días seguidos.'),
 (@lk_trial2,'es',N'Reto sin notificaciones', N'Minimiza interrupciones por 3 días.'),
 (@lk_meet1,'es', N'Quedada en Centro Cívico', N'Actividad grupal.'),
 (@lk_meet2,'es', N'Paseo en Parque Norte',   N'Marcha suave y charla.');

-- Legal/Banner
INSERT INTO dbo.translation (id_link, lang, title, content) VALUES
 (@lk_legal1,  'es', N'Aviso legal', N'Términos y condiciones.'),
 (@lk_banner1, 'es', N'Sorteo activo', N'Participa y gana puntos.');

------------------------------------------------------------
-- 15) POINT (puntos asociados a acciones/enlaces)
------------------------------------------------------------
DECLARE @pt_learn1 INT, @pt_learn2 INT, @pt_forum1 INT, @pt_trial1 INT;

INSERT INTO dbo.point (id_link, points) VALUES (@lk_learn1, 10); SET @pt_learn1 = SCOPE_IDENTITY();
INSERT INTO dbo.point (id_link, points) VALUES (@lk_learn2, 12); SET @pt_learn2 = SCOPE_IDENTITY();
INSERT INTO dbo.point (id_link, points) VALUES (@lk_forum1,  5); SET @pt_forum1 = SCOPE_IDENTITY();
INSERT INTO dbo.point (id_link, points) VALUES (@lk_trial1,  8); SET @pt_trial1 = SCOPE_IDENTITY();

-- Más puntos opcionales
INSERT INTO dbo.point (id_link, points) VALUES
 (@lk_forum2, 5),
 (@lk_trial2, 7),
 (@lk_rt1,    4),
 (@lk_rt2,    6);

------------------------------------------------------------
-- 16) USER_ACTIVITY (qué hizo cada usuario)
------------------------------------------------------------
INSERT INTO dbo.user_activity (id_user, id_point, status) VALUES
 (@u_ezequiel, @pt_learn1, 'finalizado'),
 (@u_ezequiel, @pt_forum1, 'finalizado'),
 (@u_ana,      @pt_learn2, 'en_proceso'),
 (@u_pedro,    @pt_trial1, 'apuntado');

PRINT 'Seed completado correctamente.';
