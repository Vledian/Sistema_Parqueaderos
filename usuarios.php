<?php
include 'db.php'; // Conexión a la base de datos

header("Access-Control-Allow-Origin: *");  // Permite solicitudes de cualquier origen
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");  // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Tipos de encabezados permitidos
// CRUD para la tabla usuarios
function manejarCRUDUsuarios($method, $conn) {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                obtenerUsuario($conn, $_GET['id']);
            } else {
                obtenerUsuarios($conn);
            }
            break;
        case 'POST':
            crearUsuario($conn);
            break;
        case 'PUT':
            actualizarUsuario($conn);
            break;
        case 'DELETE':
                eliminarUsuario($conn);
            break;
        default:
            header("HTTP/1.1 405 Method Not Allowed");
            echo json_encode(["mensaje" => "Método no permitido"]);
            break;
    }
}

// Métodos para usuarios
function obtenerUsuarios($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM usuarios");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function obtenerUsuario($conn, $id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

function crearUsuario($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    // Ver los datos recibidos (puedes quitar esto en producción)
    if (!empty($data['nombre']) && !empty($data['correo']) && !empty($data['contrasena']) && !empty($data['rol'])) {
        try {
            // Cifrado de la contraseña
            $hashed_password = password_hash($data['contrasena'], PASSWORD_BCRYPT);

            // Preparar la consulta
            $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, contrasena, rol) VALUES (:nombre, :correo, :contrasena, :rol)");
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':correo', $data['correo']);
            $stmt->bindParam(':contrasena', $hashed_password);
            $stmt->bindParam(':rol', $data['rol']);
            $stmt->execute();

            // Respuesta de éxito
            header('Content-Type: application/json');
            http_response_code(200);  // Código 200 para éxito
            echo json_encode(["mensaje" => "Usuario creado exitosamente"]);
        } catch (PDOException $e) {
            // Respuesta de error
            header('Content-Type: application/json');
            http_response_code(500);  // Error del servidor
            echo json_encode(["mensaje" => "Hubo un error al registrar el usuario", "error" => $e->getMessage()]);
        }
    } else {
        // Respuesta si los datos están incompletos
        header('Content-Type: application/json');
        http_response_code(400);  // Solicitud incorrecta (Bad Request)
        echo json_encode(["mensaje" => "Datos incompletos"]);
    }
}

function actualizarUsuario($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['id']) && !empty($data['nombre']) && !empty($data['correo']) && !empty($data['rol'])) {
        try {
            // Si la contraseña está presente en los datos, la ciframos y la incluimos en la actualización
            if (!empty($data['contrasena'])) {
                $hashed_password = password_hash($data['contrasena'], PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo, contrasena = :contrasena, rol = :rol WHERE id = :id");
                $stmt->bindParam(':contrasena', $hashed_password);
            } else {
                // Si no se proporciona una nueva contraseña, la omitimos en la consulta
                $stmt = $conn->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo, rol = :rol WHERE id = :id");
            }

            // Enlazar los demás parámetros
            $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
            $stmt->bindParam(':nombre', $data['nombre']);
            $stmt->bindParam(':correo', $data['correo']);
            $stmt->bindParam(':rol', $data['rol']);
            $stmt->execute();
            echo json_encode(["mensaje" => "Usuario actualizado correctamente"]);
        } catch (PDOException $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["mensaje" => "Datos incompletos"]);
    }
}

function eliminarUsuario($conn) {
    // Obtener los datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents("php://input"), true);

    // Verificar que el ID esté presente en los datos recibidos
    if (isset($data['id'])) {
        $id = $data['id'];

        try {
            // Preparar la consulta SQL para eliminar el usuario con el ID proporcionado
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(["mensaje" => "Usuario eliminado correctamente"]);
        } catch (PDOException $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    } else {
        // Si no se proporciona un ID, enviar un mensaje de error
        echo json_encode(["mensaje" => "ID requerido para eliminar"]);
    }
}
// Manejo de solicitud
manejarCRUDUsuarios($_SERVER['REQUEST_METHOD'], $conn);
?>
