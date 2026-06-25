CREATE TABLE IF NOT EXISTS vehicle_lookups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  brand VARCHAR(80) NOT NULL,
  model VARCHAR(120) NOT NULL,
  vehicle_type VARCHAR(80) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY ux_vehicle_lookup (brand, model, vehicle_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO vehicle_lookups (brand, model, vehicle_type, is_active) VALUES
('BMW', 'F10', 'سدان', 1),
('BMW', 'F25', 'شاسی بلند', 1),
('BMW', 'F30', 'سدان', 1),
('BMW', 'E60', 'سدان', 1),
('BMW', 'G12', 'سدان', 1),
('BMW', 'G30', 'سدان', 1),
('Mercedes-Benz', 'C Class', 'سدان', 1),
('Mercedes-Benz', 'E Class', 'سدان', 1),
('Mercedes-Benz', 'S Class', 'سدان', 1),
('Mercedes-Benz', 'GLC', 'شاسی بلند', 1),
('Mercedes-Benz', 'GLE', 'شاسی بلند', 1),
('Porsche', 'Cayenne', 'شاسی بلند', 1),
('Porsche', 'Macan', 'شاسی بلند', 1),
('Porsche', 'Panamera', 'سدان', 1),
('Volkswagen', 'Tiguan', 'شاسی بلند', 1),
('Volkswagen', 'Touareg', 'شاسی بلند', 1),
('Volvo', 'XC90', 'شاسی بلند', 1),
('Volvo', 'XC60', 'شاسی بلند', 1),
('Audi', 'A4', 'سدان', 1),
('Audi', 'A6', 'سدان', 1),
('Audi', 'Q5', 'شاسی بلند', 1),
('Audi', 'Q7', 'شاسی بلند', 1),
('Other', 'سایر', 'سایر', 1);
