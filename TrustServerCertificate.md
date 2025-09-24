🔹 ¿Qué significa TrustServerCertificate=No?

    - Cuando Encrypt=Yes (que es obligatorio por defecto en ODBC 18), la conexión cliente–SQL Server se cifra con TLS.
    - Si pones TrustServerCertificate=No, el cliente valida el certificado TLS del servidor SQL igual que hace un navegador web con HTTPS:
        ¿Es un certificado emitido por una CA de confianza?
        ¿El nombre del certificado (CN o SAN) coincide con el nombre del servidor que usas en la cadena (Server=...)?
        ¿No está caducado ni revocado?
    - Si alguna de estas comprobaciones falla → la conexión se rechaza.

Esto garantiza que no solo está cifrado, sino que también estás hablando con el servidor real y no con un impostor (man-in-the-middle).

🔹 ¿Y qué pasa con TrustServerCertificate=Yes?

    - El cliente acepta cualquier certificado que presente el servidor, aunque no coincida el nombre, aunque sea autofirmado o caducado.
    - La conexión sigue yendo cifrada, pero sin validar la identidad del servidor.
    - Es útil para entornos de desarrollo o como “parche” mientras se instala un certificado TLS válido en SQL Server.
    - En producción es mala práctica porque permite ataques de suplantación.

🔹 ¿Por qué lo ves ahora?

    - Antes (ODBC 13, 11, o SQLNCLI) la opción por defecto era no cifrar o no validar, y todo “funcionaba” sin certificado.
    - Con ODBC 18 Microsoft subió la seguridad por defecto:
        - Encrypt=Yes
        - TrustServerCertificate=No
    - Por eso, si tu SQL Server no tiene un certificado TLS válido configurado, la conexión falla a menos que pongas TrustServerCertificate=Yes.

🔹 En la práctica:

    - Recomendado: Instalar un certificado TLS válido en el servidor SQL (CN = nombre que usas en Server=) y dejar TrustServerCertificate=No.
    - Temporal/desarrollo: TrustServerCertificate=Yes para que funcione aunque el cert sea autofirmado o no coincida el nombre.

👉 ¿Quieres que te dé los pasos exactos para comprobar si tu SQL Server tiene ya un certificado TLS válido y, si no, cómo instalar uno para que puedas usar TrustServerCertificate=No sin problemas?

    Te detallo cómo comprobarlo y, si hace falta, cómo configurar un certificado TLS en SQL Server para que funcione con TrustServerCertificate=No.

    🔎 1. Comprobar si SQL Server ya tiene certificado válido:
    
        Opción A – Desde SQL Server Configuration Manager:

            1. Abre SQL Server Configuration Manager en el servidor.
            2. Ve a:
            SQL Server Network Configuration → Protocols for [INSTANCENAME].
            3. Clic derecho en Protocols → Propiedades.
            4. Pestaña Certificate:
                - Si hay un certificado listado y seleccionado: SQL Server lo está usando.
                - Si está vacío → no hay ninguno asociado.
            5. Pestaña Flags:
                - “Force Encryption” indica si se exige TLS a todos los clientes.
                - Aunque no lo fuerces, ODBC 18 ya intentará usar cifrado.

        Opción B – Conectar desde cliente:

            1. En tu servidor web (donde corre PHP):
                Test-NetConnection SQLSERVERNAME -Port 1433
            2. Si conecta, prueba con sqlcmd:
            sqlcmd -S SQLSERVERNAME,1433 -E -N -C
                -N = obliga a cifrar.
                -C = confía en el certificado sin validarlo (equivale a TrustServerCertificate=Yes).
            Si -N falla pero -N -C funciona → hay cifrado, pero el certificado no es válido (o no coincide el nombre).

    🔧 2. Instalar un certificado TLS válido para SQL Server:
        Requisitos:
            - El servidor SQL debe tener un certificado válido en el almacén de Windows (Local Computer → Personal).
            - El certificado debe cumplir:

                - Uso de clave: Server Authentication (1.3.6.1.5.5.7.3.1).
                - Nombre común (CN) o Subject Alternative Name (SAN) = el hostname que usan los clientes (Server=sql.lacaja.local).
                - Emitido por una CA de confianza (interna o externa).
                - La clave privada debe estar presente y marcada como exportable.

        Pasos:
            1. Generar o solicitar el certificado:
                - Con CA interna (Active Directory Certificate Services) → crear plantilla de “Computer” o “Server Authentication” y emitir para sqlserver.lacaja.local.
                - O generar CSR y firmarlo con tu CA corporativa.
            2. Instalarlo en el servidor SQL:
                - Importar en el almacén Local Computer → Personal.
                - Verifica que aparezca la clave privada (“You have a private key that corresponds to this certificate”).
            3. Asociarlo en SQL Server:
                - Abre SQL Server Configuration Manager → Protocols for [INSTANCENAME].
                - Pestaña Certificate → selecciona tu certificado.
                - Guarda cambios.
            4. Reinicia el servicio SQL Server.

    🧪 3. Probar la conexión con validación (TrustServerCertificate=No):

        - En tu PHP (db_connect.php), la cadena debe ser algo así:
            $serverName = "sqlserver.lacaja.local,1433";
            $connectionOptions = [
                "Database" => "oreka",
                "Authentication" => SQLSRV_AUTHENTICATION_WINDOWS,
                "Encrypt" => 1,
                "TrustServerCertificate" => 0
            ];
            $conn = sqlsrv_connect($serverName, $connectionOptions);

        - Si el certificado está bien instalado y el nombre coincide, la conexión funcionará.
        - Si falla, revisa:
            - Que usas el mismo nombre DNS en la cadena de conexión que el CN/SAN del certificado.
            - Que el certificado no esté caducado.
            - Que la CA emisora sea de confianza para el servidor web.

    🚦 Resumen:

        - TrustServerCertificate=No exige que el servidor SQL tenga certificado TLS válido.
        - Si aún no lo tiene, debes emitir uno (con CA interna o externa), instalarlo y asociarlo en SQL Server.
        - Una vez configurado, puedes mantener seguridad fuerte y cumplir lo que pide el cliente.

