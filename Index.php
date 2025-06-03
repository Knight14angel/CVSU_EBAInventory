<?php
// Welcome to your Inventory Dashboard's brain!
// This PHP script is the central hub, managing all the comings and goings
// of your product data, sales, and user interactions.

// --- Configuration and Data Storage Strategy ---
// We are now using plain JSON files for data storage.
// Think of these JSON files as your super basic, flat-file 'database'.
// Make sure 'products.json', 'sales.json', 'orders.json', and 'order_items.json'
// are sitting right next to this 'index.php' file and that your web server
// has permission to write to them!

$products_file = 'products.json';     // This file will hold all your product details.
$sales_file = 'sales.json';         // This one will keep track of every sale you make.
$orders_file = 'orders.json';       // This will store your main order details.
$order_items_file = 'order_items.json'; // This will store the items within each order.

// A little helper function to pull data out of our JSON files.
// It's designed to be a bit careful, checking if the file exists and isn't empty.
function loadData($filename)
{
    if (file_exists($filename) && filesize($filename) > 0) {
        $json = file_get_contents($filename); // Grab all the content from the file.
        // Decode that JSON string into a PHP associative array, which is much easier to work with.
        return json_decode($json, true);
    }
    // If the file is missing or empty, no worries! We'll just start with an empty slate.
    return [];
}

