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

const sanitizeToken = (token = '') => {
    const normalized = normalizeBearerToken(String(token || '').trim());

    if (!normalized) {
        return '';
    }

    const lowered = normalized.toLowerCase();
    if (lowered === 'undefined' || lowered === 'null' || lowered === 'false' || lowered === 'nan') {
        return '';
    }

    return normalized;
};

const getSocketToken = (socket, data = {}) => {
    const rawToken = data.token
        || data.access_token
        || data.accessToken
        || data.authToken
        || data.Authorization
        || data.authorization
        || socket.handshake.auth?.token
        || socket.handshake.auth?.accessToken
        || socket.handshake.query?.token
        || socket.handshake.headers.authorization
        || socket.handshake.headers.Authorization
        || socket.token
        || '';

    return sanitizeToken(rawToken);
};

const toFixedOrNull = (value, digits = 2) => {
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed.toFixed(digits) : null;
};

const formatDistance = (value) => {
    const parsed = Number.parseFloat(value);

    if (!Number.isFinite(parsed)) {
        return null;
    }

    return parsed.toFixed(Math.abs(parsed) < 1 ? 4 : 2);
};

const resolveDistanceForDriver = (driver = {}) => {
    const kmNumeric = Number.parseFloat(driver?.distance_km ?? driver?.distance);

    if (!Number.isFinite(kmNumeric)) {
        return { km: null, miles: null, meters: null };
    }

    const milesNumeric = kmNumeric * 0.621371;

    return {
        km: formatDistance(kmNumeric),
        miles: formatDistance(milesNumeric),
        meters: toFixedOrNull(kmNumeric * 1000, 1),
    };
};

