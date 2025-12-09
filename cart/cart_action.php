<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';

$user_id = $_SESSION['customer_id'] ?? null;


if (!$user_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}



if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_POST['action'] ?? '';
$id     = $_POST['id'] ?? '';

switch ($action) {

    case 'add':

        if (!$user_id) {
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }
    
        // check if product already exists
        $check = $pdo->prepare("SELECT quantity FROM cart WHERE user_id=? AND product_id=?");
        $check->execute([$user_id, $id]);
    
        if ($check->rowCount() > 0) {
            $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = ? AND product_id = ?")
                ->execute([$user_id, $id]);
        } else {
            $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)")
                ->execute([$user_id, $id]);
        }
    
        echo json_encode(['status' => 'success']);
        exit;
    


        case 'remove':

            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $id]);
        
            echo json_encode(['status' => 'removed']);
            exit;
        

            case 'increase':

                $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 
                                       WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$user_id, $id]);
            
                echo json_encode(['status' => 'increased']);
                exit;
            
    
    
                case 'decrease':

                    $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity - 1 
                                           WHERE user_id = ? AND product_id = ?");
                
                    $stmt->execute([$user_id, $id]);
                
                    // Remove if quantity becomes 0
                    $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND quantity <= 0")
                        ->execute([$user_id, $id]);
                
                    echo json_encode(['status' => 'decreased']);
                    exit;
                
    
                    case 'clear':

                        $pdo->prepare("DELETE FROM cart WHERE user_id = ?")
                            ->execute([$user_id]);
                    
                        echo json_encode(['status' => 'cleared']);
                        exit;
                    
    

    case 'count':

        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
    
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
        echo json_encode(['count' => $total]);
        exit;
    


        case 'get':

            $stmt = $pdo->prepare("
              SELECT c.product_id as id, c.quantity as qty,
                     p.name, p.price, 
                     (SELECT image_url FROM product_images WHERE product_id = p.product_id AND is_primary=1 LIMIT 1) as image
              FROM cart c
              JOIN products p ON c.product_id = p.product_id
              WHERE c.user_id = ?
            ");
            $stmt->execute([$user_id]);
        
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            $total = 0;
            foreach ($items as $item) {
                $total += $item['qty'] * $item['price'];
            }
        
            echo json_encode([
              'items' => $items,
              'total' => $total
            ]);
            exit;
        
}

echo json_encode(['status' => 'success']);
