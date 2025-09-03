USE oreka;
SET NOCOUNT ON;

BEGIN TRY
  BEGIN TRAN;

  /* 1) Tablas hijas / de relación (dependen de otras) */
  DELETE FROM dbo.user_activity;      -- FK -> [user], point
  DELETE FROM dbo.category_link;      -- FK -> link, category
  DELETE FROM dbo.translation;        -- FK -> link
  DELETE FROM dbo.point;              -- FK -> link

  /* 2) “Hub” que referencia a muchas: primero hay que vaciarla antes de borrar sus padres */
  DELETE FROM dbo.link;               -- FK -> banner, learn, forum, recommendation, routine, trial, meeting, admin_legal

  /* 3) Padres referenciados por link (ya se puede) */
  DELETE FROM dbo.learn;              -- FK -> image
  DELETE FROM dbo.forum;
  DELETE FROM dbo.recommendation;
  DELETE FROM dbo.routine;
  DELETE FROM dbo.trial;              -- FK -> image
  DELETE FROM dbo.meeting;
  DELETE FROM dbo.banner;
  DELETE FROM dbo.admin_legal;

  /* 4) Categorías e imágenes (image depende de category; también hay traducciones/relaciones) */
  DELETE FROM dbo.image;              -- FK -> category
  DELETE FROM dbo.category_translation; -- FK -> category
  DELETE FROM dbo.category_relation;    -- FK -> category (parent/child)
  DELETE FROM dbo.category;

  /* 5) Usuarios (último porque user_activity dependía de esta) */
  DELETE FROM dbo.[user];

  /* 6) Reiniciar identidades (para que el próximo ID vuelva a 1) */
  DECLARE @tbl SYSNAME;
  DECLARE cur CURSOR FAST_FORWARD FOR
    SELECT t.name
    FROM sys.tables t
    JOIN sys.columns c ON c.object_id = t.object_id AND c.is_identity = 1
    WHERE t.schema_id = SCHEMA_ID('dbo');

  OPEN cur;
  FETCH NEXT FROM cur INTO @tbl;
  WHILE @@FETCH_STATUS = 0
  BEGIN
    DECLARE @sql NVARCHAR(400) = N'DBCC CHECKIDENT (''dbo.' + @tbl + N''', RESEED, 0);';
    EXEC sp_executesql @sql;
    FETCH NEXT FROM cur INTO @tbl;
  END
  CLOSE cur; DEALLOCATE cur;

  COMMIT TRAN;
  PRINT 'Limpieza completada y identidades reiniciadas.';
END TRY
BEGIN CATCH
  IF XACT_STATE() <> 0 ROLLBACK TRAN;
  -- Re-lanza el error para verlo completo en el cliente
  THROW;
END CATCH;
