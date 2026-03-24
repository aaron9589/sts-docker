<?php
require 'open_db.php';
require 'drop_down_list_functions.php';

$dbc = open_db();

// Pull in all open car orders
$sql = 'SELECT co.waybill_number as waybill_number,
               co.shipment as shipment_id,
               shipments.code as shipment,
               shipments.description as description,
               shipments.consignment as consignment_id,
               shipments.car_code as car_code_id,
               shipments.loading_location as loading_location_id,
               shipments.unloading_location as unloading_location_id,
               shipments.remarks,
               commodities.code as consignment,
               car_codes.code as car_code,
               loc01.code as loading_location,
               loc02.code as unloading_location,
               sta01.station as loading_station,
               sta02.station as unloading_station,
               (SELECT COUNT(*) FROM pool WHERE shipment_id = co.shipment) as pool_count
        FROM (
          SELECT DISTINCT waybill_number, shipment
          FROM car_orders
          WHERE car = "" OR car IS NULL
        ) as co
        LEFT JOIN shipments ON shipments.id = co.shipment
        LEFT JOIN commodities ON commodities.id = shipments.consignment
        LEFT JOIN car_codes ON car_codes.id = shipments.car_code
        LEFT JOIN locations loc01 ON loc01.id = shipments.loading_location
        LEFT JOIN locations loc02 ON loc02.id = shipments.unloading_location
        LEFT JOIN routing sta01 ON sta01.id = loc01.station
        LEFT JOIN routing sta02 ON sta02.id = loc02.station
        ORDER BY co.waybill_number';

