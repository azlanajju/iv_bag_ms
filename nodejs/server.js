const express = require("express");
const app = express();
const port = 3000;

// Enable CORS for all routes
app.use((req, res, next) => {
  res.header("Access-Control-Allow-Origin", "*");
  res.header("Access-Control-Allow-Methods", "GET, POST, OPTIONS");
  res.header("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept");
  if (req.method === "OPTIONS") {
    return res.sendStatus(200);
  }
  next();
});

const initialWeight = 1;
let weight = initialWeight;
const decreaseRate = 0.01;

setInterval(() => {
  if (weight > 0) {
    weight = Math.max(0, weight - decreaseRate);
  } else {
    weight = initialWeight;
  }
}, 1000);

app.get("/data", (req, res) => {
  res.json({ weight: parseFloat(weight.toFixed(2)) });
});

app.listen(port, () => {
  console.log(`IV monitor mock server running at http://localhost:${port}/data`);
});