const calculateDistanceKm = (lat1, lng1, lat2, lng2) => {
    const pLat1 = Number.parseFloat(lat1);
    const pLng1 = Number.parseFloat(lng1);
    const pLat2 = Number.parseFloat(lat2);
    const pLng2 = Number.parseFloat(lng2);

    if (![pLat1, pLng1, pLat2, pLng2].every(Number.isFinite)) {
        return null;
    }

    const earthRadius = 6371;
    const dLat = (pLat2 - pLat1) * (Math.PI / 180);
    const dLng = (pLng2 - pLng1) * (Math.PI / 180);

    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(pLat1 * (Math.PI / 180))
        * Math.cos(pLat2 * (Math.PI / 180))
        * Math.sin(dLng / 2) * Math.sin(dLng / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return earthRadius * c;
};

const resolveBookingDriverDistance = (booking = {}, driver = {}) => {
    const calculatedKm = calculateDistanceKm(
        booking?.lat,
        booking?.lng,
        driver?.current_lat,
        driver?.current_lng
    );

    if (Number.isFinite(calculatedKm)) {
        return {
            km: formatDistance(calculatedKm),
            miles: formatDistance(calculatedKm * 0.621371),
            meters: toFixedOrNull(calculatedKm * 1000, 1),
        };
    }

    return resolveDistanceForDriver(driver);
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

    socket.on("registerUser", (data = {}) => {
        const userId = data.userId ?? data.user_id ?? data.id;
        const role = String(data.role ?? data.userRole ?? '').trim().toLowerCase();

        if (!userId) {
            console.log(`❌ registerUser missing userId/user_id in payload: ${JSON.stringify(data)}`);
            return;
        }

        if (role === 'driver') {
            socket.join(`driver_${userId}`);
            console.log(`🚚 Driver ${userId} joined room driver_${userId}`);
            socket.emit('registerUserAck', { user_id: userId, role: 'driver', room: `driver_${userId}` });
        } else if (role === 'user') {
            socket.join(`user_${userId}`);
            console.log(`👤 User ${userId} joined room user_${userId}`);
            socket.emit('registerUserAck', { user_id: userId, role: 'user', room: `user_${userId}` });
        } else {
            console.log(`❌ Unknown role: ${role || 'N/A'} for userId: ${userId}`);
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
            if (err.response?.status === 401) {
                console.error("❌ DB update error: unauthenticated token for updateLocation event");
            } else {
                console.error("❌ DB update error:", err.response?.data || err.message);
            }
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
            const distanceKmRaw = calculateDistanceKm(data.current_lat, data.current_lng, userLat, userLng);
            const distanceKm = Number.isFinite(distanceKmRaw) ? formatDistance(distanceKmRaw) : null;
            const distanceMiles = Number.isFinite(distanceKmRaw)
                ? formatDistance(distanceKmRaw * 0.621371)
                : null;
            const distanceMeters = Number.isFinite(distanceKmRaw)
                ? toFixedOrNull(distanceKmRaw * 1000, 1)
                : null;
    
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

                distance_km: distanceKm,
                distance_miles: distanceMiles,
                distance_meters: distanceMeters,
                user_distance_km: distanceKm,
                user_distance_miles: distanceMiles,
                user_distance_meters: distanceMeters,
    
                username: userDetailPayload?.name ?? null,
                useravatar: userDetailPayload?.avatar ?? null,
                drivername: driverDetailPayload?.name ?? null,
                driveravatar: driverDetailPayload?.avatar ?? null
            };
    
            console.log('📤 Emitting driverNewLocation:', JSON.stringify(emitData, null, 2));
    
            io.to(`booking_${data.booking_id}`).emit("driverNewLocation", emitData);
    
            console.log(`📡 Location update sent for booking_${data.booking_id}`);
        } catch (err) {
            if (err.response?.status === 401) {
                console.error("❌ Tracking error: unauthenticated token for trackingDriver event");
            } else {
                console.error("❌ Tracking error:", err.response?.data || err.message);
            }
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
        const { type = 'schedule', booking, drivers } = req.body;

        const normalizedDrivers = (Array.isArray(drivers) ? drivers : [])
            .filter((driver) => driver?.id !== undefined && driver?.id !== null);

        if (!booking || normalizedDrivers.length === 0) {
            return res.status(400).json({ error: 'Booking or drivers missing' });
        }

        normalizedDrivers.forEach((driver) => {
            const driverId = driver.id;
            const { km, miles, meters } = resolveBookingDriverDistance(booking, driver);
            const roomName = `driver_${driverId}`;
            const roomSize = io.sockets.adapter.rooms.get(roomName)?.size ?? 0;

            if (roomSize === 0) {
                console.log(`⚠️ No active sockets in ${roomName} for schedule booking ${booking.id}`);
            }

            const bookingPayload = {
                ...booking,
                user_distance_km: km,
                user_distance_miles: miles,
                user_distance_meters: meters,
            };

            io.to(roomName).emit("newBooking", {
                type,
                booking: bookingPayload,
                driver_id: driverId,
                status: booking?.status ?? 'Pending',
            });
        });

        console.log(`📢 Schedule booking emitted to ${normalizedDrivers.length} drivers:`, booking.id);
        res.json({ success: true, sent_to: normalizedDrivers.length });

    } catch (err) {
        console.error("❌ Error in /emit/new-schedule-booking:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/new-choose-booking', (req, res) => {
    try {
        const { type = 'choose', booking, driver_id, name, drivers, selected_driver } = req.body;

        const normalizedDrivers = Array.isArray(drivers)
            ? drivers
            : (selected_driver ? [selected_driver] : []);

        const normalizedSelectedDriver = selected_driver
            || normalizedDrivers.find((driver) => driver?.id === driver_id)
            || normalizedDrivers[0]
            || null;

        const emittedDriverId = normalizedSelectedDriver?.id ?? driver_id;
        const { km, miles, meters } = resolveBookingDriverDistance(booking, normalizedSelectedDriver || {});
        const roomName = `driver_${emittedDriverId}`;
        const roomSize = io.sockets.adapter.rooms.get(roomName)?.size ?? 0;

        if (roomSize === 0) {
            console.log(`⚠️ No active sockets in ${roomName} for choose booking ${booking.id}`);
        }

        const bookingPayload = {
            ...booking,
            user_distance_km: km,
            user_distance_miles: miles,
            user_distance_meters: meters,
        };

        if (!booking || !driver_id) {
            return res.status(400).json({ error: 'Missing booking or driver_id' });
        }
        io.to(roomName).emit("newBooking", {
            type,
            booking: bookingPayload,
            driver_id: emittedDriverId,
            name,
            status: booking?.status ?? 'Pending',
        });

        console.log(`📢 Choose booking emitted to driver ${emittedDriverId}`);
        res.json({ success: true });

    } catch (err) {
        console.error("❌ Error in /emit/new-choose-booking:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/new-instant-booking', (req, res) => {
    try {
        const { type = 'instant', booking, drivers, driver_ids, status } = req.body;

        const normalizedDrivers = Array.isArray(drivers)
            ? drivers
            : (Array.isArray(driver_ids) ? driver_ids : []);

        const deliverableDrivers = normalizedDrivers
            .filter((driver) => driver?.id !== undefined && driver?.id !== null);

        if (!booking || deliverableDrivers.length === 0) {
            return res.status(400).json({ error: 'Booking or driver_ids missing' });
        }

        const targetRooms = [];

        deliverableDrivers.forEach((driver) => {
            const driverId = driver.id;
            const { km, miles, meters } = resolveBookingDriverDistance(booking, driver);
            const roomName = `driver_${driverId}`;
            const roomSize = io.sockets.adapter.rooms.get(roomName)?.size ?? 0;
            targetRooms.push({ driver_id: driverId, room: roomName, connected: roomSize > 0, sockets: roomSize });

            if (roomSize === 0) {
                console.log(`⚠️ No active sockets in ${roomName} for instant booking ${booking.id}`);
            }

            const bookingPayload = {
                ...booking,
                user_distance_km: km,
                user_distance_miles: miles,
                user_distance_meters: meters,
            };

            io.to(roomName).emit("newBooking", {
                type,
                booking: bookingPayload,
                driver_id: driverId,
                status,
            });
        });

        console.log(`📢 Instant booking sent to ${deliverableDrivers.length} drivers`, targetRooms);
        res.json({ success: true, sent_to: deliverableDrivers.length });

    } catch (err) {
        console.error("❌ Error in /emit/new-instant-booking:", err.message);
        res.status(500).json({ error: "Internal server error" });
    }
});
app.post('/emit/driver-response', (req, res) => {
    try {
        const { booking, driver, user, status, distance_km, distance_miles } = req.body;

        if (!booking || !driver || !status) {
            return res.status(400).json({ error: 'Missing required fields' });
        }

        const liveDistanceKm = calculateDistanceKm(
            booking?.lat,
            booking?.lng,
            driver?.current_lat,
            driver?.current_lng
        );
        const fallbackDistanceKm = Number.parseFloat(distance_km);
        const fallbackDistanceMiles = Number.parseFloat(distance_miles);

        const resolvedDistanceKmNumeric = Number.isFinite(liveDistanceKm)
            ? liveDistanceKm
            : (Number.isFinite(fallbackDistanceKm) ? fallbackDistanceKm : null);

        const resolvedDistanceMilesNumeric = Number.isFinite(resolvedDistanceKmNumeric)
            ? resolvedDistanceKmNumeric * 0.621371
            : (Number.isFinite(fallbackDistanceMiles) ? fallbackDistanceMiles : null);

        const payload = {
            booking,
            driver,
            user: user ?? null,
            status,
            distance_km: formatDistance(resolvedDistanceKmNumeric),
            distance_miles: formatDistance(resolvedDistanceMilesNumeric),
            distance_meters: toFixedOrNull(
                Number.isFinite(resolvedDistanceKmNumeric) ? resolvedDistanceKmNumeric * 1000 : null,
                1
            ),
            user_distance_km: formatDistance(resolvedDistanceKmNumeric),
            user_distance_miles: formatDistance(resolvedDistanceMilesNumeric),
            user_distance_meters: toFixedOrNull(
                Number.isFinite(resolvedDistanceKmNumeric) ? resolvedDistanceKmNumeric * 1000 : null,
                1
            ),
        };

        if (booking?.driver_id) {
            io.to(`driver_${booking.driver_id}`).emit('driverResponse', payload);
        }

        if (booking?.user_id) {
            io.to(`user_${booking.user_id}`).emit('driverResponse', payload);
        }

        if (!booking?.driver_id && !booking?.user_id) {
            io.emit('driverResponse', payload);
        }

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
