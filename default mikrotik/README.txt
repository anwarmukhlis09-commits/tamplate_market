TEMPLATE HOTSPOT MIKROTIK — MARKETPLACE EDITION
================================================

Versi ini mendukung placeholder untuk customisasi otomatis saat
pembeli membeli dan generate ZIP. Semua placeholder akan otomatis
diganti saat user membeli template.

DAFTAR PLACEHOLDER (diganti otomatis saat pembeli beli):
- {{BUSINESS_NAME}}     → Nama brand / WiFi
- {{RUNNING_TEXT}}       → Teks berjalan / tagline
- {{PRIMARY_COLOR}}      → Warna utama (hex)
- {{PRIMARY_COLOR_RGB}}  → Warna utama (RGB)
- {{LOGO_URL}}           → Path logo brand
- {{LOGIN_BTN_TEXT}}     → Teks tombol login
- {{SHOW_VOUCHER}}       → Tampilkan section voucher (block/none)
- {{VOUCHER_1_PRICE}}    → Harga voucher 1
- {{VOUCHER_1_DURATION}} → Durasi voucher 1
- {{VOUCHER_1_NAME}}     → Nama voucher 1
- {{VOUCHER_2_PRICE}}    → Harga voucher 2
- {{VOUCHER_2_DURATION}} → Durasi voucher 2
- {{VOUCHER_2_NAME}}     → Nama voucher 2
- {{VOUCHER_3_PRICE}}    → Harga voucher 3
- {{VOUCHER_3_DURATION}} → Durasi voucher 3
- {{VOUCHER_3_NAME}}     → Nama voucher 3
- {{FOOTER_TEXT}}        → Teks footer
- {{WHATSAPP}}           → Nomor WhatsApp

VARIABLE MIKROTIK (otomatis terisi di router):
- $(username)            → Username login
- $(password)            → Password (encrypted)
- $(ip)                  → IP address user
- $(mac)                 → MAC address
- $(uptime)              → Lama waktu aktif
- $(bytes-in-nice)       → Total download
- $(bytes-out-nice)      → Total upload
- $(session-time-left)   → Sisa waktu sesi
- $(link-orig)           → URL tujuan setelah login
- $(link-login-only)      → URL POST login
- $(link-login)           → URL form login
- $(link-logout)         → URL logout
- $(link-status)          → URL status page
- $(chap-id)              → CHAP challenge
- $(chap-challenge)       → CHAP challenge value
- $(error)               → Pesan error

DAFTAR FILE:
Wajib (tanpa ini template tidak diproses):
- login.html             → Halaman form login utama

Direkomendasikan (preview optimal):
- status.html            → Halaman status koneksi
- logout.html            → Halaman setelah logout

Opsional (tidak wajib, preview masih bisa tampil):
- alogin.html            → Halaman redirect setelah login
- error.html             → Halaman jika login gagal
- voucher.html           → Halaman daftar voucher
- kontak.html            → Halaman kontak support
- bantuan.html           → Halaman bantuan / FAQ
- img/                   → Folder icon (user.svg, password.svg)
- icon/                  → Folder icon tambahan
- css/                   → Folder stylesheet alternatif
- js/                    → Folder JavaScript
- assets/                → Folder asset lain

Cara pakai:
1. Upload folder ini ke /admin/templates/create di marketplace
2. Validator akan otomatis scan struktur file
3. Template akan di-approve jika login.html ada
4. Pembeli bisa customisasi via /template/{id}/edit
5. Setelah checkout, ZIP di-generate dengan placeholder terisi
