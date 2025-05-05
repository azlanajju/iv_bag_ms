CREATE TABLE IV_Device_Info (
    DeviceID INT PRIMARY KEY AUTO_INCREMENT,
    DeviceName VARCHAR(100),
    Status VARCHAR(50),
    IPAddress VARCHAR(50),
    InitialWeight DECIMAL(5,2),
    CurrentWeight DECIMAL(5,2),
    HasAutoResupplyRequest BOOLEAN,
    HasLocalDisplayUnit BOOLEAN,
    HasVoiceAlerts BOOLEAN,
    HasBatteryBackupMonitoring BOOLEAN,
    SupportsOTAUpdates BOOLEAN,
    HasNurseCallIntegration BOOLEAN,
    NurseCallNumber VARCHAR(50),
    startTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    LastUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
