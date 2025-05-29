<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CvSU Inventory Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #006400;
            color: white;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: white;
            color: black;
            padding: 10px 20px;
        }

        header img {
            height: 60px;
            background: transparent;
        }

        header h1 {
            margin: 0;
            font-size: 20px;
        }

        header button {
            background-color: #006400;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
        }

        .sidebar {
            width: 250px;
            background-color: #004d00;
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            float: left;
        }

        .sidebar button {
            display: block;
            width: 100%;
            margin: 10px 0;
            padding: 10px;
            background-color: transparent;
            border: 2px solid white;
            color: white;
            border-radius: 20px;
            font-size: 16px;
            cursor: pointer;
            text-align: left;
        }

        main {
            margin-left: 250px;
            padding: 20px;
            text-align: center;
        }

        .main img {
            max-width: 80%;
            height: auto;
            background-size: cover;
        }
    </style>
</head>

<body>

    <?php

    $products_file = 'products.js';
    $sales_file = 'sales.js';


    function loadData($filename)
    {
        if (file_exists($filename) && filesize($filename) > 0) {
            $json = file_get_contents($filename);
            return json_decode($json, true);
        }

        return [];
    }


    function saveData($filename, $data)
    {

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

                    $products = array_filter($products, function ($product) use ($product_id_to_delete) {
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
                            $active_products = array_filter($products, function ($p) {
                                return $p['status'] === 'active';
                            });
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