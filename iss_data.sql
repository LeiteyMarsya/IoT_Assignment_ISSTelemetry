CREATE TABLE iss_data (
  id SERIAL PRIMARY KEY,
  latitude FLOAT,
  longitude FLOAT,
  altitude FLOAT,
  velocity FLOAT,
  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE iss_analytics (
    id                          INTEGER PRIMARY KEY AUTOINCREMENT,
    period_start                TIMESTAMP NOT NULL,
    period_end                  TIMESTAMP NOT NULL,
    max_longitude               REAL,
    min_longitude               REAL,
    altitude_changes            INTEGER,
    altitude_change_magnitude   REAL
);
