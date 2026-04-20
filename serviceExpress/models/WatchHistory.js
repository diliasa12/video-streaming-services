import mongoose from "mongoose";

const watchHistorySchema = new mongoose.Schema({
  user_id: {
    type: mongoose.Schema.Types.ObjectId,
    ref: "User",
    required: true,
  },
  video_id: {
    type: mongoose.Schema.Types.ObjectId,
    ref: "Video",
    required: true,
  },
  watched_sec: { type: Number, default: 0 },
  completed: { type: Boolean, default: false },
  watched_at: { type: Date, default: Date.now },
});

// compound unique index: 1 user hanya punya 1 record per video
watchHistorySchema.index({ user_id: 1, video_id: 1 }, { unique: true });

module.exports = mongoose.model("WatchHistory", watchHistorySchema);
