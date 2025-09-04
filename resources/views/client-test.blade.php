<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Socket Test</title>
  <script src="https://cdn.socket.io/4.7.2/socket.io.min.js"></script>
</head>
<body>
  <h2>Socket.IO Test Client</h2>
  <button onclick="sendLocation()">Send Location</button>

  <script>
    const socket = io("http://localhost:3001");

    socket.on("connect", () => {
      console.log("✅ Connected:", socket.id);
    });

    socket.on("driverLocationUpdated:1", (data) => {
      console.log("📍 Location update received:", data);
    });

    function sendLocation() {
      socket.emit("updateLocation", {
        driver_id: 1,
        lat: 40.7128,
        lng: -74.0060,
        token: "TEST_TOKEN"
      });
    }
  </script>
</body>
</html>
