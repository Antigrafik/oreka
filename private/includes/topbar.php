<?php
$user = $_SERVER['REMOTE_USER'] ?? null;
$puntos = null;

if ($user) {
    try {
        $stmt = $pdo->prepare("SELECT puntos FROM users WHERE nombre = :nombre");
        $stmt->execute(['nombre' => $user]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $puntos = $result['puntos'];
        } else {
            $puntos = 'Usuario no encontrado';
        }
    } catch (PDOException $e) {
        $puntos = 'Error en la base de datos';
    }
} else {
    $user = 'Invitado';
    $puntos = 'No disponibles';
}
$stmt = $pdo->query("SELECT SYSTEM_USER AS usuario_sql, ORIGINAL_LOGIN() AS login_inicial");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Usuario real en SQL Server: " . $row['usuario_sql'] . "<br>";
echo "Login original: " . $row['login_inicial'] . "<br>";
?>

<p>Hola <?= htmlspecialchars($user) ?> | <?= htmlspecialchars($puntos) ?> puntos</p>




<!-- ACLARACIÓN

Perfecto, este resultado revela información muy útil sobre cómo funciona la autenticación en tu entorno IIS + SQL Server.

Vamos a desglosarlo:

🧩 Resultado que imprime tu código:
sql
Copiar
Editar
Usuario real en SQL Server: lacaja\ezequ
Login original: MicrosoftAccount\ezequiel_33_1@hotmail.com
Hola MicrosoftAccount\ezequiel_33_1@hotmail.com | 300 puntos
🧠 ¿Qué significa cada línea?
🔹 Usuario real en SQL Server: lacaja\ezequ
Esto es el valor de SYSTEM_USER, es decir, el usuario con el que realmente se está ejecutando la sesión de SQL Server.

👉 En tu caso, lacaja\ezequ es la cuenta de usuario de dominio que realmente accede al servidor SQL.

Esta es la cuenta que tiene permisos reales sobre la base de datos.

Es la cuenta bajo la cual corre el proceso PHP/IIS, o a la que está mapeado el acceso del usuario externo.

🔹 Login original: MicrosoftAccount\ezequiel_33_1@hotmail.com
Esto viene de ORIGINAL_LOGIN() y muestra:

El usuario con el que te logueaste inicialmente en IIS (lo que ves en $_SERVER['REMOTE_USER']).

Es tu cuenta de Microsoft externa (seguramente la que usas para iniciar sesión en Windows).

Entonces, el flujo es así:

Inicias sesión en tu ordenador o web con MicrosoftAccount\ezequiel_33_1@hotmail.com.

IIS recibe este usuario, pero internamente está configurado para mapearlo a lacaja\ezequ, que sí tiene permisos para acceder a SQL Server.

SQL Server recibe finalmente al usuario lacaja\ezequ, no al original de Microsoft Account.

✅ ¿Y por qué $_SERVER['REMOTE_USER'] es diferente?
En tu PHP estás usando:

php
Copiar
Editar
$user = $_SERVER['REMOTE_USER']; // MicrosoftAccount\ezequiel_33_1@hotmail.com
Que es lo que IIS entrega como "quien ha hecho login".

Pero la base de datos sólo conoce a lacaja\ezequ (que es quien ejecuta realmente el proceso de conexión).

🛠 Entonces, ¿por qué la consulta funciona?
Tu tabla users debe tener un registro como este:

nombre	puntos
MicrosoftAccount\ezequiel_33_1@hotmail.com	300

Y por eso encuentra 300 puntos al buscar por nombre = :nombre.

🧾 Conclusión
Contexto	Usuario
IIS / PHP	MicrosoftAccount\ezequiel_33_1@hotmail.com (REMOTE_USER)
SQL Server (efectivo)	lacaja\ezequ (SYSTEM_USER)
Login original en SQL	MicrosoftAccount\ezequiel_33_1@hotmail.com (ORIGINAL_LOGIN)

➡️ Aunque te logueas con tu cuenta Microsoft, SQL Server ve otra (la del dominio), porque hay un mapeo o delegación configurado en tu entorno Windows/IIS. -->