import jwt from "jsonwebtoken";
import crypto from "crypto";
import axios from "axios";
import { User, RefreshToken } from "../models/User.js";
// ─────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────
const issueAccessToken = (user) =>
  jwt.sign(
    { id: user.id, email: user.email, role: user.role },
    process.env.JWT_SECRET,
    { expiresIn: process.env.JWT_EXPIRES_IN || "15m" },
  );

const issueRefreshToken = () => crypto.randomBytes(64).toString("hex");

// Propagasi user mirror ke semua service (fire & forget)
const propagateUser = async (user) => {
  const secret = process.env.INTERNAL_SECRET;
  const headers = { "X-Internal-Secret": secret };

  const services = [
    {
      url: `${process.env.VIDEO_SERVICE_URL}/internal/users`,
      body: {
        _id: user.id,
        name: user.name,
        avatar_url: user.avatar_url,
        role: user.role,
      },
    },
    {
      url: `${process.env.COURSE_SERVICE_URL}/internal/users`,
      body: {
        id: user.id,
        name: user.name,
        email: user.email,
        role: user.role,
      },
    },
  ];

  await Promise.allSettled(
    services.map(({ url, body }) =>
      axios
        .post(url, body, { headers, timeout: 3000 })
        .catch((err) =>
          console.warn(`[propagate] Gagal ke ${url}:`, err.message),
        ),
    ),
  );
};

// ─────────────────────────────────────────────
// POST /auth/register
// ─────────────────────────────────────────────
export const register = async (req, res) => {
  try {
    const { name, email, password, role } = req.body;

    // Validasi manual — tidak pakai library tambahan
    const errors = {};
    if (!name) errors.name = "Name wajib diisi.";
    if (!email) errors.email = "Email wajib diisi.";
    if (!password) errors.password = "Password wajib diisi.";
    if (password && password.length < 8)
      errors.password = "Password minimal 8 karakter.";

    if (Object.keys(errors).length > 0) {
      return res.status(422).json({ success: false, errors });
    }

    const exists = await User.emailExists(email);
    if (exists) {
      return res.status(409).json({
        success: false,
        message: "Email sudah terdaftar.",
      });
    }

    const user = await User.create({ name, email, password, role });

    const accessToken = issueAccessToken(user);
    const refreshToken = issueRefreshToken();
    await RefreshToken.create(
      user.id,
      refreshToken,
      Number(process.env.JWT_REFRESH_EXPIRES_DAYS) || 30,
    );

    // Sync ke semua service (tidak blocking)
    propagateUser(user);

    return res.status(201).json({
      success: true,
      message: "Registrasi berhasil.",
      data: {
        user,
        access_token: accessToken,
        refresh_token: refreshToken,
        token_type: "Bearer",
        expires_in: 900,
      },
    });
  } catch (err) {
    console.error("[register]", err);
    return res
      .status(500)
      .json({ success: false, message: "Internal server error." });
  }
};

// ─────────────────────────────────────────────
// POST /auth/login
// ─────────────────────────────────────────────
export const login = async (req, res) => {
  try {
    const { email, password } = req.body;

    if (!email || !password) {
      return res.status(422).json({
        success: false,
        message: "Email dan password wajib diisi.",
      });
    }

    const user = await User.findByEmail(email);

    // Gunakan pesan yang sama untuk mencegah user enumeration
    const invalidMsg = "Email atau password salah.";

    if (!user) {
      return res.status(401).json({ success: false, message: invalidMsg });
    }

    const valid = await User.verifyPassword(password, user.password);
    if (!valid) {
      return res.status(401).json({ success: false, message: invalidMsg });
    }

    // Buang password sebelum dikirim ke client
    const { password: _, ...safeUser } = user;

    const accessToken = issueAccessToken(safeUser);
    const refreshToken = issueRefreshToken();
    await RefreshToken.create(
      safeUser.id,
      refreshToken,
      Number(process.env.JWT_REFRESH_EXPIRES_DAYS) || 30,
    );

    return res.json({
      success: true,
      message: "Login berhasil.",
      data: {
        user: safeUser,
        access_token: accessToken,
        refresh_token: refreshToken,
        token_type: "Bearer",
        expires_in: 900,
      },
    });
  } catch (err) {
    console.error("[login]", err);
    return res
      .status(500)
      .json({ success: false, message: "Internal server error." });
  }
};

// ─────────────────────────────────────────────
// POST /auth/refresh
// ─────────────────────────────────────────────
export const refresh = async (req, res) => {
  try {
    const { refresh_token } = req.body;

    if (!refresh_token) {
      return res
        .status(422)
        .json({ success: false, message: "refresh_token wajib diisi." });
    }

    const userId = await RefreshToken.verify(refresh_token);
    if (!userId) {
      return res.status(401).json({
        success: false,
        message: "Refresh token tidak valid atau sudah expired.",
      });
    }

    const user = await User.findById(userId);
    if (!user) {
      return res
        .status(401)
        .json({ success: false, message: "User tidak ditemukan." });
    }

    // Rotate — revoke token lama, buat token baru
    await RefreshToken.revoke(refresh_token);
    const newRefreshToken = issueRefreshToken();
    await RefreshToken.create(
      userId,
      newRefreshToken,
      Number(process.env.JWT_REFRESH_EXPIRES_DAYS) || 30,
    );

    return res.json({
      success: true,
      data: {
        access_token: issueAccessToken(user),
        refresh_token: newRefreshToken,
        token_type: "Bearer",
        expires_in: 900,
      },
    });
  } catch (err) {
    console.error("[refresh]", err);
    return res
      .status(500)
      .json({ success: false, message: "Internal server error." });
  }
};

// ─────────────────────────────────────────────
// POST /auth/logout
// ─────────────────────────────────────────────
export const logout = async (req, res) => {
  try {
    const { refresh_token } = req.body;

    if (refresh_token) {
      await RefreshToken.revoke(refresh_token);
    }

    return res.json({ success: true, message: "Logout berhasil." });
  } catch (err) {
    console.error("[logout]", err);
    return res
      .status(500)
      .json({ success: false, message: "Internal server error." });
  }
};

// ─────────────────────────────────────────────
// POST /auth/logout-all  (logout semua device)
// ─────────────────────────────────────────────
export const logoutAll = async (req, res) => {
  try {
    // req.user di-set oleh jwtMiddleware
    await RefreshToken.revokeAll(req.user.id);
    return res.json({
      success: true,
      message: "Logout dari semua device berhasil.",
    });
  } catch (err) {
    console.error("[logout-all]", err);
    return res
      .status(500)
      .json({ success: false, message: "Internal server error." });
  }
};

// ─────────────────────────────────────────────
// GET /auth/me
// ─────────────────────────────────────────────
export const me = async (req, res) => {
  try {
    const user = await User.findById(req.user.id);
    if (!user) {
      return res
        .status(404)
        .json({ success: false, message: "User tidak ditemukan." });
    }
    return res.json({ success: true, data: user });
  } catch (err) {
    console.error("[me]", err);
    return res
      .status(500)
      .json({ success: false, message: "Internal server error." });
  }
};
