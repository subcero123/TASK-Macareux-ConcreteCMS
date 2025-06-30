# Population Importer - ConcreteCMS Package

## Installation

### Requirements

- ConcreteCMS 9.x
- PHP 8.0 or higher
- Doctrine ORM

### Steps

1. **Download the package**
   ```bash
   cd /path/to/your/concrete5/packages/
   # Download or clone this package to 'population_importer' folder
   ```

2. **Install via Dashboard**
   - Go to Dashboard > Extend Concrete > Add Functionality
   - Find "Population Importer" in the list
   - Click "Install"

## Features

- **CSV Import**: Automatic Shift-JIS to UTF-8 conversion for Japanese e-Stat data
- **Dashboard Management**: Import management and statistics overview
- **Public Search**: Prefecture and year-based population search with AJAX statistics
- **Data Validation**: Automatic validation and duplicate prevention

## Usage

### Importing Data

1. **Prepare CSV File**
   - Download population data from [e-Stat Japan](https://www.e-stat.go.jp/stat-search/files?tclass=000001041653&cycle=7&year=20220)
   - Supports matrix format with prefectures as rows and years as columns
   - Automatic encoding detection (Shift-JIS to UTF-8)

2. **Import via Dashboard**
   - Go to Dashboard > Population Importer > Import CSV
   - Select your CSV file
   - Click "Upload and Import"
   - View import statistics and recent imports


## File Structure

```
packages/population_importer/
├── controller.php                                    # Main package controller
├── README.md                                        # Documentation
├── mi040001.csv                                     # Sample prefecture data
├── src/
│   ├── Entity/
│   │   └── Population.php                          # Doctrine entity
│   ├── EntityManagerProvider.php                   # Doctrine configuration
│   └── SchemaManager.php                           # Database schema management
├── controllers/
│   └── single_page/
│       ├── dashboard/
│       │   ├── population_importer.php             # Main dashboard controller
│       │   └── population_importer/
│       │       └── import.php                      # CSV import controller
│       └── population_search.php                   # Public search controller
└── single_pages/
    ├── dashboard/
    │   ├── population_importer.php                 # Main dashboard view
    │   └── population_importer/
    │       └── import.php                          # Import form view
    └── population_search.php                       # Public search view
```

## Technical Details

### Package Structure

- **Main Controller**: `controller.php` - Handles package installation, entity registration, and page creation
- **Entity**: `src/Entity/Population.php` - Doctrine ORM entity for population data
- **Schema Manager**: `src/SchemaManager.php` - Database schema creation and management
- **Controllers**: Handle dashboard and public page logic
- **Views**: Single page templates for dashboard and public interface

## Database Schema

The package creates a `population_data` table with the following structure:

```sql
CREATE TABLE population_data (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    prefecture VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    population BIGINT NOT NULL,
    prefecture_code VARCHAR(10) NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_prefecture_year (prefecture, year),
    INDEX idx_prefecture (prefecture),
    INDEX idx_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Schema Details

- **id**: Primary key, unsigned integer with auto-increment
- **prefecture**: Prefecture name (up to 50 characters)
- **year**: Year of the population data
- **population**: Population count (bigint to handle large numbers)
- **prefecture_code**: Optional prefecture code (up to 10 characters)
- **created_at**: Timestamp when record was created
- **updated_at**: Timestamp when record was last updated

### Indexes

- **Primary Key**: `id`
- **Composite Index**: `idx_prefecture_year` on (prefecture, year) for efficient searches
- **Single Indexes**: `idx_prefecture` and `idx_year` for individual column queries
