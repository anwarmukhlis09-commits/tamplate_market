// Convert Laravel validation errors (or any thrown error) into a
// human-readable Indonesian message suitable for a toast.
//
// Laravel returns errors as { fieldName: [msg1, msg2] } for 422 responses.
// We:
//   - translate common field names to Indonesian labels
//   - aggregate multiple errors into a multi-line bulleted list
//   - avoid duplicating the label if the server message already includes it
const fieldLabels = {
    name: 'Nama template',
    category: 'Kategori',
    short_desc: 'Deskripsi singkat',
    long_desc: 'Deskripsi lengkap',
    price: 'Harga',
    discount_price: 'Harga diskon',
    badge: 'Badge',
    status: 'Status publikasi',
    allow_edit_before_checkout: 'Pengaturan edit',
    preview_image: 'Gambar preview',
    template_files: 'Folder template',
    'template_files.0': 'File di folder template',
    'template_files.*': 'File di folder template',
    email: 'Email',
    password: 'Password',
};

export function formatUploadErrors(errors) {
    if (!errors) {
        return 'Terjadi kesalahan pada server. Silakan coba lagi, dan hubungi admin jika masalah berlanjut.';
    }
    if (typeof errors === 'string') return errors;
    if (Array.isArray(errors)) return errors.join('\n');
    if (typeof errors !== 'object') return String(errors);

    const keys = Object.keys(errors);
    if (keys.length === 0) {
        return 'Periksa kembali data yang Anda masukkan.';
    }

    const lines = [];
    for (const field of keys) {
        const label = fieldLabels[field] || field;
        let msgs = errors[field];
        if (!Array.isArray(msgs)) msgs = [msgs];
        for (const m of msgs) {
            if (!m) continue;
            const text = String(m);
            if (text.toLowerCase().startsWith(label.toLowerCase())) {
                lines.push(`• ${text}`);
            } else {
                lines.push(`• ${label}: ${text}`);
            }
        }
    }

    if (lines.length === 0) return 'Periksa kembali data yang Anda masukkan.';
    if (lines.length === 1) return lines[0].replace(/^•\s*/, '');
    return lines.join('\n');
}
