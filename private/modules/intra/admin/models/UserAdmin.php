<?php
class UserAdmin {
    /** @var PDO */
    private $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    /**
     * Obtiene usuarios paginados y filtrados.
     * $sort: 'usuario' | 'roles' (whitelist)
     * $dir: 'ASC' | 'DESC'
     * $roleFilter: 'user' | 'admin' | '' (todos)
     * $search: texto libre
     */
    public function getUsers(int $page, int $perPage, string $sort, string $dir, string $roleFilter, string $search): array {
        $whitelistSort = ['usuario' => '[usuario]', 'roles' => '[roles]'];
        $orderBy = $whitelistSort[$sort] ?? '[usuario]';
        $dir = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';

        $where = [];
        $params = [];

        if ($roleFilter !== '') {
            $where[] = '[roles] = :role';
            $params[':role'] = $roleFilter;
        }

        if ($search !== '') {
            // Busca en varios campos
            $where[] = '('
                . '[usuario] LIKE :q OR [nombre] LIKE :q OR [apel] LIKE :q OR [nif] LIKE :q OR [email] LIKE :q'
                . ')';
            $params[':q'] = '%'.$search.'%';
        }

        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        $offset = max(0, ($page - 1) * $perPage);

        // SQL Server 2019: OFFSET/FETCH requiere ORDER BY
        $sql = "
            SELECT [id], [usuario], [roles], [nombre], [apel], [nif], [email]
            FROM [oreka].[dbo].[user]
            $whereSql
            ORDER BY $orderBy $dir
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY;
        ";
        $st = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_STR);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $total = $this->countUsers($roleFilter, $search);

        return ['rows' => $rows, 'total' => (int)$total];
    }

    public function countUsers(string $roleFilter, string $search): int {
        $where = [];
        $params = [];
        if ($roleFilter !== '') {
            $where[] = '[roles] = :role';
            $params[':role'] = $roleFilter;
        }
        if ($search !== '') {
            $where[] = '('
                . '[usuario] LIKE :q OR [nombre] LIKE :q OR [apel] LIKE :q OR [nif] LIKE :q OR [email] LIKE :q'
                . ')';
            $params[':q'] = '%'.$search.'%';
        }
        $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

        $st = $this->pdo->prepare("SELECT COUNT(*) FROM [oreka].[dbo].[user] $whereSql");
        foreach ($params as $k => $v) $st->bindValue($k, $v, PDO::PARAM_STR);
        $st->execute();
        return (int)$st->fetchColumn();
    }

    public function updateRole(int $id, string $role): bool {
        if (!in_array($role, ['user','admin'], true)) return false;
        $st = $this->pdo->prepare("UPDATE dbo.[user] SET [roles] = :r WHERE [id] = :id");
        return $st->execute([':r' => $role, ':id' => $id]);
    }
}
