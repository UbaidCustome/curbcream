require('dotenv').config();
const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const axios = require('axios');

const app = express();
app.use(express.json());

const server = http.createServer(app);

const io = socketIo(server, {
  cors: { origin: "*" }
});

// ✅ Socket events
io.on('connection', (socket) => {
  console.log('Client connected:', socket.id);

  // Driver location update
  socket.on('updateLocation', async (data) => {
    console.log("Location update:", data);

    try {
      await axios.post(`${process.env.LARAVEL_API}/api/driver/update-location`, {
        driver_id: data.driver_id,
        current_lat: data.lat,
        current_lng: data.lng
      }, {
        headers: { Authorization: `Bearer ${data.token}` }
      });
    } catch (err) {
      console.error("DB update error:", err.message);
    }

    io.emit(`driverLocationUpdated:${data.driver_id}`, {
      driver_id: data.driver_id,
      lat: data.lat,
      lng: data.lng
    });
  });

  socket.on('disconnect', () => {
    console.log('Client disconnected:', socket.id);
  });
});

// ✅ Laravel se booking emit karwane ke liye ek API bana rahe hain
app.post('/emit/new-schedule-booking', (req, res) => {
  const { booking, drivers } = req.body;

  if (!booking || !drivers) {
    return res.status(400).json({ error: 'Booking or drivers missing' });
  }

  drivers.forEach(driver => {
    io.emit(`newScheduleBooking:${driver.id}`, {
      booking,
      driver_id: driver.id
    });
  });

  console.log("📢 New schedule booking emitted to drivers:", drivers.map(d => d.id));
  res.json({ success: true });
});

server.listen(process.env.SERVER_PORT, () => {
  console.log(`🚀 Socket.IO running on port ${process.env.SERVER_PORT}`);
});