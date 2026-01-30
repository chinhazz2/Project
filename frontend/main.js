/**
 * MAIN.JS - PHIÊN BẢN ỔN ĐỊNH CAO
 * Fix lỗi không ẩn được menu do Sidebar load chậm
 */

const API_BASE = 'http://localhost/clinic/api';

// ============================================================
// 1. KIỂM TRA ĐĂNG NHẬP
// ============================================================
(async function checkLogin() {
    if (window.location.pathname.includes('login.html')) return;

    try {
        const res = await fetch(`${API_BASE}/check_session.php`);
        const data = await res.json();
        
        if (!data.logged_in) {
            window.location.href = 'login.html';
        } else {
            // Đăng nhập thành công -> Gọi hàm phân quyền ngay
            // Truyền role vào để xử lý
            waitForSidebarAndApplyRole(data.role);
        }
    } catch (e) {
        console.error("Lỗi auth:", e);
    }
})();

// ============================================================
// 2. KHỞI TẠO TRANG
// ============================================================
document.addEventListener("DOMContentLoaded", async function() {
    await loadSidebar();

    const path = window.location.pathname;
    if (path.includes('index.html') || path.endsWith('/')) {
        loadDashboardData();
        loadAppointments();
    } 
    else if (path.includes('appointments.html')) {
        loadAppointments();
    }
});

// ============================================================
// 3. HÀM PHÂN QUYỀN (LOẠI BỎ MENU THỪA) - QUAN TRỌNG
// ============================================================
function waitForSidebarAndApplyRole(role) {
    if (!role || role === 'guest') return;
    if (role === 'admin') return; // Admin thấy hết, không cần làm gì

    console.log("⏳ Đang đợi Sidebar để phân quyền cho:", role);

    // Thử tìm sidebar mỗi 100ms (Thử tối đa 20 lần = 2 giây)
    let attempts = 0;
    const interval = setInterval(() => {
        attempts++;
        // Kiểm tra xem Sidebar đã hiện chưa (tìm nút Trang chủ)
        const sidebarLoaded = document.getElementById('nav-index');

        if (sidebarLoaded) {
            console.log("✅ Đã thấy Sidebar! Bắt đầu ẩn menu...");
            clearInterval(interval); // Dừng tìm kiếm
            applyUserRole(role);     // Thực hiện ẩn
        } else if (attempts >= 20) {
            console.warn("⚠️ Quá thời gian đợi Sidebar.");
            clearInterval(interval);
        }
    }, 100);
}

function applyUserRole(role) {
    // Danh sách ID các menu
    const menus = {
        'doctor': 'nav-doctor',
        'cashier': 'nav-cashier',
        'inventory': 'nav-inventory',
        'registration': 'nav-registration',
        'appointments': 'nav-appointments',
        'records': 'nav-hoso'
    };

    const hide = (id) => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    };

    // LOGIC ẨN MENU
    if (role === 'doctor') {
        // Bác sĩ không cần thấy: Thu ngân, Đăng ký, Kho
        hide(menus.cashier);
        hide(menus.registration);
        hide(menus.inventory);
    } 
    else if (role === 'cashier') {
        // Thu ngân không cần thấy: Bác sĩ, Hồ sơ, Kho, Lịch hẹn
        hide(menus.doctor);
        hide(menus.records);
        hide(menus.inventory);
        hide(menus.appointments);
        hide(menus.registration);
    }
}

// ============================================================
// 4. CÁC HÀM HỖ TRỢ (Sidebar, Dashboard...)
// ============================================================

async function loadSidebar() {
    try {
        const response = await fetch('sidebar.html');
        const data = await response.text();
        const placeholder = document.getElementById('sidebar-placeholder');
        if (placeholder) {
            placeholder.innerHTML = data;
            
            // Active menu hiện tại
            let page = window.location.pathname.split("/").pop().replace(".html", "");
            if (page === '' || page === 'index') page = 'index';
            
            // Đợi 1 chút cho DOM cập nhật rồi mới active
            setTimeout(() => {
                const activeItem = document.getElementById('nav-' + page);
                if (activeItem) {
                    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
                    activeItem.classList.add('active');
                }
            }, 50);
        }
    } catch (e) { console.error(e); }
}

function navigateTo(page) {
    window.location.href = page + ".html";
}

async function loadDashboardData() {
    try {
        const resRev = await fetch(`${API_BASE}/get_daily_revenue.php`);
        const dataRev = await resRev.json();
        const revEl = document.getElementById('revenue-display');
        if (revEl) revEl.innerText = dataRev.formatted;
    } catch (e) {}

    try {
        const resStock = await fetch(`${API_BASE}/get_low_stock.php`);
        const dataStock = await resStock.json();
        const stockEl = document.getElementById('low-stock-count');
        if (stockEl) {
            stockEl.innerText = dataStock.count;
            stockEl.className = dataStock.count > 0 ? "mb-0 fw-bold text-danger" : "mb-0 fw-bold text-success";
        }
    } catch (e) {}
}

async function loadAppointments() {
    const tableBody = document.getElementById('appointmentList'); 
    const tableBody2 = document.getElementById('apptTableBody'); 
    const targetTable = tableBody || tableBody2; 
    if (!targetTable) return;

    targetTable.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin me-2"></i>Đang tải...</td></tr>';

    try {
        const dateInput = document.getElementById('filterDate');
        const date = dateInput ? dateInput.value : new Date().toISOString().slice(0, 10);
        const response = await fetch(`${API_BASE}/get_appointments.php?date=${date}`);
        const appointments = await response.json();

        if (!appointments || appointments.length === 0) {
            targetTable.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted">Không có lịch hẹn nào.</td></tr>';
            return;
        }

        // Helper badge
        const getStatusBadge = (status) => {
            const map = {
                'scheduled': '<span class="badge bg-primary">Đã đặt lịch</span>',
                'waiting': '<span class="badge bg-warning text-dark">Chờ khám</span>',
                'diagnosed': '<span class="badge bg-info text-dark">Đang kê đơn</span>',
                'payment_pending': '<span class="badge bg-danger">Chờ thanh toán</span>',
                'completed': '<span class="badge bg-success">Hoàn thành</span>',
                'cancelled': '<span class="badge bg-secondary">Đã hủy</span>'
            };
            return map[status] || `<span class="badge bg-light text-dark border">${status}</span>`;
        };

        targetTable.innerHTML = appointments.map(appt => `
            <tr>
                <td class="fw-bold text-primary ps-4">${appt.ten_benh_nhan}</td>
                <td>${appt.start_time ? appt.start_time.slice(11, 16) : '--:--'}</td> 
                <td>${appt.ten_bac_si || 'Chưa phân công'}</td>
                <td>${getStatusBadge(appt.status)}</td>
                <td class="text-center">
                     <button class="btn btn-sm btn-light border"><i class="fa-solid fa-eye text-secondary"></i></button>
                </td>
            </tr>
        `).join('');
    } catch (error) {
        targetTable.innerHTML = `<tr><td colspan="6" class="text-center text-danger">Lỗi kết nối server</td></tr>`;
    }
}
