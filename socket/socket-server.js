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

app.get("/", (req, res) => {
    res.send("✅ Socket Server Running with SSL...");
});

// 🔹 Server setup
let server;
if (process.env.NODE_ENV === "production") {
    const options = {
        key: fs.readFileSync("/home2/apimyprojectstag/ssl/keys/f5d77_a46eb_6ddaf64f5ae4efb78473166435282d66.key"),
        cert: fs.readFileSync("/home2/apimyprojectstag/ssl/certs/api_myprojectstaging_com_f5d77_a46eb_1781174562_f346f7aff4cd60a98deb099acc827de1.crt"),
    };
    server = https.createServer(options, app);
} else {
    server = http.createServer(app);
}

const io = socketIo(server, {
    transports: ["websocket", "polling"],
    allowEIO3: true,
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
        credentials: true
    },
});

const normalizeBearerToken = (token = '') => token.replace(/^Bearer\s+/i, '').trim();

const getSocketToken = (socket, data = {}) => {
    const rawToken = socket.handshake.headers.authorization
        || socket.handshake.headers.Authorization
        || data.token
        || '';

    return normalizeBearerToken(rawToken);
};

io.use((socket, next) => {
    const handshake = socket.handshake;
    // console.log("Handshake => ",handshake);
    const token = getSocketToken(socket);

    if (!token) {
        console.log("⚠️ No Authorization header found");
    } else {
        console.log("🔐 Token from headers:", token);
        socket.token = token; // store for later use
    }
    next();
});


