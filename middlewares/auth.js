import jwt from "jsonwebtoken";

export const jwtMiddleware = (req, res, next) => {
  const authHeader = req.headers["authorization"];

  if (!authHeader || !authHeader.startsWith("Bearer ")) {
    return res.status(401).json({
      success: false,
      message: "Token tidak ditemukan. Sertakan Authorization: Bearer <token>.",
    });
  }

  const token = authHeader.split(" ")[1];

  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);

    req.user = decoded;

    req.headers["x-user-id"] = String(decoded.id);
    req.headers["x-user-role"] = decoded.role;
    req.headers["x-user-email"] = decoded.email;

    next();
  } catch (err) {
    if (err.name === "TokenExpiredError") {
      return res.status(401).json({
        success: false,
        message:
          "Token sudah expired. Gunakan refresh token untuk mendapatkan token baru.",
        code: "TOKEN_EXPIRED",
      });
    }
    return res.status(401).json({
      success: false,
      message: "Token tidak valid.",
    });
  }
};

/**
 * Cek role user.
 * Pakai setelah jwtMiddleware.
 *
 * Contoh:
 * router.post('/courses', jwtMiddleware, requireRole('instructor', 'admin'), handler)
 */
export const requireRole =
  (...roles) =>
  (req, res, next) => {
    if (!roles.includes(req.user?.role)) {
      return res.status(403).json({
        success: false,
        message: `Akses ditolak. Hanya untuk role: ${roles.join(", ")}.`,
      });
    }
    next();
  };
