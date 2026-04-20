import e from "express";
const app = e();
const PORT = 3001;
app.use(e.json());
app.get("/", (req, res) => {
  res.json({ success: true });
});
app.listen(PORT, () => {
  console.log(`Serivice Express berjalan di http://localhost:${PORT}`);
});
