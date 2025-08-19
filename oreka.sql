/* ===========================================================
   BASE DE DATOS: OREKA  (SQL Server 2019)
   COLLATION: Modern_Spanish_CI_AS
   ===========================================================*/
IF DB_ID('oreka') IS NOT NULL
BEGIN
    ALTER DATABASE oreka SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE oreka;
END;
GO

CREATE DATABASE oreka
COLLATE Modern_Spanish_CI_AS;
GO
USE oreka;
GO

/* ===========================================================
   TABLAS “núcleo” sin dependencias o con mínimas dependencias
   ===========================================================*/

/* users */
CREATE TABLE dbo.[user](
    id          INT IDENTITY(1,1) PRIMARY KEY,
    name        NVARCHAR(150) NOT NULL,
    language    CHAR(2)       NOT NULL,         -- es, en, etc.
    roles       CHAR(10)       NOT NULL
);

/* banner */
CREATE TABLE dbo.banner(
    id          INT IDENTITY(1,1) PRIMARY KEY,
    prize       NVARCHAR(MAX) NULL,
    date_start  DATETIME2     NULL,
    date_finish DATETIME2     NULL,
    created_at  DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    updated_at  DATETIME2     NOT NULL DEFAULT SYSDATETIME()
);

/* admin_legal */
CREATE TABLE dbo.admin_legal(
    id          INT IDENTITY(1,1) PRIMARY KEY,
    status      VARCHAR(20)   NOT NULL,          -- p.ej. activo/inactivo
    created_at  DATETIME2     NOT NULL DEFAULT SYSDATETIME(),
    updated_at  DATETIME2     NOT NULL DEFAULT SYSDATETIME()
);

/* image */
CREATE TABLE dbo.image(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    id_category  INT NULL,                        -- FK -> category (se crea más abajo)
    path         NVARCHAR(300) NOT NULL
);

/* learn (depende de image) */
CREATE TABLE dbo.learn(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    id_image     INT NULL,                        -- FK -> image
    url          NVARCHAR(500) NULL,
    status       VARCHAR(20) NOT NULL,            -- 'active' | 'no active'
    duration     INT NULL,                        -- minutos
    created_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    updated_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT ck_learn_status CHECK (status IN ('active','no active'))
);

/* forum */
CREATE TABLE dbo.forum(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    url          NVARCHAR(500) NULL,
    date_start   DATETIME2 NULL,
    date_finish  DATETIME2 NULL,
    status       VARCHAR(20) NOT NULL,           -- borrador | activo | finalizado
    created_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    updated_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT ck_forum_status CHECK (status IN ('borrador','activo','finalizado'))
);

/* recommendation */
CREATE TABLE dbo.recommendation(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    author       NVARCHAR(150) NULL,
    likes        INT NOT NULL DEFAULT 0,
    created_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    updated_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME()
);

/* routine */
CREATE TABLE dbo.routine(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    frequency    INT NULL,       
    duration     INT NULL,               
    created_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    updated_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME()
);

/* trial (depende de image) */
CREATE TABLE dbo.trial(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    id_image     INT NULL,                        -- FK -> image
    content      NVARCHAR(MAX) NULL,
    created_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    updated_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME()
);

/* meeting */
CREATE TABLE dbo.meeting(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    place        NVARCHAR(200) NULL,
    date_start   DATETIME2 NULL,
    date_finish  DATETIME2 NULL,
    email        NVARCHAR(320) NULL,
    created_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    updated_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME()
);

/* category */
CREATE TABLE dbo.category(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    entity_type  NVARCHAR(50) NOT NULL,          -- para saber con qué entidad se usa
    status       VARCHAR(15)  NOT NULL,          -- borrador | publicado
    created_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    updated_at   DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT ck_category_status CHECK (status IN ('borrador','publicado'))
);

/* -----------------------------------------------------------
   FKs tempranas posibles ahora que existen tablas base
   -----------------------------------------------------------*/
ALTER TABLE dbo.learn
  ADD CONSTRAINT fk_learn_image
  FOREIGN KEY (id_image) REFERENCES dbo.image(id);

ALTER TABLE dbo.trial
  ADD CONSTRAINT fk_trial_image
  FOREIGN KEY (id_image) REFERENCES dbo.image(id);

ALTER TABLE dbo.image
  ADD CONSTRAINT fk_image_category
  FOREIGN KEY (id_category) REFERENCES dbo.category(id);

/* ===========================================================
   link + tablas que dependen de link
   ===========================================================*/

