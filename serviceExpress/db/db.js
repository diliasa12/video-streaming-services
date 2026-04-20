import mongoose from "mongoose";
const db = mongoose
  .connect(process.env.DB_URL_MONGO)
  .then(() => console.log("CONNECTED"))
  .catch((err) => console.log(err));
export default db;
