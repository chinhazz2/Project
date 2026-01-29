// File: main.js - Thay thế hàm loadAppointments cũ bằng hàm này
async function loadAppointments() {
    const tableBody = document.getElementById('appointmentList');
    
    // Hiển thị loading
    if(tableBody) tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4">Đang tải dữ liệu...</td></tr>';

    try {
        // Lấy ngày từ bộ lọc (nếu có) hoặc mặc định
        const dateInput = document.getElementById('filterDate');
        const date = dateInput ? dateInput.value : new Date().toISOString().slice(0, 10);

        const response = await fetch(`http://localhost/clinic/api/get_appointments.php?date=${date}`);
        const appointments = await response.json();

        if (!tableBody) return; // Nếu không ở trang có bảng này thì thoát

        if (appointments.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted">Không có lịch hẹn nào.</td></tr>';
            return;
        }

        // HÀM DỊCH TRẠNG THÁI SANG TIẾNG VIỆT & MÀU SẮC
        const getStatusBadge = (status) => {
            switch(status) {
                case 'scheduled': 
                    return '<span class="badge bg-primary">Đã đặt lịch</span>';
                case 'waiting': 
                    return '<span class="badge bg-warning text-dark">Đang chờ khám</span>';
                case 'diagnosed': 
                    return '<span class="badge bg-info text-dark">Đang kê đơn</span>';
                case 'payment_pending': 
                    return '<span class="badge bg-danger">Chờ thanh toán</span>'; // Màu đỏ cho nổi bật
                case 'completed': 
                    return '<span class="badge bg-success">Hoàn thành</span>';
                case 'cancelled': 
                    return '<span class="badge bg-secondary">Đã hủy</span>';
                default: 
                    return `<span class="badge bg-light text-dark border">${status}</span>`;
            }
        };

        tableBody.innerHTML = appointments.map(appt => `
            <tr>
                <td class="fw-bold text-primary">${appt.ten_benh_nhan}</td>
                <td>${appt.start_time.slice(11, 16)}</td> <td>${appt.ten_bac_si}</td>
                <td>${getStatusBadge(appt.status)}</td>
                <td>
                     <button class="btn btn-sm btn-light border" title="Chi tiết">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </td>
            </tr>
        `).join('');

    } catch (error) {
        console.error('Lỗi:', error);
        if(tableBody) tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Lỗi kết nối server</td></tr>`;
    }
}

        //  Hàm load Sidebar dùng chung
        async function loadSidebar() {
            const response = await fetch('sidebar.html');
            const data = await response.text();
            document.getElementById('sidebar-placeholder').innerHTML = data;

            const path = window.location.pathname;
            let page = path.split("/").pop().replace(".html", "");
            const activeItem = document.getElementById('nav-' + page);
            if (activeItem) {
            // Xóa active cũ nếu có (để an toàn)
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            // Thêm active mới
            activeItem.classList.add('active');
        }
        }
        // HÀM ĐIỀU HƯỚNG TRANG 
        function navigateTo(page) {
            window.location.href = page + ".html";
        }

        function createNewAppointment() {
            const name = prompt("Nhập tên bệnh nhân mới:");
            if (name) {
                alert("Đã tạo lịch hẹn cho: " + name);
            }
        }


       