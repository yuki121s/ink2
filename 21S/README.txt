วิธีติดตั้ง (XAMPP / Shared Hosting)
1. สร้างฐานข้อมูล shop21 (utf8mb4_general_ci) ใน phpMyAdmin แล้ว Import ไฟล์ schema.sql
2. แก้ค่าเชื่อมต่อใน db_connect.php (host / dbname / user / pass)
   - บนโฮสต์จริง ชื่อฐานข้อมูลและผู้ใช้มักมี prefix เช่น cpanelUser_shop21
3. อัปโหลดทุกไฟล์ขึ้น public_html (ผ่าน File Manager หรือ FTP) ให้โฟลเดอร์ uploads เขียนได้ (chmod 755 หรือ 775)
4. เปิด /admin/setup.php เพื่อสร้างบัญชีผู้ดูแล แล้ว "ลบไฟล์ setup.php ทิ้ง"
5. หน้าร้าน: /shop.php   หลังบ้าน: /admin/login.php
