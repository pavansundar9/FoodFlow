<?php
session_start();

// ✅ Handle AJAX request for status update FIRST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $conn = mysqli_connect("localhost", "root", "", "sdp");
    if (!$conn) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit();
    }

    $donation_id = intval($_POST['donationId']);
    $quantity_donated = floatval($_POST['quantityDonated']);

    // Get current donation details
    $check_query = "SELECT quantity, quantity_donated FROM donations WHERE id = $donation_id";
    $check_result = mysqli_query($conn, $check_query);
    $donation_data = mysqli_fetch_assoc($check_result);

    $total_quantity = floatval($donation_data['quantity']);
    $current_donated = floatval($donation_data['quantity_donated']);
    $new_total_donated = $current_donated + $quantity_donated;
    $remaining_quantity = $total_quantity - $new_total_donated;

    // Determine new status
    if ($new_total_donated >= $total_quantity) {
        $new_status = 'completed';
        $new_total_donated = $total_quantity;
        $remaining_quantity = 0;
        $completion_date = date('Y-m-d H:i:s');
        $update_query = "UPDATE donations SET 
                            donation_status = '$new_status', 
                            quantity_donated = $new_total_donated,
                            quantity_remaining = $remaining_quantity,
                            completion_date = '$completion_date'
                         WHERE id = $donation_id";
    } else if ($new_total_donated > 0) {
        $new_status = 'partially_donated';
        $update_query = "UPDATE donations SET 
                            donation_status = '$new_status', 
                            quantity_donated = $new_total_donated,
                            quantity_remaining = $remaining_quantity
                         WHERE id = $donation_id";
    } else {
        $new_status = 'pending';
        $update_query = "UPDATE donations SET 
                            donation_status = '$new_status', 
                            quantity_donated = $new_total_donated,
                            quantity_remaining = $remaining_quantity
                         WHERE id = $donation_id";
    }

    if (mysqli_query($conn, $update_query)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating status']);
    }

    exit(); // ✅ Prevent full-page HTML from being sent
}

// ✅ At this point, it's a regular page load — continue

// Database connection for page rendering
$conn = mysqli_connect("localhost", "root", "", "sdp");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Optional: debug — only show in page loads, NOT during Ajax
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $type = $_SESSION['type'];
    // echo "<script>console.log('Email: $email, Type: $type');</script>";
}

// Auto-expire donations if past expiration date
$update_expired = "UPDATE donations 
                   SET donation_status = 'expired' 
                   WHERE expiration_date < CURDATE() 
                   AND donation_status NOT IN ('completed', 'expired')";
mysqli_query($conn, $update_expired);

// Auto-update quantity_remaining if NULL
$update_remaining = "UPDATE donations 
                     SET quantity_remaining = quantity - quantity_donated 
                     WHERE quantity_remaining IS NULL";
mysqli_query($conn, $update_remaining);

