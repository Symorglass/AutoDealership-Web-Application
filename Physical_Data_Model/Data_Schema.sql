-- Tables
CREATE TABLE LoggedInUser (
	userName VARCHAR ( 100 ) NOT NULL,
	first_name VARCHAR ( 100 ) NOT NULL,
	last_name VARCHAR ( 100 ) NOT NULL,
	password VARCHAR ( 100 ) NOT NULL,
	PRIMARY KEY ( userName )
);
CREATE TABLE Salesperson (
	userName VARCHAR ( 100 ) NOT NULL,
	PRIMARY KEY ( userName )
);
CREATE TABLE ServiceWriter (
	userName VARCHAR ( 100 ) NOT NULL,
	PRIMARY KEY ( userName )
);
CREATE TABLE InventoryClerk (
	userName VARCHAR ( 100 ) NOT NULL,
	PRIMARY KEY ( userName )
);
CREATE TABLE Manager (
	userName VARCHAR ( 100 ) NOT NULL,
	PRIMARY KEY ( userName )
);
CREATE TABLE Owner (
	userName VARCHAR ( 100 ) NOT NULL,
	PRIMARY KEY ( userName )
);
CREATE TABLE Vehicle (
	VIN CHAR ( 20 ) NOT NULL,
	model_name CHAR ( 20 ) NOT NULL,
	model_year YEAR NOT NULL,
	invoice_price DECIMAL (19,2) NOT NULL,
	added_date DATETIME NOT NULL,
	description VARCHAR (250) NULL,
	manufacturer VARCHAR ( 100 ) NOT NULL,
	userName VARCHAR ( 100 ) NOT NULL, 
	PRIMARY KEY ( VIN )
);
CREATE TABLE Manufacturer (
    manufacturer VARCHAR ( 100 ) NOT NULL,
    PRIMARY KEY ( manufacturer )
);
CREATE TABLE Car (
	VIN CHAR ( 20 ) NOT NULL,
	number_of_doors INT NOT NULL,
	PRIMARY KEY ( VIN )
);
CREATE TABLE Convertible (
	VIN CHAR ( 20 ) NOT NULL,
	roof_type CHAR ( 100 ) NOT NULL,
	back_seat_count INT NOT NULL,
	PRIMARY KEY ( VIN )
);
	
CREATE TABLE Truck (
	VIN CHAR ( 20 ) NOT NULL,
	cargo_capacity DECIMAL ( 19,2 ) NOT NULL,
	cargo_cover_type CHAR ( 20 ) NULL,
	number_of_rear_axles INT NOT NULL,
	PRIMARY KEY ( VIN )
);
	
CREATE TABLE VAN_MiniVAN (
	VIN CHAR ( 20 ) NOT NULL,
	driver_side_backdoor TINYINT(1) NOT NULL,
	PRIMARY KEY ( VIN )
);
	
CREATE TABLE SUV (
	VIN CHAR ( 20 ) NOT NULL,
	number_of_cupholders INT NOT NULL,
	PRIMARY KEY ( VIN )
);
CREATE TABLE Vehicle_Color (
  VIN CHAR (20) NOT NULL,
	color VARCHAR ( 100 ) NOT NULL,
	PRIMARY KEY (VIN, color)
);
CREATE TABLE Color (
	color VARCHAR ( 100 ) NOT NULL,
	PRIMARY KEY (color)
);

CREATE TABLE Repair (
	VIN CHAR ( 20 ) NOT NULL,
	start_date DATETIME NOT NULL,
	odometer_readout DECIMAL (19,2) NOT NULL,
	completion_date DATETIME NULL,
	labor_charge DECIMAL (19,2) NULL,
	description VARCHAR (250) NULL,
	userName VARCHAR ( 100 ) NOT NULL, 
	customerID INT NOT NULL,
	PRIMARY KEY ( VIN, start_date ),
        CHECK (completion_date > start_date or completion_date = Null)
);
CREATE TABLE Part (
	VIN CHAR (20) NOT NULL,
	start_date DATETIME NOT NULL,
	part_number CHAR (20) NOT NULL,
	vendor_name VARCHAR (100) NOT NULL,
	price DECIMAL (19,2) NOT NULL,
	quantity INT NOT NULL,
	PRIMARY KEY ( VIN, start_date, part_number)
);
CREATE TABLE Customer (
	customerID INT NOT NULL AUTO_INCREMENT,
        email VARCHAR ( 250 ) NULL,
	phone_number CHAR (20) NOT NULL,
	street_address VARCHAR ( 250 ) NOT NULL,
	city VARCHAR ( 100 ) NOT NULL,
	state CHAR (10) NOT NULL,
	postal_code CHAR (10) NOT NULL,
        PRIMARY KEY (customerID)
);
CREATE TABLE Individual (
        Driver_License_Number CHAR (20) NOT NULL,
	first_name VARCHAR ( 100 ) NOT NULL,
	last_name VARCHAR ( 100 ) NOT NULL,
        customerID INT NOT NULL,
	PRIMARY KEY (Driver_License_Number),
	UNIQUE KEY (Driver_License_Number, customerID)
);
CREATE TABLE Business (
    Tax_Identification_Number CHAR (20) NOT NULL,
	business_name VARCHAR ( 250 ) NOT NULL,
	primary_contact_name CHAR (20) NOT NULL,
	primary_contact_title CHAR (10) NOT NULL,
        customerID INT NOT NULL,
	PRIMARY KEY (Tax_Identification_Number),
	UNIQUE KEY (Tax_Identification_Number, customerID)
);
CREATE TABLE SalesTransaction (
        VIN CHAR (20) NOT NULL,
        Purchase_Date DATETIME NOT NULL,
	customerID INT NOT NULL,	
	userName VARCHAR ( 100 ) NOT NULL,
	sold_price DECIMAL (19,2) NOT NULL,
	PRIMARY KEY (VIN)
 );
 


