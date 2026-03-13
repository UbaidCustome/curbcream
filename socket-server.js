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

const normalizeBearerToken = (token = '') => token.replace(/^Bearer\s+/i, '').trim();
const toFixedOrNull = (value) => {
  const parsed = Number.parseFloat(value);
  return Number.isFinite(parsed) ? parsed.toFixed(2) : null;
};

const resolveDistanceForDriver = (driver = {}) => {
  const km = toFixedOrNull(driver?.distance_km ?? driver?.distance);
  const miles = toFixedOrNull(
    driver?.distance_miles ?? (km !== null ? Number.parseFloat(km) * 0.621371 : null)
  );

  return { km, miles };
};

// ✅ Socket events
io.on('connection', (socket) => {
  console.log('Client connected:', socket.id);

  // Driver location update
  socket.on('updateLocation', async (data) => {
    console.log("Location update:", data);

    try {
      const token = normalizeBearerToken(data.token || '');

      if (!token) {
        console.log("⚠️ Missing token in updateLocation payload");
        return;
      }

      await axios.post(`${process.env.LARAVEL_API}/api/driver/update-location`, {
        driver_id: data.driver_id,
        current_lat: data.lat,
        current_lng: data.lng
      }, {
        headers: { Authorization: `Bearer ${token}` }
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
  const { type = 'schedule', booking, drivers } = req.body;

  if (!booking || !drivers) {
    return res.status(400).json({ error: 'Booking or drivers missing' });
  }

  const normalizedDrivers = (Array.isArray(drivers) ? drivers : [])
    .filter((driver) => driver?.id !== undefined && driver?.id !== null);

  if (normalizedDrivers.length === 0) {
    return res.status(400).json({ error: 'Booking or drivers missing' });
  }

  normalizedDrivers.forEach(driver => {
    const driverId = driver.id;
    const { km, miles } = resolveDistanceForDriver(driver);

    const driverPayload = {
      id: driverId,
      current_lat: driver?.current_lat ?? null,
      current_lng: driver?.current_lng ?? null,
      distance: km,
      distance_km: km,
      distance_miles: miles,
    };

    const bookingPayload = {
      ...booking,
      user_distance_km: km,
      user_distance_miles: miles,
    };

    io.emit(`newScheduleBooking:${driver.id}`, {
      booking: bookingPayload,
      driver_id: driverId,
      driver: driverPayload
    });

    io.to(`driver_${driverId}`).emit("newBooking", {
      type,
      booking: bookingPayload,
      driver_id: driverId,
      driver: driverPayload,
      drivers: [driverPayload],
      selected_driver: driverPayload,
      driver_id_list: [driverId],
      distance_km: km,
      distance_miles: miles,
      user_distance_km: km,
      user_distance_miles: miles,
      status: booking?.status ?? 'Pending'
    });
  });

  console.log("📢 New schedule booking emitted to drivers:", normalizedDrivers.map(d => d.id));
  res.json({ success: true, sent_to: normalizedDrivers.length });
});

server.listen(process.env.SERVER_PORT, () => {
  console.log(`🚀 Socket.IO running on port ${process.env.SERVER_PORT}`);
});