// Load donation records
if (isset($_SESSION['type'])) {
    if ($_SESSION['type'] == 'foodreceiver') {
        $type = 'receiver';
    }
    $donations_table = "SELECT * FROM donations WHERE $type = '$email' ORDER BY donation_date DESC";
    $donations_result = mysqli_query($conn, $donations_table);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations - FoodFlow</title>
    <link rel="website icon" type="image/png" href="images/food-flow-icon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Josefin+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#17272b',
                        accent: '#c8ed6c',
                        secondary: '#ff8a3d',
                        lightBg: '#f9f9f9',
                    },
                    fontFamily: {
                        sans: ['Josefin Sans', 'Arial', 'sans-serif'],
                    },
                    boxShadow: {
                        'custom': '0 5px 15px rgba(0, 0, 0, 0.1)',
                        'elevated': '0 10px 25px rgba(0, 0, 0, 0.15)',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes underlineAnimation {
            from { width: 0; }
            to { width: 100%; }
        }

        .underline-animation {
            background-image: linear-gradient(currentColor, currentColor);
            background-size: 0 2px;
            background-repeat: no-repeat;
            background-position: 0 100%;
            transition: background-size 0.3s ease-in-out;
        }

        .underline-animation:hover {
            background-size: 100% 2px;
        }

        body {
            overflow: hidden;
            height: 100vh;
        }

        .donation-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .donation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .donation-card.status-pending {
            border-left-color: #fbbf24;
        }

        .donation-card.status-partially_donated {
            border-left-color: #3b82f6;
        }

        .donation-card.status-completed {
            border-left-color: #10b981;
        }

        .donation-card.status-expired {
            border-left-color: #ef4444;
        }

        .expandable-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-in-out, padding 0.3s ease-in-out;
        }

        .expandable-content.expanded {
            max-height: 1000px;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .expand-button {
            transition: transform 0.3s ease;
        }

        .expand-button.rotated {
            transform: rotate(180deg);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-partially_donated {
            background-color: #dbeafe;
            color: #2563eb;
        }

        .status-completed {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-expired {
            background-color: #fecaca;
            color: #dc2626;
        }

        .progress-bar {
            background-color: #e5e7eb;
            border-radius: 9999px;
            height: 0.5rem;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.3s ease;
        }

        .progress-fill.pending {
            background-color: #fbbf24;
        }

        .progress-fill.partially_donated {
            background-color: #3b82f6;
        }

        .progress-fill.completed {
            background-color: #10b981;
        }

        .progress-fill.expired {
            background-color: #ef4444;
        }

        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .gallery-image {
            aspect-ratio: 1;
            border-radius: 0.5rem;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .gallery-image:hover {
            transform: scale(1.05);
        }

        .gallery-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
        }

        .modal-content img {
            max-width: 100%;
            max-height: 100%;
            border-radius: 0.5rem;
        }

        .modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            color: white;
            font-size: 2rem;
            cursor: pointer;
        }

        #mobile-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        #mobile-sidebar.open {
            transform: translateX(0);
        }

        #mobile-menu-button {
            transition: opacity 0.3s ease;
        }

        #mobile-menu-button.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .quantity-display {
            background: linear-gradient(135deg, #c8ed6c, #a3d944);
            color: #15803d;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            font-weight: 700;
            text-align: center;
            box-shadow: 0 2px 8px rgba(200, 237, 108, 0.3);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .update-status-btn {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .update-status-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .status-modal .modal-content {
            background: white;
            border-radius: 0.75rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>

<body class="font-sans bg-lightBg text-gray-800 flex">
    <!-- Mobile Menu Button -->
    <button id="mobile-menu-button" class="fixed top-4 left-4 z-30 bg-primary text-white p-2 rounded-md lg:hidden">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Mobile Sidebar Navigation -->
    <nav id="mobile-sidebar" class="mobile-menu fixed left-0 top-0 w-64 h-screen bg-primary p-5 z-20 lg:hidden">
        <div class="flex justify-between items-center mb-8">
            <img src="images/foodflow-logo.png" alt="FoodFlow Logo" class="h-16 w-auto">
            <button id="close-mobile-menu" class="text-white text-xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="flex flex-col list-none p-0 space-y-6">
            <li class="text-xl">
                <a href="profile.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation font-semibold">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="block text-black no-underline bg-white p-3 rounded shadow-md hover:shadow-lg transition-shadow duration-300">
                    <p class="underline-animation">Donations</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="communitypage.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Community Page</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="settings.php" class="text-white no-underline p-3 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Settings</p>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Desktop Sidebar Navigation -->
    <nav class="desktop-sidebar fixed left-0 top-0 w-[20%] min-w-[250px] h-screen bg-primary p-5 z-10 hidden lg:block">
        <img src="images/foodflow-logo.png" alt="FoodFlow Logo" class="h-24 w-auto mb-10">
        <ul class="flex flex-col list-none p-0 ml-5 space-y-12">
            <li class="text-xl">
                <a href="profile.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-shadow duration-300">
                    <p class="underline-animation font-semibold">Profile</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="donations.php" class="block text-black no-underline bg-white p-4 rounded shadow-md hover:shadow-lg transition-colors duration-300">
                    <p class="underline-animation">Donations</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="communitypage.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Community Page</p>
                </a>
            </li>
            <li class="text-xl">
                <a href="settings.php" class="text-white no-underline p-4 block hover:bg-white/10 rounded transition-colors duration-300">
                    <p class="underline-animation">Settings</p>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Overlay for mobile menu -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-10 hidden lg:hidden"></div>

    <!-- Image Modal -->
    <div id="imageModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <img id="modalImage" src="" alt="Full size image">
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal status-modal">
        <div class="modal-content p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-primary">Update Donation Status</h3>
                <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="statusUpdateForm">
                <input type="hidden" id="donationId" name="donationId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Quantity Donated:</label>
                    <input type="number" id="quantityDonated" name="quantityDonated" step="0.01" min="0.01"
                           class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    <div class="text-sm text-gray-500 mt-1">
                        <div>Total Available: <span id="totalQuantity"></span></div>
                        <div>Already Donated: <span id="alreadyDonated"></span></div>
                        <div>Remaining: <span id="remainingQuantity"></span></div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeStatusModal()" 
                            class="flex-1 py-2 px-4 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 py-2 px-4 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content lg:ml-[20%] min-w-0 flex-1 p-4 sm:p-6 lg:p-8 h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-8 mt-12 lg:mt-0">
            <h1 class="text-3xl font-bold text-primary flex items-center gap-3">
                <i class="fas fa-heart text-secondary"></i>
                My Donations
            </h1>
           <div class="flex items-center gap-4">
                <div class="text-sm text-gray-500">
                    <i class="fas fa-clock mr-1"></i>
                    Last updated: <?php echo date('M j, Y'); ?>
                </div>
                <?php if ($type == 'doner' && mysqli_num_rows($donations_result) > 0): ?>
                <button onclick="window.location.href='search-foodBank.php'" 
                    class="bg-secondary hover:bg-secondary/90 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-300 shadow-md hover:shadow-lg transform hover:scale-105 flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    New Donation
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Dashboard -->
        <?php
        // Calculate statistics
        $stats_query = "SELECT 
            COUNT(*) as total_donations,
            SUM(CASE WHEN donation_status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN donation_status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN donation_status = 'partially_donated' THEN 1 ELSE 0 END) as partial,
            SUM(CASE WHEN donation_status = 'expired' THEN 1 ELSE 0 END) as expired,
            SUM(quantity_donated) as total_donated_quantity
            FROM donations WHERE $type = '$email'";
        $stats_result = mysqli_query($conn, $stats_query);
        $stats = mysqli_fetch_assoc($stats_result);
        ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <div class="text-2xl font-bold text-primary mb-1"><?php echo $stats['total_donations']; ?></div>
                <div class="text-sm text-gray-600">Total Donations</div>
            </div>
            <div class="stat-card">
                <div class="text-2xl font-bold text-green-600 mb-1"><?php echo $stats['completed'] ?? 0; ?></div>
                <div class="text-sm text-gray-600">Completed</div>
            </div>
            <div class="stat-card">
                <div class="text-2xl font-bold text-yellow-600 mb-1"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="text-sm text-gray-600">Pending</div>
            </div>
            <div class="stat-card">
                <div class="text-2xl font-bold text-blue-600 mb-1"><?php echo $stats['partial'] ?? 0; ?></div>
                <div class="text-sm text-gray-600">Partial</div>
            </div>
            <div class="stat-card">
                <div class="text-2xl font-bold text-secondary mb-1"><?php echo number_format($stats['total_donated_quantity'], 1); ?></div>
                <div class="text-sm text-gray-600">Total Quantity <?php echo ($type == 'doner') ? 'Donated' : 'Received'; ?></div>
            </div>
        </div>

        <!-- Donations List -->
        <div class="space-y-4">
            <?php
            if (mysqli_num_rows($donations_result) > 0) {
                $donation_count = 0;
                while ($row = mysqli_fetch_assoc($donations_result)) {
                    $donation_count++;
                    $image_paths = array_filter(explode(';', $row["donation_img"]));
                    
                    // Calculate donation progress
                    $total_quantity = (float)$row["quantity"];
                    $donated_quantity = (float)$row["quantity_donated"];
                    $remaining_quantity = $total_quantity - $donated_quantity;
                    $progress_percentage = $total_quantity > 0 ? ($donated_quantity / $total_quantity) * 100 : 0;
                    
                    // Get status information
                    $status = $row["donation_status"];
                    $status_info = [
                        'pending' => ['text' => 'Pending', 'icon' => 'fas fa-clock'],
                        'partially_donated' => ['text' => 'Partial', 'icon' => 'fas fa-chart-pie'],
                        'completed' => ['text' => 'Completed', 'icon' => 'fas fa-check-circle'],
                        'expired' => ['text' => 'Expired', 'icon' => 'fas fa-times-circle']
                    ];
                    
                    // Calculate days until expiration for reference
                    $expiry_date = new DateTime($row["expiration_date"]);
                    $current_date = new DateTime();
                    $days_diff = $current_date->diff($expiry_date)->days;
                    $is_expired = $current_date > $expiry_date;

                    // Get partner information
                    if ($type == 'doner') {
                        $receiver_email = $row["receiver"];
                        if (!empty($receiver_email)) {
                            $receiver_query = "SELECT name, phone, address FROM foodreceivers WHERE email = '$receiver_email'";
                            $result = $conn->query($receiver_query);
                            $partner_info = $result->fetch_assoc();
                            $partner_name = $partner_info['name'] ?? 'Unknown Receiver';
                            $partner_phone = $partner_info['phone'] ?? 'N/A';
                            $partner_address = $partner_info['address'] ?? 'N/A';
                            $partner_type = 'Receiver';
                        } else {
                            $partner_name = 'Available for Pickup';
                            $partner_phone = 'N/A';
                            $partner_address = 'N/A';
                            $partner_type = 'Available';
                        }
                    } else {
                        $doner_email = $row["doner"];
                        $doner_query = "SELECT name, phone, address FROM fooddoners WHERE email = '$doner_email'";
                        $result = $conn->query($doner_query);
                        $partner_info = $result->fetch_assoc();
                        $partner_name = $partner_info['name'] ?? 'Unknown Donor';
                        $partner_phone = $partner_info['phone'] ?? 'N/A';
                        $partner_address = $partner_info['address'] ?? 'N/A';
                        $partner_type = 'Donor';
                    }
            ?>
                <div class="donation-card status-<?php echo $status; ?> bg-white rounded-xl shadow-custom overflow-hidden">
                    <div class="p-6">
                        <!-- Header Section -->
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="text-xl font-bold text-primary"><?php echo htmlspecialchars($partner_name); ?></h3>
                                    <span class="text-sm text-gray-500">•</span>
                                    <span class="text-sm text-gray-600"><?php echo $partner_type; ?></span>
                                </div>
                                <div class="flex items-center gap-4 text-sm text-gray-600 mb-3">
                                    <span><i class="fas fa-calendar mr-1"></i><?php echo date('M j, Y', strtotime($row["donation_date"])); ?></span>
                                    <span class="status-badge status-<?php echo $status; ?>">
                                        <i class="<?php echo $status_info[$status]['icon']; ?>"></i>
                                        <?php echo $status_info[$status]['text']; ?>
                                    </span>
                                    <?php if ($type == 'receiver' && in_array($status, ['pending', 'partially_donated']) && $remaining_quantity > 0): ?>
                                        <button onclick="openStatusModal(<?php echo $row['id']; ?>, <?php echo $total_quantity; ?>, <?php echo $donated_quantity; ?>, '<?php echo $row['quantity_unit']; ?>')" 
                                                class="update-status-btn">
                                            <i class="fas fa-edit mr-1"></i>Update
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Progress Bar -->
                                <?php if ($status !== 'expired'): ?>
                                <div class="mb-2">
                                    <div class="flex justify-between text-xs text-gray-600 mb-1">
                                        <span>Progress: <?php echo number_format($progress_percentage, 1); ?>%</span>
                                        <span><?php echo number_format($donated_quantity, 1); ?> / <?php echo number_format($total_quantity, 1); ?> <?php echo $row['quantity_unit']; ?></span>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php echo $status; ?>" style="width: <?php echo $progress_percentage; ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <button class="expand-button text-gray-400 hover:text-primary transition-colors p-2" onclick="toggleExpand(<?php echo $donation_count; ?>)">
                                <i class="fas fa-chevron-down text-lg"></i>
                            </button>
                        </div>

                        <!-- Quick Info Section -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-utensils text-secondary"></i>
                                    <span class="text-sm font-semibold text-gray-700">Food Type</span>
                                </div>
                                <p class="text-primary font-medium"><?php echo htmlspecialchars($row["food_type"]); ?></p>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-weight-hanging text-secondary"></i>
                                    <span class="text-sm font-semibold text-gray-700">Quantity Info</span>
                                </div>
                                <div class="quantity-display mb-2">
                                    Total: <?php echo htmlspecialchars($row["quantity"] . ' ' . $row["quantity_unit"]); ?>
                                </div>
                                <?php if ($donated_quantity > 0): ?>
                                    <div class="text-xs text-green-600 font-medium">
                                        <?php echo ($type == 'doner') ? 'Donated' : 'Received'; ?>: <?php echo number_format($donated_quantity, 1); ?> <?php echo $row['quantity_unit']; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($remaining_quantity > 0 && $status !== 'expired'): ?>
                                    <div class="text-xs text-orange-600 font-medium">
                                        Remaining: <?php echo number_format($remaining_quantity, 1); ?> <?php echo $row['quantity_unit']; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center gap-2 mb-1">
                                    <i class="fas fa-clock text-secondary"></i>
                                    <span class="text-sm font-semibold text-gray-700">Expires</span>
                                </div>
                                <p class="text-primary font-medium"><?php echo date('M j, Y', strtotime($row["expiration_date"])); ?></p>
                                <?php if (!$is_expired && $status !== 'expired'): ?>
                                    <p class="text-xs text-gray-500"><?php echo $days_diff; ?> days left</p>
                                <?php elseif ($status === 'completed'): ?>
                                    <p class="text-xs text-green-600">Completed: <?php echo date('M j, Y', strtotime($row["completion_date"])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Expandable Content -->
                        <div id="expand-<?php echo $donation_count; ?>" class="expandable-content">
                            <div class="border-t pt-4">
                                <!-- Detailed Information -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div>
                                        <h4 class="font-semibold text-primary mb-3 flex items-center gap-2">
                                            <i class="fas fa-info-circle text-secondary"></i>
                                            Additional Details
                                        </h4>
                                        <div class="space-y-3">
                                            <?php if (!empty($row["donation_info"])): ?>
                                                <div>
                                                    <span class="text-sm font-medium text-gray-700">Description:</span>
                                                    <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($row["donation_info"]); ?></p>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <span class="text-sm font-medium text-gray-700">Condition:</span>
                                                <p class="text-gray-600 mt-1"><?php echo $row["damage"] ? 'Damaged' : 'Good Condition'; ?></p>
                                            </div>
                                            <div class="flex gap-4">
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-box <?php echo $row['packaged'] ? 'text-green-500' : 'text-gray-400'; ?>"></i>
                                                    <span class="text-sm <?php echo $row['packaged'] ? 'text-green-600' : 'text-gray-500'; ?>">
                                                        <?php echo $row['packaged'] ? 'Packaged' : 'Not Packaged'; ?>
                                                    </span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <i class="fas fa-truck <?php echo $row['pickup'] ? 'text-green-500' : 'text-gray-400'; ?>"></i>
                                                    <span class="text-sm <?php echo $row['pickup'] ? 'text-green-600' : 'text-gray-500'; ?>">
                                                        <?php echo $row['pickup'] ? 'Pickup Available' : 'No Pickup'; ?>
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Donation Progress Details -->
                                            <?php if ($status !== 'expired'): ?>
                                            <div class="bg-blue-50 p-3 rounded-lg">
                                                <h5 class="font-medium text-blue-800 mb-2">Donation Progress</h5>
                                                <div class="space-y-1 text-sm">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Total Quantity:</span>
                                                        <span class="font-medium"><?php echo number_format($total_quantity, 1) . ' ' . $row['quantity_unit']; ?></span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600"><?php echo ($type == 'doner') ? 'Donated:' : 'Received:'; ?></span>
                                                        <span class="font-medium text-green-600"><?php echo number_format($donated_quantity, 1) . ' ' . $row['quantity_unit']; ?></span>
                                                    </div>
                                                    <?php if ($remaining_quantity > 0): ?>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-600">Remaining:</span>
                                                        <span class="font-medium text-orange-600"><?php echo number_format($remaining_quantity, 1) . ' ' . $row['quantity_unit']; ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="flex justify-between border-t pt-1 mt-2">
                                                        <span class="text-gray-600">Progress:</span>
                                                        <span class="font-medium text-blue-600"><?php echo number_format($progress_percentage, 1); ?>%</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h4 class="font-semibold text-primary mb-3 flex items-center gap-2">
                                            <i class="fas fa-user text-secondary"></i>
                                            <?php echo $partner_type; ?> Information
                                        </h4>
                                        <div class="space-y-3">
                                            <div>
                                                <span class="text-sm font-medium text-gray-700">Name:</span>
                                                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($partner_name); ?></p>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-700">Contact:</span>
                                                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($partner_phone); ?></p>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-700">Address:</span>
                                                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($partner_address); ?></p>
                                            </div>

                                            <!-- Status Timeline -->
                                            <div class="bg-gray-50 p-3 rounded-lg">
                                                <h5 class="font-medium text-gray-800 mb-2">Status History</h5>
                                                <div class="space-y-2 text-sm">
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas fa-clock text-blue-500"></i>
                                                        <span>Created: <?php echo date('M j, Y g:i A', strtotime($row["donation_date"])); ?></span>
                                                    </div>
                                                    <?php if ($status === 'completed' && !empty($row['completion_date'])): ?>
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas fa-check-circle text-green-500"></i>
                                                        <span>Completed: <?php echo date('M j, Y g:i A', strtotime($row["completion_date"])); ?></span>
                                                    </div>
                                                    <?php elseif ($status === 'expired'): ?>
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas fa-times-circle text-red-500"></i>
                                                        <span>Expired: <?php echo date('M j, Y', strtotime($row["expiration_date"])); ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Image Gallery -->
                                <?php if (!empty($image_paths)): ?>
                                    <div>
                                        <h4 class="font-semibold text-primary mb-3 flex items-center gap-2">
                                            <i class="fas fa-images text-secondary"></i>
                                            Donation Photos (<?php echo count($image_paths); ?>)
                                        </h4>
                                        <div class="image-gallery">
                                            <?php foreach ($image_paths as $index => $image_path): ?>
                                                <div class="gallery-image" onclick="openModal('<?php echo htmlspecialchars($image_path); ?>')">
                                                    <img src="<?php echo htmlspecialchars($image_path); ?>" alt="Donation Image <?php echo $index + 1; ?>" loading="lazy">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
                }
            } else {
            ?>
                <!-- No Donations Message -->
                <div class="flex flex-col items-center justify-center py-16">
                    <div class="text-center">
                        <div class="mb-6">
                            <i class="fas fa-heart text-6xl text-gray-300"></i>
                        </div>
                        <h2 class="text-2xl font-bold text-primary mb-4">No Donations Yet</h2>
                        <p class="text-gray-600 max-w-md mx-auto mb-8">
                            <?php echo ($type == 'doner') ?
                                "You haven't made any donations yet. Start making a difference today!" :
                                "You haven't received any donations yet. They'll appear here once you do.";
                            ?>
                        </p>
                        <?php if ($type == 'doner'): ?>
                            <button onclick="window.location.href='search-foodBank.php'" 
                                class="bg-secondary hover:bg-secondary/90 text-white font-bold py-3 px-8 rounded-full transition-all duration-300 shadow-lg hover:shadow-xl transform hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>Make Your First Donation
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
            }
            ?>
        </div>

        <!-- Bottom padding -->
        <div class="h-10"></div>
    </main>

    <script>
        // Mobile menu functionality
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const closeMobileMenu = document.getElementById('close-mobile-menu');
        const mobileSidebar = document.getElementById('mobile-sidebar');
        const mobileOverlay = document.getElementById('mobile-overlay');

        mobileMenuButton.addEventListener('click', () => {
            mobileSidebar.classList.add('open');
            mobileOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            mobileMenuButton.classList.add('hidden');
        });

        function closeMenu() {
            mobileSidebar.classList.remove('open');
            mobileOverlay.classList.add('hidden');
            document.body.style.overflow = '';
            mobileMenuButton.classList.remove('hidden');
        }

        closeMobileMenu.addEventListener('click', closeMenu);
        mobileOverlay.addEventListener('click', closeMenu);

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                closeMenu();
            }
        });

        // Expand/collapse functionality
        function toggleExpand(id) {
            const content = document.getElementById(`expand-${id}`);
            const button = content.previousElementSibling.querySelector('.expand-button');
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                button.classList.remove('rotated');
            } else {
                content.classList.add('expanded');
                button.classList.add('rotated');
            }
        }

        // Image modal functionality
        function openModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function closeStatusModal() {
            const modal = document.getElementById('statusModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        function openStatusModal(donationId, totalQuantity, currentDonated, unit) {
            const modal = document.getElementById('statusModal');
            const remaining = totalQuantity - currentDonated;
            
            document.getElementById('donationId').value = donationId;
            document.getElementById('quantityDonated').max = remaining;
            document.getElementById('totalQuantity').textContent = totalQuantity + ' ' + unit;
            document.getElementById('alreadyDonated').textContent = currentDonated + ' ' + unit;
            document.getElementById('remainingQuantity').textContent = remaining + ' ' + unit;
            
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        // Handle status update form submission
        document.getElementById('statusUpdateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('donationId', document.getElementById('donationId').value);
            formData.append('quantityDonated', document.getElementById('quantityDonated').value);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Status updated successfully!');
                    closeStatusModal();
                    location.reload(); // Refresh the page to show updated status
                } else {
                    alert('Error updating status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the status.');
            });
        });

        // Close modals when clicking outside
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeStatusModal();
            }
        });

        // Update remaining quantity in real-time
        document.getElementById('quantityDonated').addEventListener('input', function() {
            const totalQuantity = parseFloat(document.getElementById('totalQuantity').textContent);
            const alreadyDonated = parseFloat(document.getElementById('alreadyDonated').textContent);
            const newDonated = parseFloat(this.value) || 0;
            const newRemaining = totalQuantity - (alreadyDonated + newDonated);
            
            const unit = document.getElementById('totalQuantity').textContent.split(' ').slice(-1)[0];
            document.getElementById('remainingQuantity').textContent = 
                Math.max(0, newRemaining).toFixed(1) + ' ' + unit;
        });
    </script>
</body>
</html>