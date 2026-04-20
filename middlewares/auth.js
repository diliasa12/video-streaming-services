import jwt from "jsonwebtoken";
const authMiddleware = (req, res, next) => {
  const token = req.headers.authorization?.split(" ")[1];
  if (!token)
    return res
      .status(401)
      .json({ success: false, message: "token not provided" });

  try {
    const decode = jwt.verify(token, process.env.SECRET_KEY);
    req.headers["X-User-Id"] = decoded.id;
    req.headers["X-User-Role"] = decoded.role;
    req.headers["X-User-Email"] = decoded.email;
    next();
  } catch (err) {
    return res.status(500).json({ message: err.message });
  }
};
export default authMiddleware;
