const db = require("../config/db");
const bcrypt = require("bcryptjs");

export const User = {
  // ─────────────────────────────────────────────
  // CREATE
  // ─────────────────────────────────────────────
  async create({ name, email, password, role = "student", avatar_url = null }) {
    const hashed = await bcrypt.hash(password, 12);

    const [result] = await db.execute(
      `INSERT INTO users (name, email, password, role, avatar_url)
       VALUES (?, ?, ?, ?, ?)`,
      [name, email, hashed, role, avatar_url],
    );

    return this.findById(result.insertId);
  },

  // ─────────────────────────────────────────────
  // READ
  // ─────────────────────────────────────────────
  async findById(id) {
    const [rows] = await db.execute(
      `SELECT id, name, email, role, avatar_url, is_active, created_at, updated_at
       FROM users WHERE id = ? AND is_active = 1 LIMIT 1`,
      [id],
    );
    return rows[0] ?? null;
  },

  async findByEmail(email) {
    // Sertakan password — untuk verifikasi login
    const [rows] = await db.execute(
      `SELECT id, name, email, password, role, avatar_url, is_active
       FROM users WHERE email = ? AND is_active = 1 LIMIT 1`,
      [email],
    );
    return rows[0] ?? null;
  },

  async emailExists(email) {
    const [rows] = await db.execute(
      `SELECT id FROM users WHERE email = ? LIMIT 1`,
      [email],
    );
    return rows.length > 0;
  },

  // ─────────────────────────────────────────────
  // AUTH
  // ─────────────────────────────────────────────
  async verifyPassword(plain, hashed) {
    return bcrypt.compare(plain, hashed);
  },
};

export const RefreshToken = {
  async create(userId, token, days = 30) {
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + days);

    await db.execute(
      `INSERT INTO refresh_tokens (user_id, token, expires_at)
       VALUES (?, ?, ?)`,
      [userId, token, expiresAt],
    );
  },

  async verify(token) {
    const [rows] = await db.execute(
      `SELECT user_id FROM refresh_tokens
       WHERE token = ? AND revoked = 0 AND expires_at > NOW()
       LIMIT 1`,
      [token],
    );
    return rows[0]?.user_id ?? null;
  },

  async revoke(token) {
    await db.execute(`UPDATE refresh_tokens SET revoked = 1 WHERE token = ?`, [
      token,
    ]);
  },

  async revokeAll(userId) {
    await db.execute(
      `UPDATE refresh_tokens SET revoked = 1 WHERE user_id = ?`,
      [userId],
    );
  },
};