$rs = mysqli_query($dbc, $sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STS - Fill Car Orders</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .order-card {
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        .order-card.pool {
            background-color: #ffff80;
        }
        .order-header {
            padding: 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .order-header:hover {
            background-color: #e9ecef;
        }
        .order-details {
            padding: 1rem;
            border-top: 1px solid #dee2e6;
        }
        .car-row {
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .car-row:hover {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }
        .car-row.pool {
            background-color: rgba(128, 128, 128, 0.3);
            color: white;
            background-color: gray;
        }
        .car-row.station {
            background-color: rgba(169, 169, 169, 0.3);
            background-color: darkgray;
            color: white;
        }
        .car-row.priority {
            background-color: rgba(211, 211, 211, 0.3);
            background-color: lightgray;
        }
        .car-row.system {
            background-color: white;
            border: 1px solid #dee2e6;
        }
        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 0.5rem;
        }
        .load-count {
            background-color: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            font-weight: bold;
            color: black;
        }
        .badge-category {
            font-size: 0.75rem;
            margin-left: 0.25rem;
        }
        .cars-container {
            max-height: 600px;
            overflow-y: auto;
        }
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .order-info-item {
            display: flex;
            flex-direction: column;
        }
        .order-info-label {
            font-weight: bold;
            color: #666;
            font-size: 0.85rem;
            text-transform: uppercase;
        }
        .order-info-value {
            font-size: 1rem;
            margin-top: 0.25rem;
        }
        .spinner-container {
            text-align: center;
            padding: 2rem;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-dark noprint mb-3" style="background-color: #2e7d32;">
  <div class="container-fluid">
    <span class="navbar-brand"><i class="bi bi-box-seam"></i> Fill Car Orders</span>
    <div>
      <a href="operations.html" class="btn btn-outline-light btn-sm me-2">
        <i class="bi bi-arrow-left"></i> Operations
      </a>
      <a href="index.html" class="btn btn-outline-light btn-sm">
        <i class="bi bi-house"></i> Home
      </a>
    </div>
  </div>
</nav>
    <div class="container-fluid px-4">
        <h5 class="mb-1">Fill Car Orders</h5>
        <p class="text-muted mb-3">Select an order to see available cars, then click a car to assign it.</p>

        <?php if (mysqli_num_rows($rs) > 0) { ?>
            <div class="mb-4 p-3 bg-light border rounded">
                <p class="mb-0">
                    <strong><?php echo mysqli_num_rows($rs); ?> open car orders</strong><br/>
                    Click on any order below to see available cars. Click on a car to assign it to the order.
                </p>
            </div>

            <div id="ordersContainer">
                <?php
                $row_count = 0;
                while ($row = mysqli_fetch_array($rs)) {
                    $is_pool = $row['pool_count'] > 0 ? true : false;
                    $pool_class = $is_pool ? 'pool' : '';
                    ?>
                    <div class="order-card <?php echo $pool_class; ?>" data-waybill="<?php echo htmlspecialchars($row['waybill_number']); ?>">
                        <div class="order-header" onclick="toggleOrder(this)">
                            <div>
                                <div style="font-weight: bold; font-size: 1.1rem;">
                                    <?php echo htmlspecialchars($row['waybill_number']); ?>
                                    <?php if ($is_pool) echo '<span class="badge bg-warning text-dark ms-2">Pool</span>'; ?>
                                </div>
                                <div style="font-size: 0.9rem; color: #666;">
                                    <?php echo htmlspecialchars($row['shipment']) . ' - ' . htmlspecialchars($row['description']); ?>
                                </div>
                            </div>
                            <div style="font-size: 1.2rem;">
                                <i class="bi bi-chevron-down"></i>
                            </div>
                        </div>

                        <div class="order-details" style="display: none;">
                            <div class="order-info-grid">
                                <div class="order-info-item">
                                    <span class="order-info-label">Consignment</span>
                                    <span class="order-info-value"><?php echo htmlspecialchars($row['consignment'] ?: '(none)'); ?></span>
                                </div>
                                <div class="order-info-item">
                                    <span class="order-info-label">Car Code</span>
                                    <span class="order-info-value"><?php echo htmlspecialchars($row['car_code']); ?></span>
                                </div>
                                <div class="order-info-item">
                                    <span class="order-info-label">Loading</span>
                                    <span class="order-info-value">
                                        <u><?php echo htmlspecialchars($row['loading_station']); ?></u><br/>
                                        <?php echo htmlspecialchars($row['loading_location']); ?>
                                    </span>
                                </div>
                                <div class="order-info-item">
                                    <span class="order-info-label">Unloading</span>
                                    <span class="order-info-value">
                                        <u><?php echo htmlspecialchars($row['unloading_station']); ?></u><br/>
                                        <?php echo htmlspecialchars($row['unloading_location']); ?>
                                    </span>
                                </div>
                                <?php if ($row['remarks']) { ?>
                                    <div class="order-info-item" style="grid-column: 1/-1;">
                                        <span class="order-info-label">Remarks</span>
                                        <span class="order-info-value"><?php echo htmlspecialchars($row['remarks']); ?></span>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="mt-3">
                                <h6>Available Cars:</h6>
                                <div class="cars-container">
                                    <div class="spinner-container">
                                        <div class="spinner-border" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2">Loading available cars...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                    $row_count++;
                }
                mysqli_close($dbc);
                ?>
            </div>
        <?php } else { ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> There are no car orders that need to be filled.
            </div>
        <?php } ?>
    </div>

    <script>
        function toggleOrder(headerElement) {
            const card = headerElement.closest('.order-card');
            const details = card.querySelector('.order-details');
            const chevron = headerElement.querySelector('i');

            if (details.style.display === 'none') {
                // Expanding - load cars
                details.style.display = 'block';
                chevron.classList.remove('bi-chevron-down');
                chevron.classList.add('bi-chevron-up');

                const waybill = card.getAttribute('data-waybill');
                loadAvailableCars(card, waybill);
            } else {
                // Collapsing
                details.style.display = 'none';
                chevron.classList.add('bi-chevron-down');
                chevron.classList.remove('bi-chevron-up');
            }
        }

        function loadAvailableCars(card, waybill) {
            const carsContainer = card.querySelector('.cars-container');

            $.ajax({
                url: 'get_available_cars_ajax.php',
                type: 'GET',
                data: { waybill_number: waybill },
                dataType: 'json',
                success: function(data) {
                    let html = '';

                    if (data.total_cars_found === 0) {
                        html = '<div class="alert alert-warning">No eligible cars found on the system</div>';
                    } else {
                        html = `<div class="mb-3 text-muted">
                            <small>
                                <strong>${data.total_cars_found} eligible cars found:</strong><br/>
                                <span class="badge" style="background-color: gray; color: white;">Pool: ${data.pool_count}</span>
                                <span class="badge" style="background-color: darkgray; color: white;">Station: ${data.station_count}</span>
                                <span class="badge" style="background-color: lightgray; color: black;">Priority: ${data.priority_count}</span>
                                <span class="badge bg-secondary">System: ${data.system_count}</span>
                            </small>
                        </div>`;

                        html += '<div class="car-grid">';
                        data.cars.forEach(car => {
                            const categoryClass = car.category;
                            const categoryLabel = {
                                'pool': 'Pool',
                                'station': 'Station',
                                'priority': 'Priority',
                                'system': 'System'
                            }[car.category] || 'System';

                            html += `<div class="car-row ${categoryClass}" onclick="assignCar('${waybill}', ${car.car_id}, this)">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong>${car.reporting_marks}</strong>
                                        <small class="badge-category badge bg-secondary">${categoryLabel}</small>
                                    </div>
                                    <div class="text-end">
                                        <div class="load-count">Load: ${car.load_count}</div>
                                    </div>
                                </div>
                                <small style="display: block; margin-top: 0.5rem;">
                                    <strong>Code:</strong> ${car.car_code}<br/>
                                    <strong>Location:</strong> <u>${car.current_station}</u> / ${car.current_location}
                                </small>
                                ${car.remarks ? `<small style="display: block; margin-top: 0.25rem; color: #666;"><strong>Remarks:</strong> ${car.remarks}</small>` : ''}
                            </div>`;
                        });
                        html += '</div>';
                    }

                    carsContainer.innerHTML = html;
                },
                error: function(error) {
                    carsContainer.innerHTML = '<div class="alert alert-danger">Error loading available cars. Please try again.</div>';
                    console.error('Error:', error);
                }
            });
        }

        function assignCar(waybill, carId, clickedElement) {
            // Show loading indicator
            const originalHtml = clickedElement.innerHTML;
            clickedElement.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Assigning...</span></div>';
            clickedElement.style.pointerEvents = 'none';

            $.ajax({
                url: 'assign_car_ajax.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    waybill_number: waybill,
                    car_id: carId
                }),
                dataType: 'json',
                success: function(response) {
                    // Remove the order card with fade out
                    const card = document.querySelector(`[data-waybill="${waybill}"]`);
                    card.style.transition = 'opacity 0.3s';
                    card.style.opacity = '0';

                    setTimeout(() => {
                        card.remove();

                        // Check if there are any orders left
                        if (document.querySelectorAll('.order-card').length === 0) {
                            document.getElementById('ordersContainer').innerHTML =
                                '<div class="alert alert-success"><i class="bi bi-check-circle"></i> All car orders have been filled!</div>';
                        }
                    }, 300);
                },
                error: function(error) {
                    clickedElement.innerHTML = originalHtml;
                    clickedElement.style.pointerEvents = 'auto';
                    alert('Error assigning car. Please try again.');
                    console.error('Error:', error);
                }
            });
        }
    </script>

    <style>
        .bi-chevron-down::before, .bi-chevron-up::before {
            font-size: 1.5rem;
        }
    </style>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
