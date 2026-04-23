import { Router } from "express";
import {
  register,
  login,
  refresh,
  logout,
  me,
  logoutAll,
} from "../controllers/authController.js";
import { jwtMiddleware } from "../middlewares/auth.js";
// Public
const router = Router();
router.post("/register", register);
router.post("/login", login);
router.post("/refresh", refresh);
router.post("/logout", logout);

// Protected (butuh token)
router.get("/me", jwtMiddleware, me);
router.post("/logout-all", jwtMiddleware, logoutAll);

export default router;
