CREATE TABLE iss_data (
  id SERIAL PRIMARY KEY,
  latitude FLOAT,
  longitude FLOAT,
  altitude FLOAT,
  velocity FLOAT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);  
