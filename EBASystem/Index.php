<?php

$products_file = 'products.json';
$sales_file = 'sales.json';


function loadData($filename) {
    if (file_exists($filename) && filesize($filename) > 0) {
        $json = file_get_contents($filename);
        
        return json_decode($json, true);
    }
    
    return [];
}


function saveData($filename, $data) {
   
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}


$products = loadData($products_file);
$sales = loadData($sales_file);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
               
                $new_product = [
                    'id' => uniqid('prod_'), 
                    'name' => htmlspecialchars($_POST['product_name']),
                    'type' => htmlspecialchars($_POST['product_type']),
                    'size' => htmlspecialchars($_POST['product_size']),
                    'price' => floatval($_POST['product_price']), 
                    'stock' => intval($_POST['product_stock']),  
                    'status' => 'active' 
                ];
                $products[] = $new_product; 
                saveData($products_file, $products); 
                header('Location: index.php?page=products');
                exit(); 
            case 'delete_product':
                $product_id_to_delete = $_POST['product_id'];
                
                $products = array_filter($products, function($product) use ($product_id_to_delete) {
                    return $product['id'] !== $product_id_to_delete;
                });
                
                $products = array_values($products);
                saveData($products_file, $products); 
                header('Location: index.php?page=products');
                exit();
            case 'archive_product':
                $product_id_to_archive = $_POST['product_id'];
                
                foreach ($products as &$product) { 
                    if ($product['id'] === $product_id_to_archive) {
                        $product['status'] = 'archived'; 
                        break; 
                    }
                }
                saveData($products_file, $products); 
                header('Location: index.php?page=products');
                exit();
            case 'record_sale':
                $product_id_sold = $_POST['sold_product_id'];
                $quantity_sold = intval($_POST['sold_quantity']);

                $product_found = false;
               
                foreach ($products as &$product) {
                    if ($product['id'] === $product_id_sold) {
                        if ($product['stock'] >= $quantity_sold) { 
                            $product['stock'] -= $quantity_sold; 
                            $product_found = true;
                            break;
                        }
                    }
                }

                if ($product_found) {
                    
                    $new_sale = [
                        'sale_id' => uniqid('sale_'), 
                        'product_id' => $product_id_sold,
                        'quantity' => $quantity_sold,
                        'sale_date' => date('Y-m-d') 
                    ];
                    $sales[] = $new_sale; 
                    saveData($products_file, $products); 
                    saveData($sales_file, $sales);   
                } else {
                   
                }
                header('Location: index.php?page=sales');
                exit();
        }
    }
}


