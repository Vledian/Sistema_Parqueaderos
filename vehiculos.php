<?php
include 'db.php'; // Conexión a la base de datos

header("Access-Control-Allow-Origin: *");  // Permite solicitudes de cualquier origen
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");  // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type, Authorization");  // Tipos de encabezados permitidos


// CRUD para la tabla vehiculos
function manejarCRUDVehiculos($method, $conn) {
    switch ($method) {
        case 'GET':
            if (isset($_GET['usuario_id']) && !empty($_GET['usuario_id'])) {
                obtenerVehiculosPorUsuario($conn, $_GET['usuario_id']);
            } else {
                obtenerTodosLosVehiculos($conn); // Caso para el auditor
            }
            break;
        case 'POST':
            crearVehiculo($conn);
            break;
        case 'PUT':
            actualizarVehiculo($conn);
            break;
        case 'DELETE':
            eliminarVehiculo($conn);
            break;
        default:
            header("HTTP/1.1 405 Method Not Allowed");
            echo json_encode(["mensaje" => "Método no permitido"]);
            break;
    }
}

// Obtener vehículos de un usuario específico
function obtenerVehiculosPorUsuario($conn, $usuarioId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM vehiculos WHERE usuario_id = :usuario_id");
        $stmt->bindParam(':usuario_id', $usuarioId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}
// Obtener todos los vehículos (para auditor)
function obtenerTodosLosVehiculos($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM vehiculos");
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// Crear un nuevo vehículo
function crearVehiculo($conn) {
    // Decodificar los datos JSON enviados
    $data = json_decode(file_get_contents("php://input"), true);

    // Verificar que los datos están correctamente recibidos
    if (!$data) {
        echo json_encode(["mensaje" => "No se recibieron datos o los datos no están en el formato correcto"]);
        return;
    }

    // Verificar que todos los datos requeridos estén presentes
    if (
        isset($data['usuario_id'], $data['tipo_vehiculo'], $data['placa']) &&
        !empty($data['usuario_id']) &&
        !empty($data['tipo_vehiculo']) &&
        !empty($data['placa'])
    ) {
        try {
            // Si el lugar_id no es proporcionado, se puede dejar como NULL
            $lugar_id = isset($data['lugar_id']) ? $data['lugar_id'] : NULL;
            // Estado por defecto "Activo"
            $estado = 'Activo';

            // Preparar la consulta SQL
            $stmt = $conn->prepare(
                "INSERT INTO vehiculos (usuario_id, tipo_vehiculo, placa, tiempo_llegada, estado, lugar_id) 
                 VALUES (:usuario_id, :tipo_vehiculo, :placa, NOW(), :estado, :lugar_id)"
            );
            // Vincular los parámetros
            $stmt->bindParam(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
            $stmt->bindParam(':tipo_vehiculo', $data['tipo_vehiculo'], PDO::PARAM_STR);
            $stmt->bindParam(':placa', $data['placa'], PDO::PARAM_STR);
            $stmt->bindParam(':estado', $estado, PDO::PARAM_STR); // Estado 'Activo'
            $stmt->bindParam(':lugar_id', $lugar_id, PDO::PARAM_INT); // Puede ser NULL

            // Ejecutar la consulta
            $stmt->execute();

            // Respuesta de éxito
            http_response_code(201);
            echo json_encode(['success' => true, 'mensaje' => 'Vehículo registrado exitosamente.']);
        } catch (PDOException $e) {
            // Mostrar detalles completos del error
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "mensaje" => "Hubo un error al registrar el vehículo.",
                "error" => $e->getMessage(),
                "query" => $stmt->queryString, // Consulta SQL ejecutada
                "params" => [
                    'usuario_id' => $data['usuario_id'],
                    'tipo_vehiculo' => $data['tipo_vehiculo'],
                    'placa' => $data['placa'],
                    'lugar_id' => $lugar_id
                ]
            ]);
        }
    } else {
        // Respuesta si los datos están incompletos
        http_response_code(400);
        echo json_encode(["success" => false, "mensaje" => "Datos incompletos."]);
    }
}


// Actualizar un vehículo
function actualizarVehiculo($conn) {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['id']) && !empty($data['usuario_id']) && !empty($data['tipo_vehiculo']) && !empty($data['placa']) && !empty($data['estado'])) {
        try {
            $stmt = $conn->prepare("UPDATE vehiculos SET tipo_vehiculo = :tipo_vehiculo, placa = :placa, estado = :estado WHERE id = :id AND usuario_id = :usuario_id");
            $stmt->bindParam(':id', $data['id'], PDO::PARAM_INT);
            $stmt->bindParam(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
            $stmt->bindParam(':tipo_vehiculo', $data['tipo_vehiculo']);
            $stmt->bindParam(':placa', $data['placa']);
            $stmt->bindParam(':estado', $data['estado']);
            $stmt->execute();
            echo json_encode(["mensaje" => "Vehículo actualizado correctamente"]);
        } catch (PDOException $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["mensaje" => "Datos incompletos"]);
    }
}

function eliminarVehiculo($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['id'])) {
        $id = $data['id'];
        try {
            $stmt = $conn->prepare("DELETE FROM vehiculos WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode(["mensaje" => "Vehículo eliminado correctamente"]);
        } catch (PDOException $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    } else {
        echo json_encode(["mensaje" => "ID requerido para eliminar"]);
    }
}

// Manejo de la solicitud
manejarCRUDVehiculos($_SERVER['REQUEST_METHOD'], $conn);
?>