👉 ¿Quieres que te prepare una guía paso a paso para emitir un certificado desde una CA interna de Active Directory (lo más habitual en empresas con dominio), para que lo instales en el SQL?

    Te preparo la guía para emitir e instalar un certificado TLS para SQL Server usando una CA interna de Active Directory (lo típico en un dominio como lacaja.local).

    🛠️ Guía paso a paso – Emitir e instalar certificado TLS desde una CA interna (ADCS): 

        1. Verificar que tienes una CA interna:
            - En la red de lacaja.local debería existir un servidor con Active Directory Certificate Services (ADCS).
            - Si no existe, habría que montar uno o usar CA corporativa/externa.

        2. Solicitar el certificado desde el servidor SQL
            2.1. Inicia sesión en el servidor SQL con una cuenta de administrador de dominio.
            2.2. Abre mmc.exe.
            2.3. Menú File → Add/Remove Snap-in.
            2.4. Selecciona Certificates → botón Add.
            2.5. Elige Computer account → Local computer → OK.
            2.6. En el árbol, expande Certificates (Local Computer) → Personal.
            2.7. Clic derecho en Personal → Certificates → All Tasks → Request New Certificate.
            2.8. Aparece el asistente de ADCS:
                - Selecciona la plantilla de certificado “Computer” o “Server Authentication” (depende de cómo esté configurada en la CA).
                - En “More information is required...” haz clic y pon el nombre DNS del servidor SQL (ejemplo: sqlsrv01.lacaja.local) en el campo Common Name o en Subject Alternative Name (SAN).
            2.9. Completa la solicitud → debería emitirse automáticamente.

        ⚠️ Importante: el nombre en el certificado debe coincidir con el que pongas en la cadena de conexión de PHP (Server=sqlsrv01.lacaja.local,1433).

        3. Confirmar que el certificado está bien:
            3.1. En MMC, dentro de Certificates → Personal, verás tu nuevo cert.
            3.2. Ábrelo y revisa:
                - Intended Purposes: Server Authentication.
                - You have a private key → ✔️.
                - Subject / SAN → incluye sqlsrv01.lacaja.local.
                - Issued by → tu CA corporativa (CA-LACAJA o similar).
                - Valid from / to → fechas correctas.

        4. Asociar el certificado a SQL Server
            4.1. Abre SQL Server Configuration Manager.
            4.2. Ve a: SQL Server Network Configuration → Protocols for MSSQLSERVER (o el nombre de tu instancia).
            4.3. Clic derecho → Properties.
            4.4. Pestaña Certificate → selecciona tu certificado.
            4.5. Pestaña Flags → Force Encryption = Yes (opcional; si lo dejas en No, ODBC 18 seguirá cifrando porque Encrypt=Yes en cliente).
            4.6. Aplica y reinicia el servicio SQL Server.

        5. Probar la conexión segura
            Desde el servidor web (IIS donde corre PHP):
                sqlcmd -S sqlsrv01.lacaja.local,1433 -E -N

                -E = Windows Auth (puedes usar -U/-P si es SQL Auth).
                -N = obliga a cifrar y validar el certificado.

            Si funciona → el certificado es válido y confiado.

        6. Ajustar PHP
            En tu db_connect.php:
                $serverName = "sqlsrv01.lacaja.local,1433";
                $connectionOptions = [
                    "Database" => "oreka",
                    "Authentication" => SQLSRV_AUTHENTICATION_WINDOWS,
                    "Encrypt" => 1,
                    "TrustServerCertificate" => 0 // ahora sí puedes dejarlo en No
                ];
                $conn = sqlsrv_connect($serverName, $connectionOptions);

                if ($conn === false) {
                    die(print_r(sqlsrv_errors(), true));
                }

    🚦 Resumen
        - CA interna de AD emite el certificado → lo instalas en el servidor SQL.
        - Asociado a la instancia mediante SQL Server Configuration Manager.
        - PHP/IIS podrá conectarse con Encrypt=Yes y TrustServerCertificate=No, cumpliendo buenas prácticas de seguridad y lo que pide el cliente.