// Another helper, this time for saving our updated data back into the JSON files.
// We're using JSON_PRETTY_PRINT to make the files nice and readable for humans,
// which is super helpful for debugging or just peeking at your data.
function saveData($filename, $data)
{
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

// Let's get the ball rolling! Load up our existing products, sales, orders, and order items
// as soon as the page starts loading. This ensures we're always working with the latest info.
$products = loadData($products_file);
$sales = loadData($sales_file);
$orders = loadData($orders_file);
$order_items = loadData($order_items_file);


// --- The Heart of Product Management: Handling Your Forms ---
// This big block of code kicks into action whenever you submit a form on the dashboard.
// It checks what 'action' you're trying to perform (like adding a product, deleting, etc.)
// and then executes the appropriate logic, now interacting with our JSON files.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                // When you hit 'Add Product', we gather all the details you've entered.
                $new_product = [
                    'id' => uniqid('prod_'), // We generate a unique ID for each product – no two alike!
                    'name' => htmlspecialchars($_POST['product_name']), // Sanitize input to keep things safe from naughty characters.
                    'type' => htmlspecialchars($_POST['product_type']),
                    'size' => htmlspecialchars($_POST['product_size']),
                    'price' => floatval($_POST['product_price']), // Convert price to a proper number (float).
                    'stock' => intval($_POST['product_stock']),   // Convert stock to a whole number (integer).
                    'status' => 'active' // New products are always 'active' by default, ready for sale!
                ];
                $products[] = $new_product; // Add this shiny new product to our main list.
                saveData($products_file, $products); // And immediately save our updated product list.
                // After adding, we redirect you back to the products page.
                // This prevents accidental re-submissions if you refresh the page.
                header('Location: index.php?page=products');
                exit(); // Crucial: stop the script here after redirecting.

            case 'delete_product':
                $product_id_to_delete = $_POST['product_id'];
                // Time to say goodbye to a product! We filter our list, keeping only those
                // that don't match the ID of the product you want to delete.
                $products = array_filter($products, function ($product) use ($product_id_to_delete) {
                    return $product['id'] !== $product_id_to_delete;
                });
                // After removing, we re-index the array to keep everything neat and tidy.
                $products = array_values($products);
                saveData($products_file, $products); // Save the updated list, now without the deleted item.
                header('Location: index.php?page=products');
                exit();

            case 'archive_product':
                $product_id_to_archive = $_POST['product_id'];
                // Deciding to archive a product? We loop through our products to find the right one.
                foreach ($products as &$product) { // The '&' here is important! It means we're modifying the actual product in the array.
                    if ($product['id'] === $product_id_to_archive) {
                        $product['status'] = 'archived'; // Change its status to 'archived' – it's still there, just not active for sale.
                        break; // Found it, job done, no need to keep looping.
                    }
                }
                saveData($products_file, $products); // Save the list with the product's new archived status.
                header('Location: index.php?page=products');
                exit();

            case 'restore_product': // Action to bring an item back from the archive!
                $product_id_to_restore = $_POST['product_id'];
                // We're going on a mission to find that archived product...
                foreach ($products as &$product) {
                    if ($product['id'] === $product_id_to_restore) {
                        $product['status'] = 'active'; // ...and set its status back to 'active'! Welcome back!
                        break;
                    }
                }
                saveData($products_file, $products); // Save the updated list.
                // Redirect back to the page where the action was initiated, for a smooth experience.
                header('Location: ' . $_SERVER['HTTP_REFERER']); // This sends them back to the previous page.
                exit();

            case 'record_sale':
                $product_id_sold = $_POST['sold_product_id'];
                $quantity_sold = intval($_POST['sold_quantity']);

                $product_found = false;
                // When a sale happens, we need to find the product and update its stock.
                foreach ($products as &$product) {
                    if ($product['id'] === $product_id_sold) {
                        // Crucial check: do we actually have enough stock?
                        if ($product['stock'] >= $quantity_sold) {
                            $product['stock'] -= $quantity_sold; // Deduct the sold quantity from stock.
                            $product_found = true;
                            break;
                        }
                    }
                }

                if ($product_found) {
                    // If the product was found and stock was available, let's record this sale!
                    $new_sale = [
                        'sale_id' => uniqid('sale_'), // Another unique ID, this time for the sale itself.
                        'product_id' => $product_id_sold,
                        'quantity' => $quantity_sold,
                        'sale_date' => date('Y-m-d') // Timestamp the sale with today's date.
                    ];
                    $sales[] = $new_sale; // Add this new sale record to our sales list.
                    saveData($products_file, $products); // Don't forget to save the updated product stock!
                    saveData($sales_file, $sales);     // And save the new sale record too.
                } else {
                    // Oops! If we couldn't find the product or didn't have enough stock,
                    // in a more advanced app, you'd show a friendly message like "Out of stock!"
                    // For now, we just won't record the sale.
                }
                header('Location: index.php?page=sales');
                exit();

            case 'adjust_stock':
                $product_id_to_adjust = $_POST['product_id'];
                $quantity_to_deduct = intval($_POST['adjust_quantity']);

                // Let's find the product we need to adjust in our list.
                foreach ($products as &$product) {
                    if ($product['id'] === $product_id_to_adjust) {
                        // Make sure we don't deduct more than what's available (stock can't go negative!).
                        $product['stock'] = max(0, $product['stock'] - $quantity_to_deduct);
                        break; // Found it, adjusted, now we can stop looping.
                    }
                }
                saveData($products_file, $products); // Save our product list with the updated stock.
                header('Location: index.php?page=stock_records'); // Redirect back to the stock records page.
                exit();

                // NEW: Action to create a new order (JSON version)
            case 'create_order':
                $new_order = [
                    'order_id' => uniqid('order_'),
                    'order_date' => date('Y-m-d'),
                    'customer_name' => htmlspecialchars($_POST['customer_name']),
                    'total_amount' => 0.00, // Initialize total amount to zero
                    'status' => htmlspecialchars($_POST['order_status'])
                ];
                $orders[] = $new_order;
                saveData($orders_file, $orders);
                header('Location: index.php?page=orders');
                exit();

                // NEW: Action to add an item to an existing order (JSON version)
            case 'add_order_item':
                $order_id_to_add_item = $_POST['order_id'];
                $product_id_to_add = $_POST['product_id'];
                $quantity_to_add = intval($_POST['quantity']);

                $price_at_order = 0;
                // Find the product to get its price
                foreach ($products as $product) {
                    if ($product['id'] === $product_id_to_add) {
                        $price_at_order = $product['price'];
                        break;
                    }
                }

                if ($price_at_order > 0) {
                    $new_order_item = [
                        'item_id' => uniqid('item_'),
                        'order_id' => $order_id_to_add_item,
                        'product_id' => $product_id_to_add,
                        'quantity' => $quantity_to_add,
                        'price_at_order' => $price_at_order
                    ];
                    $order_items[] = $new_order_item;
                    saveData($order_items_file, $order_items);

                    // Update the total_amount for the parent order
                    foreach ($orders as &$order) {
                        if ($order['order_id'] === $order_id_to_add_item) {
                            $order['total_amount'] += ($quantity_to_add * $price_at_order);
                            break;
                        }
                    }
                    saveData($orders_file, $orders);
                }
                header('Location: index.php?page=orders');
                exit();

                // NEW: Action to update order status (JSON version)
            case 'update_order_status':
                $order_id_to_update = $_POST['order_id'];
                $new_status = htmlspecialchars($_POST['new_status']);

                foreach ($orders as &$order) {
                    if ($order['order_id'] === $order_id_to_update) {
                        $order['status'] = $new_status;
                        break;
                    }
                }
                saveData($orders_file, $orders);
                header('Location: index.php?page=orders');
                exit();

            case 'logout': // Our brand new logout action!
                // For this file-based demo, we'll just redirect to the main page,
                // effectively 'resetting' the dashboard view.
                header('Location: index.php');
                exit();
        }
    }
}