io.on('connection', (socket) => {
    console.log('✅ Client connected:', socket.id);
    // console.log('🔐 Client auth:', socket.handshake.auth);

    const token = getSocketToken(socket);
    console.log('Socket Connected with token',token);
    if (!token) {
        console.log('⚠️  Client connected without token');
    }

    socket.on("registerUser", (data) => {
        const { userId, role } = data;

        if (role === 'driver') {
            socket.join(`driver_${userId}`);
            console.log(`🚚 Driver ${userId} joined room driver_${userId}`);
        } else if (role === 'user') {
            socket.join(`user_${userId}`);
            console.log(`👤 User ${userId} joined room user_${userId}`);
        } else {
            console.log(`❌ Unknown role: ${role} for userId: ${userId}`);
        }
    });

    socket.on("joinBookingRoom", (data) => {
        const room = `booking_${data.booking_id}`;
        socket.join(room);
        console.log(`✅ ${data.role} ${data.user_id} joined ${room}`);
    });    
    socket.on('updateLocation', async (data) => {
        // console.log("📍 Location update:", data);
    
        try {
            const token = getSocketToken(socket, data);

            if (!token) {
                console.log("⚠️ Missing token in updateLocation event");
                return;
            }
            
            const res = await axios.post(`${process.env.LARAVEL_API}/driver/update-location`, {
                current_lat: data.current_lat,
                current_lng: data.current_lng
            }, {
                headers: { 
                    Authorization: `Bearer ${token}`
                }
            });
    
            // console.log("✅ DB update success:", res.data);
    
            io.emit(`driverLocationUpdated:${data.user_id}`, {
                current_lat: data.current_lat,
                current_lng: data.current_lng
            });
            // console.log(`Location Update for user ${data.user_id}`)
    
        } catch (err) {
            console.error("❌ DB update error:", err.response?.data || err.message);
        }
    });
    socket.on("trackingDriver", async (data) => {
        console.log("🚚 Driver tracking update:", data);
    
        try {
            const token = getSocketToken(socket, data);

            if (!token) {
                console.log("⚠️ Missing token in trackingDriver event");
                return;
            }
    
            console.log('driver_token', token);
    
            const headers = { 
                Authorization: `Bearer ${token}`,
                Accept: 'application/json'
            };
    
            // ✅ Step 1: Update driver location (needs token)
            const userUpdate = await axios.post(
                `${process.env.LARAVEL_API}/driver/update-location`, 
                {
                    current_lat: data.current_lat,
                    current_lng: data.current_lng
                }, 
                { headers }
            );
            console.log("✅ Location update response:", userUpdate.data);
    
            // ✅ Step 2: Get user location (no auth required)
            const userResponse = await axios.get(
                `${process.env.LARAVEL_API}/user/${data.user_id}/location`,
                { headers }
            );
            console.log("✅ User location:", userResponse.data);
    
            const userLocation = userResponse.data?.data ?? userResponse.data;
            const userLat = userLocation?.lat ?? userLocation?.current_lat ?? null;
            const userLng = userLocation?.lng ?? userLocation?.current_lng ?? null;
    
            // ✅ Step 3: Get user details (no headers)
            const userDetail = await axios.get(
                `${process.env.LARAVEL_API}/user-detail/${data.user_id}`,
                { headers } // token ke sath
            );
    
            // ✅ Step 4: Get driver details (no headers)
            const driverDetail = await axios.get(
                `${process.env.LARAVEL_API}/driver-detail/${data.driver_id}`,
                { headers }
            );

            const userDetailPayload = userDetail.data?.data ?? userDetail.data;
            const driverDetailPayload = driverDetail.data?.data ?? driverDetail.data;
    
            // ✅ Emit to booking room
            const emitData = {
                booking_id: data.booking_id,
                driver_id: data.driver_id,
                user_id: data.user_id,
                driver_lat: data.current_lat,
                driver_lng: data.current_lng,
                user_lat: userLat,
                user_lng: userLng,
    
                username: userDetailPayload?.name ?? null,
                useravatar: userDetailPayload?.avatar ?? null,
                drivername: driverDetailPayload?.name ?? null,
                driveravatar: driverDetailPayload?.avatar ?? null
            };
    
            console.log('📤 Emitting driverNewLocation:', JSON.stringify(emitData, null, 2));
    
            io.to(`booking_${data.booking_id}`).emit("driverNewLocation", emitData);
    
            console.log(`📡 Location update sent for booking_${data.booking_id}`);
        } catch (err) {
            console.error("❌ Tracking error:", err.response?.data || err.message);
        }
    });

    socket.on('disconnect', () => {
        console.log('❌ Client disconnected:', socket.id);
    });
});
app.post('/emit/direct-join-room', async (req, res) => {
    try {
        const { booking_id, driver_id, user_id } = req.body;

        if (!booking_id || !driver_id || !user_id) {
            return res.status(400).json({ error: 'Missing required fields' });
        }

        const bookingRoom = `booking_${booking_id}`;

        const driverSockets = await io.in(`driver_${driver_id}`).fetchSockets();
        driverSockets.forEach(socket => {
            socket.join(bookingRoom);
            console.log(`✅ Driver ${driver_id} (socket: ${socket.id}) joined ${bookingRoom}`);
        });

        const userSockets = await io.in(`user_${user_id}`).fetchSockets();
        userSockets.forEach(socket => {
            socket.join(bookingRoom);
            console.log(`✅ User ${user_id} (socket: ${socket.id}) joined ${bookingRoom}`);
        });

        if (driverSockets.length === 0) {
            console.log(`⚠️ Driver ${driver_id} is not connected`);
        }
        if (userSockets.length === 0) {
            console.log(`⚠️ User ${user_id} is not connected`);
        }

        res.json({ 
            success: true, 
            message: `Both users directly joined booking room ${bookingRoom}`,
            driver_connected: driverSockets.length > 0,
            user_connected: userSockets.length > 0
        });

    } catch (err) {
        console.error("❌ Error in /emit/direct-join-room:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/start-tracking', (req, res) => {
    const { booking_id, driver_id, user_id } = req.body;

    io.to(`booking_${booking_id}`).emit("startTracking", {
        booking_id,
        driver_id,
        user_id,
    });

    console.log(`📡 Tracking started for booking_${booking_id}`);
    res.json({ success: true });
});
// 🔹 Ride Completed Event
app.post('/emit/ride-completed', (req, res) => {
    try {
        const { booking_id, driver_id, user_id, completed_at,drivername,username,status } = req.body;

        // ✅ Dono clients ko inform karo
        io.to(`booking_${booking_id}`).emit("rideCompleted", {
            booking_id: booking_id,
            driver_id: driver_id,
            user_id: user_id,
            completed_at: completed_at,
            drivername:drivername,
            username:username,
            message: "Ride completed successfully! Thank you for using our service.",
            status:status
        });

        // ✅ Booking room ko close karo (optional)
        io.in(`booking_${booking_id}`).socketsLeave([]);

        console.log(`🎉 Ride completed for booking_${booking_id}`);
        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/ride-completed:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/new-schedule-booking', (req, res) => {
    try {
        const { type, booking, drivers, driver_id_list } = req.body;

        const normalizedDrivers = Array.isArray(drivers) ? drivers : [];
        const normalizedDriverIdList = Array.isArray(driver_id_list)
            ? driver_id_list
            : normalizedDrivers.map((driver) => driver?.id).filter((id) => id !== undefined && id !== null);

        if (!booking || normalizedDrivers.length === 0) {
            return res.status(400).json({ error: 'Booking or drivers missing' });
        }

        io.emit("newBooking", {
            type,
            booking,
            drivers: normalizedDrivers,
            driver_id_list: normalizedDriverIdList,
        });

        console.log("📢 Schedule booking emitted:", booking.id);
        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/new-schedule-booking:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/new-choose-booking', (req, res) => {
    try {
        const { type, booking, driver_id, name, drivers, selected_driver, driver_id_list } = req.body;

        const normalizedDrivers = Array.isArray(drivers)
            ? drivers
            : (selected_driver ? [selected_driver] : []);

        const normalizedDriverIdList = Array.isArray(driver_id_list)
            ? driver_id_list
            : normalizedDrivers
                .map((driver) => driver?.id)
                .concat(driver_id)
                .filter((id, index, self) => id !== undefined && id !== null && self.indexOf(id) === index);

        const normalizedSelectedDriver = selected_driver
            || normalizedDrivers.find((driver) => driver?.id === driver_id)
            || normalizedDrivers[0]
            || null;

        if (!booking || !driver_id) {
            return res.status(400).json({ error: 'Missing booking or driver_id' });
        }
        io.to(`driver_${driver_id}`).emit("newBooking", {
            type,
            booking,
            driver_id,
            name,
            drivers: normalizedDrivers,
            selected_driver: normalizedSelectedDriver,
            driver_id_list: normalizedDriverIdList,
        });

        console.log(`📢 Choose booking emitted to driver ${driver_id}`);
        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/new-choose-booking:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/new-instant-booking', (req, res) => {
    try {
        const { type, booking, drivers, driver_ids, driver_id_list, status } = req.body;

        const normalizedDrivers = Array.isArray(drivers)
            ? drivers
            : (Array.isArray(driver_ids) ? driver_ids : []);

        const normalizedDriverIdList = Array.isArray(driver_id_list)
            ? driver_id_list
            : normalizedDrivers.map((driver) => driver?.id).filter((id) => id !== undefined && id !== null);

        if (!booking || normalizedDrivers.length === 0) {
            return res.status(400).json({ error: 'Booking or driver_ids missing' });
        }

        io.emit("newBooking", {
            type,
            booking,
            drivers: normalizedDrivers,
            driver_ids: normalizedDrivers,
            driver_id_list: normalizedDriverIdList,
            status
        });

        console.log(`📢 Instant booking sent to ${normalizedDrivers.length} drivers`);
        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/new-instant-booking:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
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
app.post('/emit/booking-closed', (req, res) => {
    try {
        const { booking_id, driver_id, status } = req.body;
        
        
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
app.post('/emit/dispute-raised', (req, res) => {
    try {
        const { driver_id, user_id, booking_id, reason, description,username,drivername,status } = req.body;
        if (!driver_id || !booking_id) {
            return res.status(400).json({ error: 'Missing required fields' });
        }

        // Emit only to that driver’s room
        io.to(`driver_${driver_id}`).emit("disputeRaised", {
            driverid:driver_id,
            userid:user_id,
            booking:booking_id,
            reason:reason,
            description:description,
            username: username,
            drivername: drivername,
            status:status
        });

        console.log(`⚠️ Dispute raised against driver ${driver_id} for booking ${booking_id}`);

        res.json({ success: true });
    } catch (err) {
        console.error("❌ Error in /emit/dispute-raised:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
const PORT = process.env.SERVER_PORT;
server.listen(PORT, () => {
    console.log(`🚀 Socket.IO running on port ${PORT} (${process.env.NODE_ENV})`);
});
