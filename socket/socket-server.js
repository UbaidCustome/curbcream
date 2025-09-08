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

    socket.on('updateLocation', async (data) => {
        console.log("📍 Location update:", data);

        try {
            const res = await axios.post(`${process.env.LARAVEL_API}/driver/update-location`, {
                current_lat: data.current_lat,
                current_lng: data.current_lng
            }, {
                headers: { Authorization: `Bearer ${data.token}` }
            });

            console.log("✅ DB update success:", res.data);

            io.emit(`driverLocationUpdated:${data.driver_id}`, {
                current_lat: data.current_lat,
                current_lng: data.current_lng
            });

        } catch (err) {
            if (err.response) {
                console.error("❌ DB update error:", err.response.status, err.response.data);
            } else {
                console.error("❌ DB update error:", err.message);
            }
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

        drivers.forEach(driver => {
            io.emit(`newScheduleBooking`, {
                booking,
                driver_id: driver.id
            });
        });

        console.log("📢 New schedule booking emitted to drivers:", drivers.map(d => d.id));
        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/new-schedule-booking:", err.message);
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
        console.log(`📢 Driver ${driver.id} responded with ${status} for booking ${booking.id}`);

        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/driver-response:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});

// 🔹 Booking Closed
app.post('/emit/booking-closed', (req, res) => {
    try {
        const { booking_id, status } = req.body;

        if (!booking_id) {
            return res.status(400).json({ error: 'Missing required fields' });
        }

        io.emit(`bookingClosed`, { booking_id, status });
        console.log(`🚫 Booking closed event emitted for booking ${booking_id}`);

        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/booking-closed:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});

// 🔹 Server start
const PORT = process.env.SERVER_PORT || 3010;
server.listen(PORT, () => {
    console.log(`🚀 Socket.IO running on port ${PORT} (${process.env.NODE_ENV})`);
});
