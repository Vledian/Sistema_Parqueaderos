<?php
// Incluir la conexión a la base de datos
include 'db.php';

// Configuración de cabeceras para el API
header("Access-Control-Allow-Origin: *");  // Permitir solicitudes desde cualquier origen
header("Access-Control-Allow-Methods: POST");  // Solo permitir método POST
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Tipos de encabezados permitidos

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    loginUsuario($conn);
} else {
    header("HTTP/1.1 405 Method Not Allowed");
    echo json_encode(["mensaje" => "Método no permitido"]);
    exit;
}

// Función para manejar el inicio de sesión
function loginUsuario($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    // Verificar que el correo y la contraseña estén presentes
    if (!isset($data['correo'], $data['contrasena']) || empty($data['correo']) || empty($data['contrasena'])) {
        header('Content-Type: application/json');
        http_response_code(400); // Solicitud incorrecta
        echo json_encode(["mensaje" => "Correo y contraseña son requeridos"]);
        exit;
    }

    try {
        // Buscar al usuario por correo
        $stmt = $conn->prepare("SELECT id, nombre, correo, contrasena, rol FROM usuarios WHERE correo = :correo");
        $stmt->bindParam(':correo', $data['correo']);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si el usuario existe y si la contraseña es correcta
        if ($usuario && password_verify($data['contrasena'], $usuario['contrasena'])) {
            // Generar la respuesta con los datos del usuario
            $response = [
                "usuario_id" => $usuario['id'], // Cambiado a "usuario_id" para que coincida con el frontend
                "nombre" => $usuario['nombre'],
                "correo" => $usuario['correo'],
                "rol" => $usuario['rol'],
                "mensaje" => "Inicio de sesión exitoso"
            ];

            header('Content-Type: application/json');
            http_response_code(200); // Código de éxito
            echo json_encode($response);
        } else {
            // Respuesta de error si la contraseña es incorrecta o el usuario no existe
            header('Content-Type: application/json');
            http_response_code(401); // No autorizado
            echo json_encode(["mensaje" => "Credenciales incorrectas"]);
        }
    } catch (PDOException $e) {
        // Respuesta en caso de error con la base de datos
        header('Content-Type: application/json');
        http_response_code(500); // Error interno del servidor
        echo json_encode(["mensaje" => "Error del servidor: " . $e->getMessage()]);
    }
}
?>
