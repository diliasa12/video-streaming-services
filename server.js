import e from "express";
import { createProxyMiddleware } from "http-proxy-middleware";
import { jwtMiddleware } from "./middlewares/auth.js";
import "dotenv/config";
import authRoute from "./routes/auth.route.js";
const app = e();
const PORT = 3000;
app.use(e.json());
app.use("/api/auth", authRoute);
app.use(
  "/service1",
  jwtMiddleware,
  createProxyMiddleware({
    target: "http://localhost:3001",
    changeOrigin: true,
    pathRewrite: {
      "^/service1": "",
    },
  }),
);
app.use(
  "/service2",
  jwtMiddleware,
  createProxyMiddleware({
    target: "http://localhost:3002",
    changeOrigin: true,
    pathRewrite: {
      "^/service2": "",
    },
  }),
);
app.listen(PORT, () => {
  console.log(`API Gateaway berjlaan di http://localhost:${PORT}`);
});