-- Constraints   Foreign Keys: FK_ChildTable_childColumn_ParentTable_parentColumn
ALTER TABLE Salesperson 
   ADD CONSTRAINT fk_Salesperson_userName_LoggedInUser_userName FOREIGN KEY ( userName ) REFERENCES LoggedInUser ( userName );
	 
ALTER TABLE InventoryClerk 
   ADD CONSTRAINT fk_InventoryClerk_userName_LoggedInUser_userName FOREIGN KEY ( userName ) REFERENCES LoggedInUser ( userName );
	 
ALTER TABLE ServiceWriter 
   ADD CONSTRAINT fk_ServiceWriter_userName_LoggedInUser_userName FOREIGN KEY ( userName ) REFERENCES LoggedInUser ( userName );
	 
ALTER TABLE Manager 
   ADD CONSTRAINT fk_Manager_userName_LoggedInUser_userName FOREIGN KEY ( userName ) REFERENCES LoggedInUser ( userName );
   
ALTER TABLE Owner 
   ADD CONSTRAINT fk_Owner_userName_LoggedInUser_userName FOREIGN KEY ( userName ) REFERENCES LoggedInUser ( userName );

ALTER TABLE Vehicle 
   ADD CONSTRAINT fk_Vehicle_userName_InventoryClerk_userName FOREIGN KEY ( userName ) REFERENCES InventoryClerk ( userName ),
   ADD CONSTRAINT fk_Vehicle_manufacturer_Manufacturer_manufacturer FOREIGN KEY ( manufacturer ) REFERENCES Manufacturer ( manufacturer ); 
    
ALTER TABLE Car 
   ADD CONSTRAINT fk_Car_VIN_Vihicle_VIN FOREIGN KEY ( VIN ) REFERENCES Vehicle ( VIN );

ALTER TABLE Convertible 
   ADD CONSTRAINT fk_Convertible_VIN_Vihicle_VIN FOREIGN KEY ( VIN ) REFERENCES Vehicle ( VIN );
ALTER TABLE Truck 
   ADD CONSTRAINT fk_Truck_VIN_Vehicle_VIN FOREIGN KEY ( VIN ) REFERENCES Vehicle ( VIN );

ALTER TABLE VAN_MiniVAN 
   ADD CONSTRAINT fk_VAN_MiniVAN_VIN_Vehicle_VIN FOREIGN KEY ( VIN ) REFERENCES Vehicle ( VIN );

ALTER TABLE SUV 
   ADD CONSTRAINT fk_SUV_VIN_Vehicle_VIN FOREIGN KEY ( VIN ) REFERENCES Vehicle ( VIN );

ALTER TABLE Vehicle_Color 
   ADD CONSTRAINT fk_Vehicle_Color_VIN_Vehicle_VIN FOREIGN KEY ( VIN ) REFERENCES Vehicle ( VIN ),
   ADD CONSTRAINT fk_Vehicle_Color_color_Color_color FOREIGN KEY ( color ) REFERENCES Color ( color );

ALTER TABLE Individual
   ADD CONSTRAINT fk_Individual_customerID_Customer_customerID FOREIGN KEY ( customerID ) REFERENCES Customer ( customerID );

ALTER TABLE Business	 
   ADD CONSTRAINT fk_Business_customerID_Customer_customerID FOREIGN KEY ( customerID ) REFERENCES Customer ( customerID );

ALTER TABLE SalesTransaction 
   ADD CONSTRAINT fk_SalesTransaction_VIN_Vehicle_VIN FOREIGN KEY ( VIN ) REFERENCES Vehicle ( VIN ),
   ADD CONSTRAINT fk_SalesTransaction_userName_Salesperson_userName FOREIGN KEY ( userName ) REFERENCES Salesperson ( userName ),
   ADD CONSTRAINT fk_SalesTransaction_customerID_Customer_customerID FOREIGN KEY ( customerID ) REFERENCES Customer ( customerID );
	 
ALTER TABLE Part 
   ADD CONSTRAINT fk_part_VIN_Repair_VIN FOREIGN KEY ( VIN, start_date ) REFERENCES Repair ( VIN, start_date );
   
ALTER TABLE Repair
   ADD CONSTRAINT fk_Repair_VIN_Vehicle_VIN FOREIGN KEY ( VIN ) REFERENCES Vehicle ( VIN ),
   ADD CONSTRAINT fk_Repair_userName_ServiceWriter_userName FOREIGN KEY ( userName ) REFERENCES ServiceWriter ( userName ),
   ADD CONSTRAINT fk_Repair_customerID_Customer_customerID FOREIGN KEY ( customerID ) REFERENCES Customer ( customerID );