// --- Navigating Your Dashboard: Page Routing ---
// This little bit decides which section of your dashboard to show you.
// It looks at the 'page' parameter in the URL (e.g., index.php?page=products).
// If no page is specified, it gracefully defaults to showing you the 'products' list.
$page = $_GET['page'] ?? 'products';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Inventory Dashboard</title>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <link rel="stylesheet" href="index.css">
    <script>
        // Sidebar toggle logic
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle-btn');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                });
            }
        });
    </script>
</head>

<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <img src="image/Cavite_State_University_(CvSU).png" alt="Logo" class="logo">
            <h2>Your Inventory Dashboard</h2>
            <nav>
                <ul>
                    <li><a href="?page=profile" class="<?= ($page === 'profile') ? 'active' : '' ?>">Your Profile</a></li>
                    <li><a href="?page=products" class="<?= ($page === 'products') ? 'active' : '' ?>">Products Overview</a></li>
                    <li><a href="?page=sales" class="<?= ($page === 'sales') ? 'active' : '' ?>">Sales Tracker</a></li>
                    <li><a href="?page=add_product" class="<?= ($page === 'add_product') ? 'active' : '' ?>">Add New Product</a></li>
                    <li><a href="?page=orders" class="<?= ($page === 'orders') ? 'active' : '' ?>">Orders & Shipments</a></li>
                    <li><a href="?page=stock_records" class="<?= ($page === 'stock_records') ? 'active' : '' ?>">Detailed Stock Records</a></li>
                    <li>
                        <form action="index.php" method="POST" style="margin-top: 20px;">
                            <input type="hidden" name="action" value="logout">
                            <button type="submit" class="btn btn-danger" style="width: 100%;">Logout</button>
                        </form>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="main-content">
            <?php
            // --- This is where we dynamically load content for each part of your dashboard ---
            switch ($page) {
                case 'profile':
            ?>
                    <h1>Profile</h1>

                    <?php

                    // Determine dashboard type (admin or staff) based on file name or a variable
                    $dashboardType = 'admin';
                    if (strpos($_SERVER['SCRIPT_NAME'], 'staff_dashboard') !== false) {
                        $dashboardType = 'staff';
                    }

                    // Connect to the 'cvsuinventory' database and fetch the latest profile from 'logs' table
                    $conn = new mysqli('localhost', 'root', '', 'cvsuinventory');
                    if ($conn->connect_error) {
                        echo '<div class="profile-details"><p>Database connection failed: ' . htmlspecialchars($conn->connect_error) . '</p></div>';
                    } else {
                        // Only fetch logs for the current dashboard's role
                        $sql = "SELECT name, role, email, last_login FROM logs WHERE role = ? ORDER BY last_login DESC LIMIT 1";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('s', $dashboardType);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        if ($result && $result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                    ?>
                            <div class="profile-details">
                                <p><strong>Name:</strong> <?= htmlspecialchars($row['name']) ?></p>
                                <p><strong>Role:</strong> <?= htmlspecialchars($row['role']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($row['email']) ?></p>
                                <p><strong>Last Login:</strong> <?= htmlspecialchars($row['last_login']) ?></p>
                            </div>
                        <?php
                        } else {
                        ?>
                            <div class="profile-details">
                                <p>No profile data found for <?= htmlspecialchars($dashboardType) ?>. Please check if the 'logs' table has data.</p>
                            </div>
                    <?php
                        }
                        $stmt->close();
                        $conn->close();
                    }
                    break;
                case 'products':
                    ?>
                    <h1>Products Overview</h1>


                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Price (₱)</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7">It looks a little empty here! No products have been added yet. Why not add your first item?</td>
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
                                                    <button type="submit" class="btn btn-warning">Archive This</button>
                                                </form>
                                            <?php else: /* if status is 'archived' */ ?>
                                                <form action="index.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="restore_product">
                                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                                    <button type="submit" class="btn btn-info">Restore Item</button>
                                                </form>
                                            <?php endif; ?>
                                            <form action="index.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_product">
                                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                                <button type="submit" class="btn btn-danger">Permanently Delete</button>
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
                    <h1>Sales Tracker</h1>

                    <div style="max-height: 600px; overflow-y: auto; background: #f9f9f9; border-radius: 10px; margin-top: 20px; padding: 20px;">
                        <div class="sales-summary">
                            <h3>Quick Sale Entry</h3>

                            <form action="index.php" method="POST">
                                <input type="hidden" name="action" value="record_sale">
                                <div class="form-group">
                                    <label for="sold_product_id">Which product was sold?</label>
                                    <select id="sold_product_id" name="sold_product_id" required>
                                        <option value="">-- Choose a product --</option>
                                        <?php
                                        // We only show active products here, so you don't accidentally sell an archived item!
                                        $active_products_for_sale = array_filter($products, function ($p) {
                                            return $p['status'] === 'active';
                                        });
                                        foreach ($active_products_for_sale as $product): ?>
                                            <option value="<?= htmlspecialchars($product['id']) ?>">
                                                <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['size']) ?>) - Stock: <?= htmlspecialchars($product['stock']) ?> available
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="sold_quantity">How many units were sold?</label>
                                    <input type="number" id="sold_quantity" name="sold_quantity" min="1" value="1" required>
                                </div>
                                <button type="submit" class="btn">Record This Sale!</button>
                            </form>
                        </div>

                        <h3 style="color: #8A2BE2; margin-top: 0;">Your Daily Sales Report</h3>

                        <?php
                        // Let's crunch some numbers to get your daily sales summary!
                        $daily_sales_report = [];
                        foreach ($sales as $sale) {
                            $date = $sale['sale_date'];
                            $quantity = $sale['quantity'];
                            $product_id = $sale['product_id'];

                            $product_name = 'Unknown Product';
                            $product_price = 0;
                            foreach ($products as $p) {
                                if ($p['id'] === $product_id) {
                                    $product_name = $p['name'] . ' (' . $p['size'] . ')';
                                    $product_price = $p['price'];
                                    break;
                                }
                            }

                            if (!isset($daily_sales_report[$date])) {
                                $daily_sales_report[$date] = [
                                    'total_amount' => 0,
                                    'items_sold' => 0,
                                    'products_sold' => []
                                ];
                            }
                            $daily_sales_report[$date]['total_amount'] += ($quantity * $product_price);
                            $daily_sales_report[$date]['items_sold'] += $quantity;

                            if (!isset($daily_sales_report[$date]['products_sold'][$product_name])) {
                                $daily_sales_report[$date]['products_sold'][$product_name] = 0;
                            }
                            $daily_sales_report[$date]['products_sold'][$product_name] += $quantity;
                        }

                        // Sort the sales by date (most recent first) for display and graph.
                        krsort($daily_sales_report);

                        // Prepare data for the D3.js graph. We need an array of objects.
                        $graph_data = [];
                        foreach ($daily_sales_report as $date => $data) {
                            $graph_data[] = [
                                'date' => $date,
                                'revenue' => $data['total_amount']
                            ];
                        }
                        // Reverse the array for the graph so dates are chronological (left to right)
                        $graph_data = array_reverse($graph_data);

                        if (empty($daily_sales_report)): ?>
                            <p>It looks like you haven't recorded any sales yet. Let's get selling!</p>
                        <?php else: ?>
                            <div class="chart-container">
                                <h3 style="color: #8A2BE2;">Daily Sales Revenue Graph</h3>
                                <div id="sales-chart"></div>
                            </div>
                            <script>
                                // This JavaScript block uses D3.js to draw our sales graph.
                                // First, we get the sales data that PHP has prepared for us.
                                const salesData = <?= json_encode($graph_data) ?>;

                                // Set up the dimensions for our SVG chart.
                                const margin = {
                                    top: 20,
                                    right: 30,
                                    bottom: 40,
                                    left: 60
                                };
                                const width = 800 - margin.left - margin.right; // Adjust as needed
                                const height = 400 - margin.top - margin.bottom; // Adjust as needed

                                // Create the SVG element where our chart will live.
                                const svg = d3.select("#sales-chart")
                                    .append("svg")
                                    .attr("viewBox", `0 0 ${width + margin.left + margin.right} ${height + margin.top + margin.bottom}`)
                                    .attr("preserveAspectRatio", "xMidYMid meet")
                                    .append("g")
                                    .attr("transform", `translate(${margin.left},${margin.top})`);

                                // Define the X scale (for dates). We use a band scale for discrete bars.
                                const xScale = d3.scaleBand()
                                    .domain(salesData.map(d => d.date)) // Domain is all our unique dates
                                    .range([0, width]) // Map dates to the width of the SVG
                                    .padding(0.1); // Add some padding between bars

                                // Define the Y scale (for revenue). This will be a linear scale.
                                const yScale = d3.scaleLinear()
                                    .domain([0, d3.max(salesData, d => d.revenue) * 1.1]) // Domain from 0 to slightly above max revenue for breathing room
                                    .range([height, 0]); // Map revenue values to the height (inverted for SVG)

                                // Add the X axis to the bottom of the chart.
                                svg.append("g")
                                    .attr("class", "x axis")
                                    .attr("transform", `translate(0,${height})`)
                                    .call(d3.axisBottom(xScale))
                                    .selectAll("text")
                                    .style("text-anchor", "end")
                                    .attr("dx", "-.8em")
                                    .attr("dy", ".15em")
                                    .attr("transform", "rotate(-45)"); // Rotate labels for better readability if many dates

                                // Add the Y axis to the left of the chart.
                                svg.append("g")
                                    .attr("class", "y axis")
                                    .call(d3.axisLeft(yScale).tickFormat(d => `₱${d3.format(".2s")(d)}`)); // Format Y-axis labels with Peso sign

                                // Add Y-axis label
                                svg.append("text")
                                    .attr("transform", "rotate(-90)")
                                    .attr("y", 0 - margin.left)
                                    .attr("x", 0 - (height / 2))
                                    .attr("dy", "1em")
                                    .style("text-anchor", "middle")
                                    .style("font-size", "0.9em")
                                    .style("fill", "#555")
                                    .text("Total Revenue (₱)");

                                // Create a tooltip div that we can show/hide on hover.
                                const tooltip = d3.select("body").append("div")
                                    .attr("class", "tooltip")
                                    .style("opacity", 0);

                                // Now, let's draw the bars for our bar chart!
                                svg.selectAll(".bar")
                                    .data(salesData)
                                    .enter().append("rect")
                                    .attr("class", "bar")
                                    .attr("x", d => xScale(d.date)) // X position based on date
                                    .attr("y", d => yScale(d.revenue)) // Y position based on revenue
                                    .attr("width", xScale.bandwidth()) // Width of each bar
                                    .attr("height", d => height - yScale(d.revenue)) // Height of each bar
                                    .on("mouseover", function(event, d) { // What happens when mouse hovers over a bar
                                        tooltip.transition()
                                            .duration(200)
                                            .style("opacity", .9);
                                        tooltip.html(`<strong>${d.date}</strong><br/>Revenue: ₱${d.revenue.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`)
                                            .style("left", (event.pageX + 10) + "px")
                                            .style("top", (event.pageY - 28) + "px");
                                        d3.select(this).style("fill", "#28A745"); // Darken bar on hover
                                    })
                                    .on("mouseout", function(event, d) { // What happens when mouse leaves a bar
                                        tooltip.transition()
                                            .duration(500)
                                            .style("opacity", 0);
                                        d3.select(this).style("fill", "#32CD32"); // Revert bar color
                                    });

                                // Add a title to the graph
                                svg.append("text")
                                    .attr("x", (width / 2))
                                    .attr("y", 0 - (margin.top / 2))
                                    .attr("text-anchor", "middle")
                                    .style("font-size", "1.2em")
                                    .style("fill", "#6A0DAD")
                                    .text("Daily Sales Revenue");
                            </script>
                            <hr style="margin: 30px 0; border-color: #eee;">
                            <h3 style="color: #8A2BE2; margin-top: 30px;">Your Daily Sales Report (Detailed List)</h3>
                        <?php
                        // This is the existing detailed list of sales, now below the graph.
                        endif;
                        // The rest of the detailed daily sales report table remains here.
                        ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Total Items Sold</th>
                                    <th>Total Revenue (₱)</th>
                                    <th>Products Sold</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($daily_sales_report)): ?>
                                    <tr>
                                        <td colspan="4">No sales recorded yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($daily_sales_report as $date => $data): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($date) ?></td>
                                            <td><?= htmlspecialchars($data['items_sold']) ?></td>
                                            <td>₱<?= number_format($data['total_amount'], 2) ?></td>
                                            <td>
                                                <ul>
                                                    <?php foreach ($data['products_sold'] as $product_name => $qty): ?>
                                                        <li><?= htmlspecialchars($product_name) ?>: <?= htmlspecialchars($qty) ?> units</li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php
                    break;

                case 'add_product':
                ?>
                    <h1>Add a Brand New Product</h1>

                    <form action="index.php" method="POST">
                        <input type="hidden" name="action" value="add_product">
                        <div class="form-group">
                            <label for="product_name">What's the product called?</label>
                            <input type="text" id="product_name" name="product_name" required placeholder="e.g., Male Uniform (for a clear description)">
                        </div>
                        <div class="form-group">
                            <label for="product_type">What type of product is this?</label>
                            <select id="product_type" name="product_type" required>
                                <option value="">-- Pick a category --</option>
                                <option value="Uniform">Uniform</option>
                                <option value="Slacks">Slacks</option>
                                <option value="ID Lace">ID Lace</option>
                                <option value="P.E. Uniform">P.E. Uniform</option>
                                <option value="P.E. Slacks">P.E. Slacks</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="product_size">What size is it? (Small, Medium, Large, or N/A for non-sized items)</label>
                            <input type="text" id="product_size" name="product_size" required placeholder="e.g., Small, 30, XL, or just N/A">
                        </div>
                        <div class="form-group">
                            <label for="product_price">How much does it cost? (in Philippine Pesos ₱)</label>
                            <input type="number" id="product_price" name="product_price" step="0.01" min="0" required placeholder="e.g., 250.00">
                        </div>
                        <div class="form-group">
                            <label for="product_stock">How many do you have in stock right now?</label>
                            <input type="number" id="product_stock" name="product_stock" min="0" required value="100">
                        </div>
                        <button type="submit" class="btn">Add This Product!</button>
                    </form>
                <?php
                    break;

                case 'orders':
                ?>
                    <h1>Orders & Shipments</h1>


                    <div class="sales-summary">
                        <h3>Create a New Order</h3>

                        <form action="index.php" method="POST">
                            <input type="hidden" name="action" value="create_order">
                            <div class="form-group">
                                <label for="customer_name">Customer Name:</label>
                                <input type="text" id="customer_name" name="customer_name" required placeholder="e.g., Juan Dela Cruz">
                            </div>
                            <div class="form-group">
                                <label for="order_status">Initial Order Status:</label>
                                <select id="order_status" name="order_status" required>
                                    <option value="Pending">Pending</option>
                                    <option value="Processing">Processing</option>
                                    <option value="Shipped">Shipped</option>
                                    <option value="Delivered">Delivered</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" class="btn">Create Order</button>
                        </form>
                    </div>

                    <h3 style="color: #8A2BE2; margin-top: 30px;">All Current Orders</h3>
                    <?php if (empty($orders)): ?>
                        <p>No orders recorded yet. Let's create the first one!</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Total (₱)</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                    <th>Add Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['order_id']) ?></td>
                                        <td><?= htmlspecialchars($order['order_date']) ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($order['status']) ?></td>
                                        <td>
                                            <form action="index.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="update_order_status">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                                <select name="new_status" onchange="this.form.submit()" style="padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                                                    <option value="Pending" <?= ($order['status'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                                                    <option value="Processing" <?= ($order['status'] == 'Processing') ? 'selected' : '' ?>>Processing</option>
                                                    <option value="Shipped" <?= ($order['status'] == 'Shipped') ? 'selected' : '' ?>>Shipped</option>
                                                    <option value="Delivered" <?= ($order['status'] == 'Delivered') ? 'selected' : '' ?>>Delivered</option>
                                                    <option value="Cancelled" <?= ($order['status'] == 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
                                                </select>
                                            </form>
                                            <button type="button" class="btn btn-info" style="padding: 5px 10px; font-size: 0.85em;" onclick="toggleOrderItems('<?= htmlspecialchars($order['order_id']) ?>')">View Items</button>
                                        </td>
                                        <td>
                                            <form action="index.php" method="POST" style="display:flex; align-items:center;">
                                                <input type="hidden" name="action" value="add_order_item">
                                                <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                                <select name="product_id" required style="width: 150px; margin-right: 5px; padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                                                    <option value="">Select Product</option>
                                                    <?php
                                                    // Fetch active products for adding to orders
                                                    $active_products_for_order = array_filter($products, function ($p) {
                                                        return $p['status'] === 'active';
                                                    });
                                                    foreach ($active_products_for_order as $prod): ?>
                                                        <option value="<?= htmlspecialchars($prod['id']) ?>">
                                                            <?= htmlspecialchars($prod['name']) ?> (<?= htmlspecialchars($prod['size']) ?>) - ₱<?= number_format($prod['price'], 2) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="number" name="quantity" value="1" min="1" required style="width: 60px; margin-right: 5px; padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                                                <button type="submit" class="btn" style="padding: 5px 10px; font-size: 0.85em;">Add</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <tr id="order-items-<?= htmlspecialchars($order['order_id']) ?>" style="display:none;">
                                        <td colspan="7">
                                            <div style="padding: 10px; background-color: #f0f0f0; border-radius: 8px; margin-top: 5px;">
                                                <h4>Items in Order <?= htmlspecialchars($order['order_id']) ?>:</h4>
                                                <ul style="list-style-type: none; padding: 0;">
                                                    <?php
                                                    // Load items for this specific order from the JSON data
                                                    $items_for_this_order = array_filter($order_items, function ($item) use ($order) {
                                                        return $item['order_id'] === $order['order_id'];
                                                    });

                                                    if (empty($items_for_this_order)): ?>
                                                        <li>No items added to this order yet.</li>
                                                    <?php else: ?>
                                                        <?php foreach ($items_for_this_order as $item):
                                                            $product_name = 'Unknown Product';
                                                            $product_size = '';
                                                            foreach ($products as $p) {
                                                                if ($p['id'] === $item['product_id']) {
                                                                    $product_name = $p['name'];
                                                                    $product_size = $p['size'];
                                                                    break;
                                                                }
                                                            }
                                                        ?>
                                                            <li>
                                                                <?= htmlspecialchars($product_name) ?> (<?= htmlspecialchars($product_size) ?>) -
                                                                <?= htmlspecialchars($item['quantity']) ?> pcs @ ₱<?= number_format($item['price_at_order'], 2) ?> each
                                                            </li>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <script>
                            function toggleOrderItems(orderId) {
                                const row = document.getElementById(`order-items-${orderId}`);
                                if (row) {
                                    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
                                }
                            }
                        </script>
                    <?php endif; ?>
                <?php
                    break;

                case 'stock_records':
                ?>
                    <h1>Detailed Stock Records</h1>

                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                                <th>Actions / Adjust Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6">It seems your stock records are empty. Time to add some products!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                        <td><?= htmlspecialchars($product['type']) ?></td>
                                        <td><?= htmlspecialchars($product['size']) ?></td>
                                        <td><?= htmlspecialchars($product['stock']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($product['status'])) ?></td>
                                        <td>
                                            <?php if ($product['status'] === 'active'): ?>
                                                <form action="index.php" method="POST" style="display:flex; align-items:center;">
                                                    <input type="hidden" name="action" value="adjust_stock">
                                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                                    <input type="number" name="adjust_quantity" value="1" min="1" style="width: 60px; margin-right: 5px; padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                                                    <button type="submit" class="btn" style="padding: 5px 10px; font-size: 0.85em;">Deduct</button>
                                                </form>
                                            <?php else: /* if status is 'archived' */ ?>
                                                <form action="index.php" method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="restore_product">
                                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
                                                    <button type="submit" class="btn btn-info" style="padding: 5px 10px; font-size: 0.85em;">Restore</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php
                    break;

                default:

                ?>
                    <h1>Oops! Page Not Found</h1>

            <?php
                    break;
            }
            ?>
        </div>
    </div>
</body>

</html>