<?php
require_once 'config.php';

// Get database connection
$conn = getDBConnection();

// Handle search and filter
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Build query
$query = "SELECT * FROM IV_Device_Info WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (DeviceName LIKE :search OR IPAddress LIKE :search)";
    $params[':search'] = "%$search%";
}

if (!empty($status)) {
    $query .= " AND Status = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY LastUpdated DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IV Bags Monitoring</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2ecc71;
            --primary-dark: #27ae60;
            --primary-light: #d5f5e3;
            --bg-dark: #1a1a1a;
            --bg-darker: #121212;
            --bg-card: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #b3b3b3;
            --border-color: #404040;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
        }

        body {
            background-color: var(--bg-dark);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-primary);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
            background: var(--bg-card);
            border-top: 4px solid var(--primary-color);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
        }

        .card-header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }

        .device-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .device-id {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-active {
            background-color: rgba(46, 204, 113, 0.2);
            color: var(--success-color);
        }

        .status-warning {
            background-color: rgba(241, 196, 15, 0.2);
            color: var(--warning-color);
        }

        .status-critical {
            background-color: rgba(231, 76, 60, 0.2);
            color: var(--danger-color);
        }

        .weight-info {
            background-color: var(--bg-darker);
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1rem 0;
            border: 1px solid var(--border-color);
        }

        .weight-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .weight-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .progress {
            height: 8px;
            border-radius: 4px;
            margin: 1rem 0;
            background-color: var(--bg-darker);
        }

        .progress-bar {
            background-color: var(--primary-color);
        }

        .last-updated {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .action-buttons .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin-right: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-info {
            background-color: var(--bg-darker);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .btn-warning {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--bg-dark);
        }

        .btn-info:hover {
            background-color: var(--border-color);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .btn-warning:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            color: var(--bg-dark);
        }

        .search-box {
            max-width: 300px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        .filter-section {
            background-color: var(--bg-card);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
        }

        .page-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
        }

        .updating {
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .form-select,
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        .form-select:focus,
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(46, 204, 113, 0.25);
            background-color: var(--bg-card);
            color: var(--text-primary);
        }

        .input-group-text {
            background-color: var(--bg-darker);
            border: 1px solid var(--border-color);
            color: var(--text-secondary);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-dark);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="container-fluid py-4">
        <h2 class="page-title">IV Bags Monitoring Dashboard</h2>

        <!-- Search and Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control search-box" name="search"
                            placeholder="Search by name or IP..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Warning" <?php echo $status === 'Warning' ? 'selected' : ''; ?>>Warning</option>
                        <option value="Critical" <?php echo $status === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Devices Cards -->
        <div class="row">
            <?php foreach ($devices as $device):
                $statusClass = strtolower($device['Status']);
            ?>
                <div class="col-md-4">
                    <div class="card" id="device-<?php echo $device['DeviceID']; ?>">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="device-name"><?php echo htmlspecialchars($device['DeviceName']); ?></div>
                                    <div class="device-id">ID: <?php echo htmlspecialchars($device['DeviceID']); ?></div>
                                </div>
                                <span class="status-badge status-<?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($device['Status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="weight-info">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="weight-label">Initial Weight</div>
                                        <div class="weight-value"><?php echo number_format($device['InitialWeight'], 2); ?> kg</div>
                                    </div>
                                    <div>
                                        <div class="weight-label">Current Weight</div>
                                        <div class="weight-value current-weight" data-device-id="<?php echo $device['DeviceID']; ?>">
                                            <span class="weight-number">--</span> kg
                                            <span class="updating-indicator" style="display: none;">
                                                <i class="fas fa-sync-alt fa-spin"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress mt-3">
                                    <div class="progress-bar bg-<?php echo $statusClass; ?>"
                                        role="progressbar"
                                        style="width: 0%"
                                        aria-valuenow="0"
                                        aria-valuemin="0"
                                        aria-valuemax="100"></div>
                                </div>
                                <div class="drip-info mt-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="weight-label">Drip Speed</div>
                                            <div class="weight-value drip-speed" data-device-id="<?php echo $device['DeviceID']; ?>">
                                                <span class="drip-speed-value">--</span> ml/hr
                                            </div>
                                        </div>
                                        <div>
                                            <div class="weight-label">Time Elapsed</div>
                                            <div class="weight-value time-elapsed" data-device-id="<?php echo $device['DeviceID']; ?>">
                                                <span class="time-value">--</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <div>
                                            <div class="weight-label">Start Time</div>
                                            <div class="weight-value start-time" data-device-id="<?php echo $device['DeviceID']; ?>">
                                                <?php echo date('M d, Y H:i', strtotime($device['startTime'])); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-danger restart-btn"
                                                data-device-id="<?php echo $device['DeviceID']; ?>"
                                                title="Restart IV Monitoring">
                                                <i class="fas fa-redo"></i> Restart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="last-updated">
                                    <i class="far fa-clock me-1"></i>
                                    Last updated: <span class="update-time">--:--</span>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-sm btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Store device information
        const devices = <?php echo json_encode($devices); ?>;

        // Function to format time
        function formatTimeElapsed(startTime) {
            const start = new Date(startTime);
            const now = new Date();
            const diff = now - start;

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

            return `${hours}h ${minutes}m`;
        }

        // Function to calculate drip speed
        function calculateDripSpeed(initialWeight, currentWeight, startTime) {
            const start = new Date(startTime);
            const now = new Date();
            const hoursElapsed = (now - start) / (1000 * 60 * 60);

            if (hoursElapsed <= 0) return 0;

            const weightDiff = initialWeight - currentWeight;
            // Assuming 1kg = 1000ml for IV fluids
            const mlDiff = weightDiff * 1000;
            const dripSpeed = mlDiff / hoursElapsed;

            return Math.max(0, dripSpeed); // Ensure non-negative value
        }

        // Function to update device weight
        async function updateDeviceWeight(deviceId, ipAddress, initialWeight, startTime) {
            console.log(`Updating device ${deviceId} with IP ${ipAddress}`);
            const weightElement = document.querySelector(`#device-${deviceId} .current-weight .weight-number`);
            const progressBar = document.querySelector(`#device-${deviceId} .progress-bar`);
            const updateTime = document.querySelector(`#device-${deviceId} .update-time`);
            const updatingIndicator = document.querySelector(`#device-${deviceId} .updating-indicator`);
            const dripSpeedElement = document.querySelector(`#device-${deviceId} .drip-speed-value`);
            const timeElapsedElement = document.querySelector(`#device-${deviceId} .time-value`);

            // Debug: Check if elements exist
            console.log('Found elements:', {
                weightElement: !!weightElement,
                progressBar: !!progressBar,
                updateTime: !!updateTime,
                updatingIndicator: !!updatingIndicator,
                dripSpeedElement: !!dripSpeedElement,
                timeElapsedElement: !!timeElapsedElement
            });

            try {
                updatingIndicator.style.display = 'inline-block';

                // Construct the correct URL based on the IP address
                let fetchUrl;
                if (ipAddress.includes('localhost')) {
                    fetchUrl = `http://${ipAddress}/data`;
                } else {
                    fetchUrl = `http://${ipAddress}:3000/data`;
                }

                console.log(`Fetching from: ${fetchUrl}`);

                const response = await fetch(fetchUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'Origin': window.location.origin
                    },
                    mode: 'cors',
                    credentials: 'omit'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('Response was not JSON');
                }

                const data = await response.json();
                console.log('Received data:', data);

                if (typeof data.weight !== 'number' || isNaN(data.weight)) {
                    throw new Error('Invalid weight data received');
                }

                const percentage = (data.weight / initialWeight) * 100;
                console.log('Calculated percentage:', percentage);

                // Update weight
                weightElement.textContent = data.weight.toFixed(2);
                progressBar.style.width = `${percentage}%`;
                progressBar.setAttribute('aria-valuenow', percentage);

                // Calculate drip speed (ml/hour)
                const timeElapsed = (new Date() - new Date(startTime)) / (1000 * 60 * 60); // hours
                const dripSpeed = (initialWeight - data.weight) / timeElapsed;
                dripSpeedElement.textContent = dripSpeed.toFixed(2);

                // Update time elapsed
                const hours = Math.floor(timeElapsed);
                const minutes = Math.floor((timeElapsed - hours) * 60);
                timeElapsedElement.textContent = `${hours}h ${minutes}m`;

                // Update status badge
                const statusBadge = document.querySelector(`#device-${deviceId} .status-badge`);
                if (data.weight <= 0) {
                    statusBadge.className = 'status-badge status-critical';
                    statusBadge.textContent = 'Empty';
                } else if (data.weight < initialWeight * 0.2) {
                    statusBadge.className = 'status-badge status-warning';
                    statusBadge.textContent = 'Low';
                } else {
                    statusBadge.className = 'status-badge status-active';
                    statusBadge.textContent = 'Active';
                }

                // Update timestamp
                const now = new Date();
                updateTime.textContent = now.toLocaleTimeString();

            } catch (error) {
                console.error(`Error fetching data for device ${deviceId}:`, error);
                weightElement.textContent = 'Error';
                dripSpeedElement.textContent = '--';
                timeElapsedElement.textContent = '--';

                // Show error in status badge
                const statusBadge = document.querySelector(`#device-${deviceId} .status-badge`);
                statusBadge.className = 'status-badge status-critical';
                statusBadge.textContent = 'Error';
            } finally {
                updatingIndicator.style.display = 'none';
            }
        }

        // Update all devices
        function updateAllDevices() {
            devices.forEach(device => {
                updateDeviceWeight(device.DeviceID, device.IPAddress, device.InitialWeight, device.startTime);
            });
        }

        // Initial update
        updateAllDevices();

        // Update every 5 seconds
        setInterval(updateAllDevices, 1000);

        // Function to restart IV monitoring
        async function restartIVMonitoring(deviceId) {
            try {
                const response = await fetch('restart_iv.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        deviceId: deviceId
                    })
                });

                if (response.ok) {
                    // Refresh the page to get updated data
                    location.reload();
                } else {
                    alert('Failed to restart IV monitoring');
                }
            } catch (error) {
                console.error('Error restarting IV monitoring:', error);
                alert('Error restarting IV monitoring');
            }
        }

        // Add event listeners for restart buttons
        document.addEventListener('DOMContentLoaded', function() {
            const restartButtons = document.querySelectorAll('.restart-btn');
            restartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to restart IV monitoring for this device?')) {
                        const deviceId = this.getAttribute('data-device-id');
                        restartIVMonitoring(deviceId);
                    }
                });
            });
        });
    </script>
</body>

</html>