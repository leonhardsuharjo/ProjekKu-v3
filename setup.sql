CREATE DATABASE IF NOT EXISTS project_manager;
USE project_manager;

DROP TABLE IF EXISTS projectlabour;
DROP TABLE IF EXISTS projectproduct;
DROP TABLE IF EXISTS productmaterial;
DROP TABLE IF EXISTS project;
DROP TABLE IF EXISTS jobrole;
DROP TABLE IF EXISTS material;
DROP TABLE IF EXISTS product;
DROP TABLE IF EXISTS supplier;
DROP TABLE IF EXISTS customer;

CREATE TABLE customer (
    CustomerID INT AUTO_INCREMENT PRIMARY KEY,
    CustomerName VARCHAR(100) NOT NULL,
    Address VARCHAR(200),
    ContactNumber VARCHAR(30)
);

CREATE TABLE supplier (
    SupplierID INT AUTO_INCREMENT PRIMARY KEY,
    SupplierName VARCHAR(100) NOT NULL,
    Address VARCHAR(200),
    ContactNo VARCHAR(30)
);

CREATE TABLE product (
    ProductID INT AUTO_INCREMENT PRIMARY KEY,
    ProductName VARCHAR(100) NOT NULL
);

CREATE TABLE material (
    MaterialID INT AUTO_INCREMENT PRIMARY KEY,
    MaterialName VARCHAR(100) NOT NULL,
    PricePerUnit DECIMAL(15,2) NOT NULL,
    SupplierID INT NOT NULL,
    FOREIGN KEY (SupplierID) REFERENCES supplier(SupplierID)
);

CREATE TABLE jobrole (
    JobRoleID INT AUTO_INCREMENT PRIMARY KEY,
    JobType VARCHAR(100) NOT NULL,
    WagePerDay DECIMAL(15,2) NOT NULL
);

CREATE TABLE project (
    ProjectID INT AUTO_INCREMENT PRIMARY KEY,
    ProjectName VARCHAR(150) NOT NULL,
    ProjectDate DATE NOT NULL,
    TransportCost DECIMAL(15,2) NOT NULL DEFAULT 0,
    ProjectValue DECIMAL(15,2) NOT NULL,
    CustomerID INT NOT NULL,
    FOREIGN KEY (CustomerID) REFERENCES customer(CustomerID)
);

CREATE TABLE productmaterial (
    ProductID INT NOT NULL,
    MaterialID INT NOT NULL,
    QuantityNeeded DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (ProductID, MaterialID),
    FOREIGN KEY (ProductID) REFERENCES product(ProductID) ON DELETE CASCADE,
    FOREIGN KEY (MaterialID) REFERENCES material(MaterialID) ON DELETE CASCADE
);

CREATE TABLE projectproduct (
    ProjectID INT NOT NULL,
    ProductID INT NOT NULL,
    Quantity INT NOT NULL,
    PRIMARY KEY (ProjectID, ProductID),
    FOREIGN KEY (ProjectID) REFERENCES project(ProjectID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES product(ProductID)
);

CREATE TABLE projectlabour (
    ProjectID INT NOT NULL,
    JobRoleID INT NOT NULL,
    NumWorkers INT NOT NULL,
    NumDays INT NOT NULL,
    PRIMARY KEY (ProjectID, JobRoleID),
    FOREIGN KEY (ProjectID) REFERENCES project(ProjectID) ON DELETE CASCADE,
    FOREIGN KEY (JobRoleID) REFERENCES jobrole(JobRoleID)
);