/* link: “hub” que puede apuntar a varias entidades */
CREATE TABLE dbo.link(
    id                 INT IDENTITY(1,1) PRIMARY KEY,
    id_banner          INT NULL,
    id_learn           INT NULL,
    id_forum           INT NULL,
    id_recommendation  INT NULL,
    id_routine         INT NULL,
    id_trial           INT NULL,
    id_meeting         INT NULL,
    id_admin_legal     INT NULL,
    -- FKs a continuación
    CONSTRAINT fk_link_banner         FOREIGN KEY (id_banner)         REFERENCES dbo.banner(id),
    CONSTRAINT fk_link_learn          FOREIGN KEY (id_learn)          REFERENCES dbo.learn(id),
    CONSTRAINT fk_link_forum          FOREIGN KEY (id_forum)          REFERENCES dbo.forum(id),
    CONSTRAINT fk_link_recommendation FOREIGN KEY (id_recommendation) REFERENCES dbo.recommendation(id),
    CONSTRAINT fk_link_routine        FOREIGN KEY (id_routine)        REFERENCES dbo.routine(id),
    CONSTRAINT fk_link_trial          FOREIGN KEY (id_trial)          REFERENCES dbo.trial(id),
    CONSTRAINT fk_link_meeting        FOREIGN KEY (id_meeting)        REFERENCES dbo.meeting(id),
    CONSTRAINT fk_link_admin_legal    FOREIGN KEY (id_admin_legal)    REFERENCES dbo.admin_legal(id)
);

/* point (depende de link) */
CREATE TABLE dbo.point(
    id          INT IDENTITY(1,1) PRIMARY KEY,
    id_link     INT NOT NULL,                         -- FK -> link
    points      INT NOT NULL,
    created_at  DATETIME2 NOT NULL DEFAULT SYSDATETIME(),
    CONSTRAINT fk_point_link FOREIGN KEY (id_link) REFERENCES dbo.link(id)
);

/* user_activity (depende de user + point) */
CREATE TABLE dbo.user_activity(
    id          INT IDENTITY(1,1) PRIMARY KEY,
    id_user     INT NOT NULL,                         -- FK -> user
    id_point    INT NOT NULL,                         -- FK -> point
    status      VARCHAR(20) NOT NULL,                 -- apuntado | en_proceso | finalizado | sorteo
    CONSTRAINT fk_uact_user  FOREIGN KEY (id_user)  REFERENCES dbo.[user](id),
    CONSTRAINT fk_uact_point FOREIGN KEY (id_point) REFERENCES dbo.point(id),
    CONSTRAINT ck_uact_status CHECK (status IN ('apuntado','en_proceso','finalizado','sorteo'))
);

/* category_link (tabla puente category <-> link) */
CREATE TABLE dbo.category_link(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    id_link      INT NOT NULL,
    id_category  INT NOT NULL,
    CONSTRAINT fk_catlink_link     FOREIGN KEY (id_link)     REFERENCES dbo.link(id),
    CONSTRAINT fk_catlink_category FOREIGN KEY (id_category) REFERENCES dbo.category(id),
    CONSTRAINT ux_catlink UNIQUE (id_link, id_category)
);

/* category_translation */
CREATE TABLE dbo.category_translation(
    id           INT IDENTITY(1,1) PRIMARY KEY,
    id_category  INT NOT NULL,
    lang         CHAR(2) NOT NULL,
    name         NVARCHAR(200) NOT NULL,
    description  NVARCHAR(1000) NULL,
    slug         NVARCHAR(220) NOT NULL,
    CONSTRAINT fk_ct_category FOREIGN KEY (id_category) REFERENCES dbo.category(id),
    CONSTRAINT ux_ct UNIQUE (id_category, lang)
);

/* category_relation (relación jerárquica entre categorías) */
CREATE TABLE dbo.category_relation(
    id         INT IDENTITY(1,1) PRIMARY KEY,
    id_parent  INT NOT NULL,
    id_child   INT NOT NULL,
    CONSTRAINT fk_crel_parent FOREIGN KEY (id_parent) REFERENCES dbo.category(id),
    CONSTRAINT fk_crel_child  FOREIGN KEY (id_child)  REFERENCES dbo.category(id),
    CONSTRAINT ux_crel UNIQUE (id_parent, id_child),
    CONSTRAINT ck_crel_diff CHECK (id_parent <> id_child)
);

/* translation (traducciones de entidades enganchadas a link) */
CREATE TABLE dbo.translation(
    id        INT IDENTITY(1,1) PRIMARY KEY,
    id_link   INT NOT NULL,                      -- FK -> link (ej. learn/forum/recommendation…)
    lang      CHAR(2) NOT NULL,
    title     NVARCHAR(300) NOT NULL,
    content   NVARCHAR(MAX) NULL,
    CONSTRAINT fk_tr_link FOREIGN KEY (id_link) REFERENCES dbo.link(id),
    CONSTRAINT ux_tr UNIQUE (id_link, lang)
);

/* ===========================================================
   Índices útiles (opcionales, añade/ajusta a tu gusto)
   ===========================================================*/
CREATE INDEX ix_point_id_link           ON dbo.point(id_link);
CREATE INDEX ix_user_activity_user      ON dbo.user_activity(id_user);
CREATE INDEX ix_user_activity_point     ON dbo.user_activity(id_point);
CREATE INDEX ix_category_link_category  ON dbo.category_link(id_category);
CREATE INDEX ix_translation_link        ON dbo.translation(id_link);
CREATE INDEX ix_image_category          ON dbo.image(id_category);
GO