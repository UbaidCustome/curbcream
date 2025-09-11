require('dotenv').config({ path: __dirname + '/../.env' });
const express = require('express');
const https = require('https');
const http = require('http');
const fs = require('fs');
const socketIo = require('socket.io');
const axios = require('axios');
const cors = require('cors');
const bodyParser = require('body-parser');

const app = express();
app.use(cors());
app.use(express.json());
app.use(bodyParser.urlencoded({ extended: false }));

// ✅ Health check
app.get("/", (req, res) => {
    res.send("✅ Socket Server Running with SSL...");
});

// 🔹 Server setup
let server;
if (process.env.NODE_ENV === "production") {
    const options = {
        key: fs.readFileSync("/home2/apimyprojectstag/ssl/keys/cb1a2_c18cf_c2679ad41be46d41b208a74ef614017d.key"),
        cert: fs.readFileSync("/home2/apimyprojectstag/ssl/certs/api_myprojectstaging_com_cb1a2_c18cf_1760082179_00e0cc2fac07580cc575d78122c38f23.crt"),
    };
    server = https.createServer(options, app);
} else {
    server = http.createServer(app);
}

// 🔹 Socket.IO setup
const io = socketIo(server, {
    transports: ["websocket", "polling"],
    allowEIO3: true,
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
        credentials: true
    }
});

// 🔹 Socket events
io.on('connection', (socket) => {
    console.log('✅ Client connected:', socket.id);
    console.log("✅ Driver connected:", socket.id);

    socket.on("registerDriver", (driverId) => {
        socket.join(`driver_${driverId}`);
        console.log(`🚚 Driver ${driverId} joined room driver_${driverId}`);
    });
    socket.on('updateLocation', async (data) => {
        console.log("📍 Location update:", data);
    
        try {
            // ✅ Extract token from socket handshake headers
            const token = socket.handshake.auth.token || data.token;
            
            const res = await axios.post(`${process.env.LARAVEL_API}/driver/update-location`, {
                current_lat: data.current_lat,
                current_lng: data.current_lng
            }, {
                headers: { 
                    Authorization: `Bearer ${token}` // ✅ Use extracted token
                }
            });
    
            console.log("✅ DB update success:", res.data);
    
            io.emit(`driverLocationUpdated:${data.user_id}`, {
                current_lat: data.current_lat,
                current_lng: data.current_lng
            });
            console.log(`Location Update for user ${data.user_id}`)
    
        } catch (err) {
            console.error("❌ DB update error:", err.response?.data || err.message);
        }
    });
    socket.on('disconnect', () => {
        console.log('❌ Client disconnected:', socket.id);
    });
});

// 🔹 New Schedule Booking
app.post('/emit/new-schedule-booking', (req, res) => {
    try {
        const { booking, drivers } = req.body;
        console.log("📩 Incoming body:", req.body);

        if (!booking || !drivers) {
            return res.status(400).json({ error: 'Booking or drivers missing' });
        }

        // Bas ek dafa emit karo
        io.emit("newScheduleBooking", {
            booking,
            drivers,
        });

        console.log("📢 New schedule booking emitted once to all drivers:", drivers.map(d => d.id));
        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/new-schedule-booking:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/new-choose-booking', (req, res) => {
    try {
        const { booking, driver_id, user_id } = req.body;

        if (!booking || !driver_id) {
            return res.status(400).json({ error: 'Missing booking or driver_id' });
        }

        // Sirf ek driver ko emit karo
        io.to(`driver_${driver_id}`).emit("newChooseBooking", {
            booking,
            user_id
        });

        console.log(`📢 ChooseTruck booking emitted to driver ${driver_id} from ${user_id}`);
        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/new-choose-booking:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});

// 🔹 Driver Response
app.post('/emit/driver-response', (req, res) => {
    try {
        const { booking, driver, status } = req.body;

        if (!booking || !driver || !status) {
            return res.status(400).json({ error: 'Missing required fields' });
        }

        io.emit(`driverResponse`, { booking, driver, status });
        console.log(`📢 Driver ${booking.driver_id} responded with ${status} for booking ${booking.id}`);

        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/driver-response:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/booking-cancelled', (req, res) => {
  const { booking_id, cancelled_by, cancelled_by_role, cancel_reason } = req.body;

  if (!booking_id || !cancelled_by || !cancelled_by_role) {
    return res.status(400).json({ error: 'Missing required fields' });
  }

  io.emit("bookingCancelled", { booking_id, cancelled_by, cancelled_by_role, cancel_reason });

  console.log(`🚫 Booking ${booking_id} cancelled by ${cancelled_by_role} (${cancelled_by}) Reason: ${cancel_reason}`);

  res.json({ success: true });
});
// 🔹 Booking Closed
app.post('/emit/booking-closed', (req, res) => {
    try {
        const { booking_id, driver_id, status } = req.body;  // 👈 safe destructure
        
        
        if (!booking_id || !status) {
            return res.status(400).json({ error: 'Missing required fields' });
        }

        io.emit("bookingClosed", { booking_id, driver_id, status });

        console.log(`🚫 Booking closed event emitted for booking ${booking_id} with status ${status}`);

        res.json({ success: true });
    } catch (err) {
        console.error("❌ Error in /emit/booking-closed:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/schedule-reminder', (req, res) => {
    try {
        const { booking_id, user_id, driver_id, ride_time, message } = req.body;

        io.emit("scheduleReminder", {
            booking_id,
            user_id,
            driver_id,
            ride_time,
            message
        });

        console.log(`⏰ Reminder emitted for booking ${booking_id}`);
        res.json({ success: true });
    } catch (err) {
        console.error("❌ Error in /emit/schedule-reminder:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
// 🔹 Server start
const PORT = process.env.SERVER_PORT;
server.listen(PORT, () => {
    console.log(`🚀 Socket.IO running on port ${PORT} (${process.env.NODE_ENV})`);
});
