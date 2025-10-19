-- Seed: categories + 100 products (SKU P-0001..P-0100)

START TRANSACTION;

INSERT IGNORE INTO categories(name) VALUES
  ('ทั่วไป'),
  ('อุปกรณ์สำนักงาน'),
  ('อุปกรณ์คอมพิวเตอร์'),
  ('อิเล็กทรอนิกส์'),
  ('ของใช้ในบ้าน');

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0001', 'กาวน้ำ สีดำ', 'ชิ้น', 3000, 71, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0002', 'น้ำยาลบคำผิด สีขาว', 'ชิ้น', 2200, 190, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0003', 'แฟ้มเอกสาร สำหรับสำนักงาน', 'ชุด', 40, 8, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0004', 'ปากกาเมจิค สีฟ้า', 'ชิ้น', 1350, 51, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0005', 'เว็บแคม สีฟ้า', 'ชุด', 1550, 72, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0006', 'เตารีด สีขาว', 'ชุด', 380, 72, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0007', 'ปากกาเมจิค ขนาดพกพา', 'ชิ้น', 75, 98, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0008', 'พาวเวอร์แบงก์ ขนาดพกพา', 'กล่อง', 3900, 12, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0009', 'เครื่องเย็บกระดาษ สีดำ', 'ชุด', 70, 142, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0010', 'ผ้าห่ม สำหรับสำนักงาน', 'กล่อง', 1450, 50, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0011', 'ยางลบ สีฟ้า', 'กล่อง', 70, 60, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0012', 'สาย HDMI สีชมพู', 'ชุด', 1850, 94, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0013', 'แฟลชไดรฟ์ ขนาดพกพา', 'แพ็ค', 2100, 69, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0014', 'เทปผ้า สีขาว', 'แพ็ค', 150, 119, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0015', 'สมุดสเก็ตช์ สำหรับนักเรียน', 'แพ็ค', 2300, 84, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0016', 'น้ำยาลบคำผิด รุ่นมาตรฐาน', 'กล่อง', 460, 69, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0017', 'ปากกาเมจิค สำหรับสำนักงาน', 'กล่อง', 220, 168, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0018', 'เมาส์แพด แบบประหยัด', 'แพ็ค', 280, 36, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0019', 'หม้อหุงข้าว สำหรับนักเรียน', 'กล่อง', 3100, 150, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0020', 'ถังขยะ สำหรับสำนักงาน', 'ชุด', 410, 57, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0021', 'นาฬิกาแขวน แบบประหยัด', 'ชิ้น', 3200, 13, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0022', 'ปากกาไฮไลท์ สีขาว', 'ชุด', 1600, 17, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0023', 'สาย HDMI สำหรับสำนักงาน', 'ชุด', 1150, 65, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0024', 'ไม้กวาด รุ่นมาตรฐาน', 'ชิ้น', 2300, 138, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0025', 'โคมไฟ ขนาดพกพา', 'ชิ้น', 320, 112, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0026', 'รางปลั๊กไฟ รุ่นมาตรฐาน', 'กล่อง', 1000, 196, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0027', 'นาฬิกาแขวน สีดำ', 'กล่อง', 4300, 164, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0028', 'เทปผ้า สีฟ้า', 'แพ็ค', 420, 196, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0029', 'เครื่องเย็บกระดาษ สำหรับนักเรียน', 'ชิ้น', 1600, 83, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0030', 'ดินสอ สีดำ', 'กล่อง', 4800, 79, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0031', 'ไม้บรรทัด สีฟ้า', 'ชิ้น', 70, 188, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0032', 'ผ้าปูที่นอน สีดำ', 'แพ็ค', 100, 169, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0033', 'สเปรย์ทำความสะอาด สำหรับนักเรียน', 'แพ็ค', 280, 136, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0034', 'ลำโพง สีฟ้า', 'แพ็ค', 2700, 80, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0035', 'กล่องดินสอ ขนาดพกพา', 'ชุด', 1100, 116, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0036', 'แฟ้มสันกว้าง สีฟ้า', 'ชิ้น', 380, 6, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0037', 'ลวดเย็บ สีฟ้า', 'แพ็ค', 20, 19, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0038', 'น้ำยาลบคำผิด สีดำ', 'ชิ้น', 4600, 85, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0039', 'นาฬิกาแขวน สีฟ้า', 'กล่อง', 2100, 125, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0040', 'เครื่องเย็บกระดาษ สีขาว', 'ชุด', 260, 122, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0041', 'โพสต์อิท สีดำ', 'ชิ้น', 2000, 111, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0042', 'ลำโพง เกรดพรีเมียม', 'ชุด', 4600, 187, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0043', 'กระเป๋าเป้ สีดำ', 'ชิ้น', 460, 187, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0044', 'ปลอกหมอน สีดำ', 'แพ็ค', 190, 49, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0045', 'ปลั๊กพ่วง สีขาว', 'ชุด', 180, 72, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0046', 'แฟ้มสันกว้าง สีดำ', 'ชุด', 3900, 141, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0047', 'ไม้บรรทัด สำหรับนักเรียน', 'ชิ้น', 75, 193, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0048', 'ปากกาลบคำผิด เกรดพรีเมียม', 'ชุด', 850, 55, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0049', 'ถังขยะ รุ่นมาตรฐาน', 'แพ็ค', 430, 1, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0050', 'กล่องเอนกประสงค์ แบบประหยัด', 'กล่อง', 490, 179, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0051', 'กล่องดินสอ แบบประหยัด', 'แพ็ค', 190, 76, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0052', 'ไม้บรรทัด สำหรับสำนักงาน', 'ชิ้น', 3100, 81, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0053', 'ไม้บรรทัด สำหรับสำนักงาน', 'ชุด', 1000, 136, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0054', 'ไม้บรรทัด สำหรับนักเรียน', 'ชิ้น', 4400, 48, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0055', 'เทปผ้า สีดำ', 'แพ็ค', 460, 31, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0056', 'แฟ้มสันกว้าง สำหรับสำนักงาน', 'ชิ้น', 1750, 21, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0057', 'กล่องดินสอ สำหรับสำนักงาน', 'กล่อง', 280, 53, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0058', 'แฟ้มสันกว้าง สีชมพู', 'ชุด', 100, 172, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0059', 'รางปลั๊กไฟ ขนาดพกพา', 'ชิ้น', 25, 118, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0060', 'ที่เจาะกระดาษ สีดำ', 'ชิ้น', 1200, 55, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0061', 'กล่องเอนกประสงค์ สีขาว', 'กล่อง', 4800, 18, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0062', 'แฟลชไดรฟ์ สีชมพู', 'แพ็ค', 600, 140, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0063', 'กาวแท่ง สำหรับนักเรียน', 'ชิ้น', 2100, 142, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0064', 'ผ้าไมโครไฟเบอร์ สีดำ', 'แพ็ค', 280, 30, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0065', 'หม้อหุงข้าว สำหรับนักเรียน', 'แพ็ค', 290, 73, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0066', 'ปากกาเมจิค ขนาดพกพา', 'แพ็ค', 2300, 163, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0067', 'นาฬิกาแขวน แบบประหยัด', 'กล่อง', 4400, 14, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0068', 'กาวน้ำ เกรดพรีเมียม', 'กล่อง', 45, 1, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0069', 'โคมไฟ สีขาว', 'กล่อง', 150, 190, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0070', 'ลวดเย็บ เกรดพรีเมียม', 'ชิ้น', 90, 20, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0071', 'เครื่องเย็บกระดาษ รุ่นมาตรฐาน', 'กล่อง', 1500, 142, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0072', 'ลำโพง สีขาว', 'ชิ้น', 340, 94, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0073', 'ถังขยะ ขนาดพกพา', 'แพ็ค', 2300, 64, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0074', 'พาวเวอร์แบงก์ สำหรับนักเรียน', 'ชุด', 1750, 192, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0075', 'ผ้าไมโครไฟเบอร์ สีฟ้า', 'แพ็ค', 3800, 46, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0076', 'ดินสอ สีขาว', 'กล่อง', 3600, 106, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0077', 'สมุดสเก็ตช์ สีขาว', 'ชิ้น', 430, 10, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0078', 'น้ำยาลบคำผิด สีฟ้า', 'ชุด', 390, 79, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0079', 'น้ำยาลบคำผิด รุ่นมาตรฐาน', 'แพ็ค', 460, 85, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0080', 'ไม้กวาด สีดำ', 'กล่อง', 390, 165, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0081', 'เมาส์แพด สำหรับนักเรียน', 'กล่อง', 35, 30, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0082', 'เทปลบคำผิด สำหรับสำนักงาน', 'กล่อง', 40, 28, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0083', 'ลำโพง ขนาดพกพา', 'กล่อง', 500, 156, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0084', 'สติ๊กเกอร์ เกรดพรีเมียม', 'แพ็ค', 270, 12, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0085', 'ปากกา สำหรับนักเรียน', 'แพ็ค', 410, 111, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0086', 'สเปรย์ทำความสะอาด ขนาดพกพา', 'กล่อง', 2000, 32, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0087', 'นาฬิกาแขวน สีชมพู', 'ชุด', 360, 104, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0088', 'ลวดเย็บ สีขาว', 'แพ็ค', 480, 171, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0089', 'กระเป๋าเป้ สีขาว', 'กล่อง', 460, 141, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0090', 'คีย์บอร์ด สีชมพู', 'แพ็ค', 500, 149, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0091', 'กรรไกร ขนาดพกพา', 'ชุด', 600, 114, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0092', 'นาฬิกาแขวน แบบประหยัด', 'แพ็ค', 2000, 22, (SELECT id FROM categories WHERE name='อุปกรณ์คอมพิวเตอร์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0093', 'นาฬิกาแขวน สำหรับสำนักงาน', 'กล่อง', 75, 193, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0094', 'กระเป๋าเป้ สีชมพู', 'แพ็ค', 3900, 51, (SELECT id FROM categories WHERE name='อุปกรณ์สำนักงาน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0095', 'ดินสอ รุ่นมาตรฐาน', 'แพ็ค', 800, 157, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0096', 'รางปลั๊กไฟ เกรดพรีเมียม', 'แพ็ค', 2700, 179, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0097', 'รีโมท เกรดพรีเมียม', 'แพ็ค', 130, 168, (SELECT id FROM categories WHERE name='ทั่วไป' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0098', 'ถังขยะ สีดำ', 'ชุด', 230, 46, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0099', 'รางปลั๊กไฟ รุ่นมาตรฐาน', 'แพ็ค', 4400, 32, (SELECT id FROM categories WHERE name='อิเล็กทรอนิกส์' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

INSERT INTO products(sku, name, unit, price, qty, category_id)
VALUES ('P-0100', 'กระดาษ A4 แบบประหยัด', 'กล่อง', 3200, 114, (SELECT id FROM categories WHERE name='ของใช้ในบ้าน' LIMIT 1))
ON DUPLICATE KEY UPDATE name=VALUES(name), unit=VALUES(unit), price=VALUES(price), qty=VALUES(qty), category_id=VALUES(category_id);

COMMIT;