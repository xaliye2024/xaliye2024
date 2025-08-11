<?php
session_start();
include 'connection.php';

$status = "";

// Tirinta booqashooyinka
$ip = $_SERVER['REMOTE_ADDR'];
try {
    $visit_query = $conn->prepare("INSERT INTO visits (ip_address) VALUES (:ip)");
    $visit_query->bindParam(':ip', $ip);
    $visit_query->execute();
} catch (Exception $e) {
    // Haddii ay cilad dhacdo, ha joojin bogga, iska daa error
}

// Tirada guud ee booqdayaasha
$total_visits = $conn->query("SELECT COUNT(*) FROM visits")->fetchColumn();

// Haddii foomka la submit gareeyo
if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST['tip'])) {
    $tip = htmlspecialchars($_POST['tip']);
    $address = htmlspecialchars($_POST['address']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $imageName = "";  // Halkan ka dhig string madhan halkii null

    // Upload image
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir);
        $imageName = time() . "_" . basename($_FILES["image"]["name"]);
        $targetFile = $targetDir . $imageName;

        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowedTypes)) {
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
                $status = "❌ Failed to upload image.";
            }
        } else {
            $status = "❌ Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }

    // Haddii error upload sawir uusan jirin, keydi xogta
    if (empty($status)) {
        try {
            $stmt = $conn->prepare("INSERT INTO reports (report, address, latitude, longitude, image) 
                                    VALUES (:tip, :address, :latitude, :longitude, :image)");
            $stmt->bindParam(':tip', $tip);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':latitude', $latitude);
            $stmt->bindParam(':longitude', $longitude);
            $stmt->bindParam(':image', $imageName);

            if ($stmt->execute()) {
                $status = "✅ Tip submitted successfully!";
            } else {
                $status = "❌ Error saving the tip.";
            }
        } catch (PDOException $e) {
            $status = "❌ Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>CID  Report</title>
    <style>
        body { font-family: Arial, sans-serif; background: #071e34; color: #fff; text-align: center; }
        h1 { color: #4db8ff; }
        .container { width: 400px; margin: 30px auto; background: #102b46; padding: 20px; border-radius: 10px; }
        textarea, input, button { width: 90%; padding: 10px; margin: 10px 0; border: none; border-radius: 5px; }
        button { background: #4db8ff; color: #fff; cursor: pointer; }
        button:hover { background: #3399ff; }
        p { color: #66ff99; }
        iframe { width: 100%; height: 250px; border: none; margin-top: 10px; }
    </style>
    <script>
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(fillLocation);
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        }
        function fillLocation(position) {
            document.getElementById("latitude").value = position.coords.latitude;
            document.getElementById("longitude").value = position.coords.longitude;
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            document.getElementById("mapPreview").src = `https://maps.google.com/maps?q=${lat},${lon}&z=15&output=embed`;
        }
        window.onload = getLocation;
    </script>
</head>
<body>
    <h1>CID - Submit Report</h1>
    <div class="container">
        <form method="POST" action="" enctype="multipart/form-data">
            <textarea name="tip" placeholder="Describe suspicious activity..." required></textarea><br>
            <input type="text" name="address" placeholder="Address or location name (optional)" /><br>
            <input type="file" name="image" accept="image/*"><br>
            <input type="hidden" name="latitude" id="latitude" />
            <input type="hidden" name="longitude" id="longitude" />
            <button type="submit">Submit Tip</button>
        </form>
        <iframe id="mapPreview" title="Map Preview"></iframe>
        <p><?php echo $status; ?></p>
        <p>👁️‍🗨️ Total Visitors: <strong><?php echo $total_visits; ?></strong></p>
    </div>
</body>
</html>