$page = $_GET['page'] ?? 'products';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Dashboard</title>
    <style>
       
        body {
            font-family: 'Inter', sans-serif; 
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh; 
            background: linear-gradient(135deg, #8A2BE2, #32CD32); 
            color: #333;
            overflow-x: hidden;
        }

       
        .dashboard-container {
            display: flex;
            width: 100%;
            max-width: 1200px; 
            margin: 20px auto;
            background-color: rgba(255, 255, 255, 0.95); 
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2); 
            overflow: hidden; 
        }

       
        .sidebar {
            width: 250px;
            background-color: #6A0DAD; 
            color: white;
            padding: 20px;
            box-shadow: 5px 0 15px rgba(0, 0, 0, 0.1); 
            display: flex;
            flex-direction: column;
            border-top-left-radius: 15px;
            border-bottom-left-radius: 15px;
        }

        .sidebar h2 {
            text-align: center;
            color: #E0BBE4; 
            margin-bottom: 30px;
            font-size: 1.8em;
        }

        .sidebar nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar nav ul li {
            margin-bottom: 10px;
        }

        .sidebar nav ul li a {
            display: block;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .sidebar nav ul li a:hover,
        .sidebar nav ul li a.active {
            background-color: #9B59B6; 
            transform: translateX(5px);
        }

       
        .main-content {
            flex-grow: 1; 
            padding: 30px;
            background-color: #fff;
            border-top-right-radius: 15px;
            border-bottom-right-radius: 15px;
            overflow-y: auto; 
        }

        .main-content h1 {
            color: #8A2BE2; 
            margin-bottom: 25px;
            font-size: 2.2em;
            border-bottom: 2px solid #32CD32; 
            padding-bottom: 10px;
        }

        
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: calc(100% - 20px); 
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1em;
            box-sizing: border-box; 
        }

        .btn {
            background-color: #32CD32; 
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease, transform 0.2s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn:hover {
            background-color: #28A745; 
            transform: translateY(-2px);
        }

        .btn-danger {
            background-color: #dc3545;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-warning {
            background-color: #ffc107; 
            color: #333;
        }

        .btn-warning:hover {
            background-color: #e0a800;
        }

        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden; 
        }

        .data-table th, .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        .data-table th {
            background-color: #E0BBE4; 
            color: #6A0DAD; 
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        .data-table tr:nth-child(even) {
            background-color: #f9f9f9; 
        }

        .data-table tr:hover {
            background-color: #f1f1f1;
        }

        .data-table td form {
            display: inline-block; 
            margin-right: 5px;
        }

        
        .sales-summary {
            background-color: #f0f8ff; 
            border: 1px solid #d0e0f0;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .sales-summary h3 {
            color: #8A2BE2;
            margin-top: 0;
        }

        .sales-summary p {
            font-size: 1.1em;
            color: #444;
        }

       
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column; 
                margin: 10px;
            }

            .sidebar {
                width: 100%;
                border-radius: 15px 15px 0 0;
                padding: 15px;
            }

            .sidebar h2 {
                margin-bottom: 15px;
            }

            .sidebar nav ul {
                display: flex; 
                flex-wrap: wrap;
                justify-content: center;
            }

            .sidebar nav ul li {
                margin: 5px;
            }

            .sidebar nav ul li a {
                padding: 8px 12px;
                font-size: 0.9em;
            }

            .main-content {
                padding: 20px;
                border-radius: 0 0 15px 15px; 
            }

            .main-content h1 {
                font-size: 1.8em;
                margin-bottom: 15px;
            }

            .data-table th, .data-table td {
                padding: 8px 10px;
                font-size: 0.85em;
            }

            .btn {
                padding: 8px 15px;
                font-size: 0.9em;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <h2>Inventory Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="?page=profile" class="<?= ($page === 'profile') ? 'active' : '' ?>">Profile</a></li>
                    <li><a href="?page=products" class="<?= ($page === 'products') ? 'active' : '' ?>">Products</a></li>
                    <li><a href="?page=sales" class="<?= ($page === 'sales') ? 'active' : '' ?>">Sales</a></li>
                    <li><a href="?page=add_product" class="<?= ($page === 'add_product') ? 'active' : '' ?>">Add Product</a></li>
                    <li><a href="?page=orders" class="<?= ($page === 'orders') ? 'active' : '' ?>">Orders</a></li>
                    <li><a href="?page=stock_records" class="<?= ($page === 'stock_records') ? 'active' : '' ?>">Stock Records</a></li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <?php
           
            switch ($page) {
                case 'profile':
                    ?>
                    <h1>Profile</h1>
                    
                    <div class="profile-details">
                        <p><strong>Name:</strong> 
                        <p><strong>Role:</strong> 
                        <p><strong>Email:</strong>
                        <p><strong>Last Login:</strong>
                    </div>
                    <?php
                    break;

                case 'products':
                    ?>
                    <h1>Products</h1>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7">No products available. Please add some!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= htmlspecialchars($product['type']) ?></td>
                                        <td><?= htmlspecialchars($product['size']) ?></td>
                                        <td>₱<?= number_format($product['price'], 2) ?></td>
                                        <td><?= htmlspecialchars($product['stock']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($product['status'])) ?></td>
                                        <td>
                                            <?php if ($product['status'] === 'active'): ?>
                                                <form action="index.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="archive_product">
                                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                                    <button type="submit" class="btn btn-warning">Archive</button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="index.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                                <button type="submit" class="btn btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php
                    break;

                case 'sales':
                    ?>
                    <h1>Sales</h1>
                    

                    <div class="sales-summary">
                        <h3>Record a Sale</h3>
                        <form action="index.php" method="POST">
                            <input type="hidden" name="action" value="record_sale">
                            <div class="form-group">
                                <label for="sold_product_id">Select Product:</label>
                                <select id="sold_product_id" name="sold_product_id" required>
                                    <option value="">-- Select a product --</option>
                                    <?php
                                    // Filter for active products only
                                    $active_products = array_filter($products, function($p) { return $p['status'] === 'active'; });
                                    foreach ($active_products as $product): ?>
                                        <option value="<?= htmlspecialchars($product['id']) ?>">
                                            <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['size']) ?>) - Stock: <?= htmlspecialchars($product['stock']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="sold_quantity">Quantity:</label>
                                <input type="number" id="sold_quantity" name="sold_quantity" min="1" value="1" required>
                            </div>
                            <button type="submit" class="btn">Record Sale</button>
                        </form>
                    </div>

                    <h3 style="color: #8A2BE2; margin-top: 30px;">Daily Sales Report</h3>
                    <?php
                  
                    $daily_sales = [];
                    foreach ($sales as $sale) {
                        $date = $sale['sale_date'];
                        $product_id = $sale['product_id'];
                        $quantity = $sale['quantity'];

                       
                        $product_name = 'Unknown Product';
                        $product_price = 0;
                        foreach ($products as $p) {
                            if ($p['id'] === $product_id) {
                                $product_name = $p['name'] . ' (' . $p['size'] . ')';
                                $product_price = $p['price'];
                                break;
                            }
                        }

                        if (!isset($daily_sales[$date])) {
                            $daily_sales[$date] = [
                                'total_amount' => 0,
                                'items_sold' => 0,
                                'products_sold' => []
                            ];
                        }
                        $daily_sales[$date]['total_amount'] += ($quantity * $product_price);
                        $daily_sales[$date]['items_sold'] += $quantity;

                        if (!isset($daily_sales[$date]['products_sold'][$product_name])) {
                            $daily_sales[$date]['products_sold'][$product_name] = 0;
                        }
                        $daily_sales[$date]['products_sold'][$product_name] += $quantity;
                    }

                   
                    krsort($daily_sales);

                    if (empty($daily_sales)): ?>
                        <p>No sales recorded yet.</p>
                    <?php else: ?>
                        <?php foreach ($daily_sales as $date => $data): ?>
                            <div class="sales-summary" style="margin-bottom: 15px; background-color: #e6ffe6; border-color: #b3ffb3;">
                                <h4>Sales for <?= htmlspecialchars($date) ?></h4>
                                <p><strong>Total Items Sold:</strong> <?= htmlspecialchars($data['items_sold']) ?></p>
                                <p><strong>Total Revenue:</strong> ₱<?= number_format($data['total_amount'], 2) ?></p>
                                <p><strong>Products Sold:</strong></p>
                                <ul>
                                    <?php foreach ($data['products_sold'] as $product_name => $qty): ?>
                                        <li><?= htmlspecialchars($product_name) ?>: <?= htmlspecialchars($qty) ?> units</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php
                    break;

                case 'add_product':
                    ?>
                    <h1>Add New Product</h1>
                    <p>Fill in the details below to add a new product to your inventory.</p>
                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="add_product">
                        <div class="form-group">
                            <label for="product_name">Product Name:</label>
                            <input type="text" id="product_name" name="product_name" required placeholder="e.g., Male Uniform">
                        </div>
                        <div class="form-group">
                            <label for="product_type">Product Type:</label>
                            <select id="product_type" name="product_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="Uniform">Uniform</option>
                                <option value="Slacks">Slacks</option>
                                <option value="ID Lace">ID Lace</option>
                                <option value="P.E. Uniform">P.E. Uniform</option>
                                <option value="P.E. Slacks">P.E. Slacks</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="product_size">Size (e.g., Small, Medium, Large, N/A):</label>
                            <input type="text" id="product_size" name="product_size" required placeholder="e.g., Small, 30, N/A">
                        </div>
                        <div class="form-group">
                            <label for="product_price">Price (₱):</label>
                            <input type="number" id="product_price" name="product_price" step="0.01" min="0" required placeholder="e.g., 250.00">
                        </div>
                        <div class="form-group">
                            <label for="product_stock">Initial Stock Quantity:</label>
                            <input type="number" id="product_stock" name="product_stock" min="0" required value="100">
                        </div>
                        <button type="submit" class="btn">Add Product</button>
                    </form>
                    <?php
                    break;

                case 'orders':
                    ?>
                    
                        
                    </ul>
                    <?php
                    break;

                case 'stock_records':
                    ?>
                    <h1>Stock Records</h1>
                    
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="5">No stock records available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= htmlspecialchars($product['type']) ?></td>
                                        <td><?= htmlspecialchars($product['size']) ?></td>
                                        <td><?= htmlspecialchars($product['stock']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($product['status'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php
                    break;

                default:
                    
                    ?>
                    <h1>Page Not Found</h1>
                    <p>The requested page does not exist.</p>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>
</body>
</html>

