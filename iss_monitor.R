# Load libraries
library(httr)
library(jsonlite)
library(dplyr)
# Load RPostgres instead of RMySQL
library(RPostgres)

con <- dbConnect(RPostgres::Postgres(),
                 dbname = Sys.getenv("DB_NAME"),
                 host = Sys.getenv("DB_HOST"),
                 port = as.integer(Sys.getenv("DB_PORT")),
                 user = Sys.getenv("DB_USER"),
                 password = Sys.getenv("DB_PASSWORD"))

# Initialize variables
altitude_history <- c()
max_longitude <- -Inf
min_longitude <- Inf
fetch_count <- 0
period_start <- Sys.time()
total_alt_changes <- 0  
total_alt_change_count <- 0       # Count of altitude changes > threshold
total_alt_change_magnitude <- 0   # Sum of altitude changes in km

# Function to fetch and store data with error handling and timeout
fetch_iss_data <- function() {
  tryCount <- 1
  success <- FALSE
  
  while (!success && tryCount <= 3) {
    tryCatch({
      response <- GET("https://api.wheretheiss.at/v1/satellites/25544", timeout(15))
      if (status_code(response) == 200) {
        data <- fromJSON(content(response, "text"))
        
        lat <- data$latitude
        lon <- data$longitude
        alt <- data$altitude
        vel <- data$velocity
        ts <- as.POSIXct(data$timestamp, origin = "1970-01-01")
        
        # Insert raw data
        query <- sprintf("INSERT INTO iss_data (latitude, longitude, altitude, velocity, timestamp) VALUES (%f, %f, %f, %f, '%s')",
                         lat, lon, alt, vel, format(ts, "%Y-%m-%d %H:%M:%S"))
        dbExecute(con, query)
        
        # Update analytics variables
        max_longitude <<- max(max_longitude, lon)
        min_longitude <<- min(min_longitude, lon)
        altitude_history <<- c(altitude_history, alt)
        
        # Cumulative altitude change detection
        if (length(altitude_history) > 1) {
          change <- abs(altitude_history[length(altitude_history)] - altitude_history[length(altitude_history) - 1])
          if (change > 0.001) {
            total_alt_change_count <<- total_alt_change_count + 1
            total_alt_change_magnitude <<- total_alt_change_magnitude + change
            cat("Altitude change detected:", change, "km (Count:", total_alt_change_count, ", Total magnitude:", total_alt_change_magnitude, "km)\n")
          }
        }
        
        cat("Fetched:", ts, "Lat:", lat, "Lon:", lon, "Alt:", alt, "km\n")
        
        success <- TRUE
      } else {
        cat("API responded with status code:", status_code(response), "\n")
      }
    }, error = function(e) {
      cat("Error fetching ISS data:", e$message, "\n")
    })
    
    if (!success) {
      cat("Retrying fetch, attempt", tryCount, "\n")
      tryCount <- tryCount + 1
      Sys.sleep(5)  # Wait 5 seconds before retrying to avoid rapid re-queries
    }
  }
  
  if (!success) {
    cat("Failed to fetch after 3 attempts. Skipping this fetch.\n")
  }
}

# Function to compute and store analytics (every 6 hours)
  # Use the cumulative total_alt_changes
compute_and_store_analytics <- function() {
  period_end <- Sys.time()
  # Insert analytics data with both count and magnitude
  query <- sprintf("INSERT INTO iss_analytics (period_start, period_end, max_longitude, min_longitude, altitude_changes, altitude_change_magnitude) VALUES ('%s', '%s', %f, %f, %d, %f)",
                   format(period_start, "%Y-%m-%d %H:%M:%S"), format(period_end, "%Y-%m-%d %H:%M:%S"),
                   max_longitude, min_longitude, total_alt_change_count, total_alt_change_magnitude)
  dbExecute(con, query)
  
  # Reset variables (except the global counters if you want cumulative)
  altitude_history <<- c()
  max_longitude <<- -Inf
  min_longitude <<- Inf
  period_start <<- period_end
  
  cat("Analytics stored for period ending", period_end, "with cumulative altitude changes:", total_alt_change_magnitude, "\n")
}

# Main loop
repeat {
  fetch_iss_data()
  fetch_count <- fetch_count + 1
  
  # Every 6 hours (21600 fetches)
  if (fetch_count %% 21600 == 0) {
    compute_and_store_analytics()
  }
  
  Sys.sleep(1)
}

# Close connection

dbDisconnect(con) 

