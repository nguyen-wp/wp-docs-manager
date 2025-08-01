# Hướng dẫn sử dụng nút View mới

## Nút View đã được thêm vào trang secure document!

### Tính năng mới:
1. **Nút View (màu xanh lá)** - Mở file trực tiếp trong trình duyệt
2. **Nút Download (màu xanh dương)** - Tải file về máy

### Vị trí hiển thị:
- Trang: `/document-files/secure/?lift_secure=TOKEN`
- Cả hai layout: Clean layout và Themed layout

### URL patterns:
- **Download**: `/document-files/download/?lift_secure=TOKEN`
- **View**: `/document-files/view/?lift_secure=TOKEN`

### Styling:
- Nút View: Màu xanh lá (#28a745) với icon 👁️
- Nút Download: Màu xanh dương (#1976d2) với icon ⬇️
- Hover effects với shadow và transform
- Responsive design cho mobile

### Cách hoạt động:
1. **View button**: Mở file trong tab mới với `target="_blank"`
2. **Download button**: Tải file về như cũ
3. Cùng token security và permission system

Trang secure document giờ đây có đầy đủ options cho người dùng! 🎉
