// Intercept all fetch calls to catch 401 (Unauthenticated) responses globally
const originalFetch = window.fetch;
window.fetch = async function(...args) {
    const response = await originalFetch(...args);
    if (response.status === 401) {
        window.location.href = 'login.php';
    }
    return response;
};

document.addEventListener('DOMContentLoaded', () => {
    // Navigation
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.section');
    
    // API Endpoints
    const API_TRADES = 'api/trades.php';
    const API_STUDENTS = 'api/students.php';
    const API_UPLOAD = 'api/upload.php';
    const API_DOWNLOAD = 'api/download.php';

    // State
    let currentStudentId = null;
    let cameraStream = null;

    // View Management
    function switchView(targetId) {
        sections.forEach(sec => sec.classList.remove('active'));
        document.getElementById(targetId).classList.add('active');
        
        navItems.forEach(item => {
            if(item.dataset.target === targetId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Stop camera if leaving detail view
        if(targetId !== 'student-detail-view') {
            stopCamera();
        }
    }

    navItems.forEach(item => {
        item.addEventListener('click', () => {
            switchView(item.dataset.target);
            if (item.dataset.target === 'trades-view') loadTrades();
            if (item.dataset.target === 'students-view') loadTradesDropdown('student-trade');
            if (item.dataset.target === 'enrollments-view') loadTradesDropdown('filter-trade');
        });
    });

    document.getElementById('back-to-enrollments').addEventListener('click', () => {
        switchView('enrollments-view');
    });

    // --- Trades ---
    async function loadTrades() {
        try {
            const res = await fetch(API_TRADES);
            const data = await res.json();
            const list = document.getElementById('trades-list');
            list.innerHTML = '';
            
            if(data.trades) {
                data.trades.forEach(trade => {
                    list.innerHTML += `
                        <div class="glass card trade-card" data-id="${trade.id}" data-name="${trade.name.replace(/"/g, '&quot;')}">
                            <div class="card-header-flex">
                                <h3 class="card-title">${trade.name}</h3>
                                <div class="card-actions">
                                    <button class="btn-icon edit-btn-icon edit-trade-btn" title="Edit Trade"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn-icon delete-btn-icon delete-trade-btn" title="Delete Trade"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                            <p class="card-subtitle">Added: ${new Date(trade.created_at).toLocaleDateString()}</p>
                        </div>
                    `;
                });
            }
        } catch (e) {
            console.error('Error loading trades', e);
        }
    }

    async function loadTradesDropdown(selectId) {
        try {
            const res = await fetch(API_TRADES);
            const data = await res.json();
            const select = document.getElementById(selectId);
            select.innerHTML = '<option value="">Select a trade</option>';
            
            if(data.trades) {
                data.trades.forEach(trade => {
                    select.innerHTML += `<option value="${trade.id}">${trade.name}</option>`;
                });
            }
        } catch (e) {
            console.error('Error loading trades for dropdown', e);
        }
    }

    document.getElementById('add-trade-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('trade-name').value;
        const btn = e.target.querySelector('button');
        btn.disabled = true;
        
        try {
            const res = await fetch(API_TRADES, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });
            const data = await res.json();
            if(data.success) {
                document.getElementById('trade-name').value = '';
                loadTrades();
            } else {
                alert('Failed to add trade: ' + data.message);
            }
        } catch(err) {
            console.error(err);
        } finally {
            btn.disabled = false;
        }
    });

    // --- Students ---
    document.getElementById('add-student-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const trade_id = document.getElementById('student-trade').value;
        const name = document.getElementById('student-name').value;
        const father_name = document.getElementById('father-name').value;
        const btn = e.target.querySelector('button');
        
        if(!trade_id) return alert('Select a trade');
        
        btn.disabled = true;
        try {
            const res = await fetch(API_STUDENTS, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ trade_id, name, father_name })
            });
            const data = await res.json();
            if(data.success) {
                e.target.reset();
                alert('Student enrolled successfully!');
            } else {
                alert('Failed: ' + data.message);
            }
        } catch(err) {
            console.error(err);
        } finally {
            btn.disabled = false;
        }
    });

    document.getElementById('filter-trade').addEventListener('change', async (e) => {
        const trade_id = e.target.value;
        if(!trade_id) {
            document.getElementById('students-list').innerHTML = '';
            return;
        }
        
        try {
            const res = await fetch(`${API_STUDENTS}?trade_id=${trade_id}`);
            const data = await res.json();
            const list = document.getElementById('students-list');
            list.innerHTML = '';
            
            if(data.students && data.students.length > 0) {
                data.students.forEach(student => {
                    const photosCount = student.photos ? student.photos.length : 0;
                    const badge = photosCount === 4 
                        ? `<span class="badge">Complete (4/4)</span>` 
                        : `<span class="badge" style="background: rgba(239,68,68,0.2); color: var(--danger)">Missing Photos (${photosCount}/4)</span>`;
                    
                    const card = document.createElement('div');
                    card.className = 'glass card student-card';
                    card.dataset.id = student.id;
                    card.dataset.name = student.name;
                    card.dataset.father = student.father_name;
                    card.dataset.tradeId = student.trade_id;
                    
                    card.innerHTML = `
                        <div class="card-header-flex">
                            <div>
                                <h3 class="card-title">${student.name}</h3>
                                <p class="card-subtitle">S/O ${student.father_name}</p>
                            </div>
                            <div class="card-actions">
                                ${badge}
                                <button class="btn-icon edit-btn-icon edit-student-btn" title="Edit Student"><i class="fa-solid fa-user-pen"></i></button>
                                <button class="btn-icon delete-btn-icon delete-student-btn" title="Delete Student"><i class="fa-solid fa-trash"></i></button>
                            </div>
                        </div>
                        <button class="btn btn-outline" style="margin-top: 1rem; width: 100%; padding: 0.5rem;" onclick="openStudentDetail(${student.id}, '${student.name.replace(/'/g, "\\'")}', '${student.father_name.replace(/'/g, "\\'")}', '${encodeURIComponent(JSON.stringify(student.photos))}')">
                            Open Assessment
                        </button>
                    `;
                    list.appendChild(card);
                });
            } else {
                list.innerHTML = '<p class="card-subtitle">No students enrolled in this trade yet.</p>';
            }
        } catch (err) {
            console.error(err);
        }
    });

    // --- Camera & Details ---
    window.openStudentDetail = (id, name, fatherName, photosJsonStr) => {
        currentStudentId = id;
        document.getElementById('detail-student-name').textContent = name;
        document.getElementById('detail-father-name').textContent = 'Father: ' + fatherName;
        document.getElementById('download-btn').href = `${API_DOWNLOAD}?student_id=${id}`;
        
        const photos = JSON.parse(decodeURIComponent(photosJsonStr || '[]'));
        
        // Reset slots
        document.querySelectorAll('.photo-slot').forEach(slot => {
            slot.classList.remove('has-photo');
            const img = slot.querySelector('.photo-preview');
            img.src = '';
            
            const type = slot.dataset.type;
            const existing = photos.find(p => p.photo_type === type);
            if(existing) {
                slot.classList.add('has-photo');
                // appending timestamp to bypass cache
                img.src = 'api/../' + existing.file_path + '?t=' + new Date().getTime();
            }
        });

        switchView('student-detail-view');
        startCamera();
    };

    async function startCamera() {
        const video = document.getElementById('camera-stream');
        try {
        const isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        const facingMode = isMobile ? 'environment' : 'user';
        cameraStream = await navigator.mediaDevices.getUserMedia({ 
            video: { facingMode, width: { ideal: 1280 }, height: { ideal: 720 } } 
        });
            video.srcObject = cameraStream;
        } catch (err) {
            console.error("Camera error:", err);
            alert("Could not access camera. Please ensure permissions are granted.");
        }
    }

    function stopCamera() {
        if(cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
    }

    // Capture Buttons
    document.querySelectorAll('.capture-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            if(!currentStudentId) return;
            
            const slot = e.target.closest('.photo-slot');
            const type = slot.dataset.type;
            
            const video = document.getElementById('camera-stream');
            const canvas = document.getElementById('canvas');
            const context = canvas.getContext('2d');
            
            const vw = video.videoWidth || 640;
            const vh = video.videoHeight || 480;
            
            if(vw === 0) return alert('Camera not ready');

            let sx = 0, sy = 0, sw = vw, sh = vh;
            let dw = vw, dh = vh;

            if (type.includes('Practical')) {
                // Square Crop (1:1)
                const side = Math.min(vw, vh);
                sx = (vw - side) / 2;
                sy = (vh - side) / 2;
                sw = side;
                sh = side;
                dw = 400; // standard output size
                dh = 400;
            } else {
                // Portrait Crop (3:4)
                const targetW = vh * 0.75;
                if (vw >= targetW) {
                    sx = (vw - targetW) / 2;
                    sy = 0;
                    sw = targetW;
                    sh = vh;
                } else {
                    const targetH = vw / 0.75;
                    sx = 0;
                    sy = (vh - targetH) / 2;
                    sw = vw;
                    sh = targetH;
                }
                dw = 360; // standard output size
                dh = 480;
            }

            canvas.width = dw;
            canvas.height = dh;

            // Draw cropped frame
            context.drawImage(video, sx, sy, sw, sh, 0, 0, dw, dh);
            
            // Get base64 image
            const base64Image = canvas.toDataURL('image/png', 0.85);
            
            // Show optimistic preview
            const img = slot.querySelector('.photo-preview');
            img.src = base64Image;
            slot.classList.add('has-photo');
            e.target.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';
            e.target.disabled = true;

            // Upload
            try {
                const res = await fetch(API_UPLOAD, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        student_id: currentStudentId,
                        photo_type: type,
                        image: base64Image
                    })
                });
                const data = await res.json();
                if(data.success) {
                    e.target.innerHTML = 'Retake Photo';
                    // Update dropdown list to reflect new photo count
                    document.getElementById('filter-trade').dispatchEvent(new Event('change'));
                } else {
                    alert('Upload failed: ' + data.message);
                    slot.classList.remove('has-photo');
                    e.target.innerHTML = 'Capture';
                }
            } catch (err) {
                console.error(err);
                alert('Upload failed due to network error.');
                slot.classList.remove('has-photo');
                e.target.innerHTML = 'Capture';
            } finally {
                e.target.disabled = false;
            }
        });
    });

    // --- Confirmation Modal Helper ---
    function showConfirmModal(message, onOk) {
        const modal = document.getElementById('confirm-modal');
        const msgEl = document.getElementById('confirm-message');
        const okBtn = document.getElementById('confirm-ok');
        const cancelBtn = document.getElementById('confirm-cancel');
        
        msgEl.textContent = message;
        modal.classList.add('active');
        
        const cleanup = () => {
            modal.classList.remove('active');
            okBtn.onclick = null;
            cancelBtn.onclick = null;
        };
        
        okBtn.onclick = () => {
            onOk();
            cleanup();
        };
        
        cancelBtn.onclick = cleanup;
    }

    // --- Edit Student Modal Helper Functions ---
    async function openEditStudentModal(id, name, fatherName, tradeId) {
        const modal = document.getElementById('edit-student-modal');
        document.getElementById('edit-student-id').value = id;
        document.getElementById('edit-student-name').value = name;
        document.getElementById('edit-father-name').value = fatherName;
        
        // Load trades into modal dropdown
        await loadTradesDropdown('edit-student-trade');
        document.getElementById('edit-student-trade').value = tradeId;
        
        modal.classList.add('active');
    }

    function closeEditStudentModal() {
        document.getElementById('edit-student-modal').classList.remove('active');
    }

    // Close modal event listeners
    document.getElementById('close-student-modal').addEventListener('click', closeEditStudentModal);
    document.getElementById('cancel-student-modal').addEventListener('click', closeEditStudentModal);

    // Modal form submit handler
    document.getElementById('edit-student-form-modal').addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('edit-student-id').value;
        const trade_id = document.getElementById('edit-student-trade').value;
        const name = document.getElementById('edit-student-name').value.trim();
        const father_name = document.getElementById('edit-father-name').value.trim();
        
        if(!trade_id) return alert('Select a trade');
        
        const btn = e.target.querySelector('button[type="submit"]');
        btn.disabled = true;
        
        try {
            const res = await fetch(API_STUDENTS, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, trade_id, name, father_name })
            });
            const data = await res.json();
            if (data.success) {
                closeEditStudentModal();
                // Refresh current student list by triggering change event on the filter
                document.getElementById('filter-trade').dispatchEvent(new Event('change'));
            } else {
                alert('Failed to update student: ' + data.message);
            }
        } catch(err) {
            console.error(err);
        } finally {
            btn.disabled = false;
        }
    });

    // --- Trades List Event Delegation (Inline Edit & Delete) ---
    document.getElementById('trades-list').addEventListener('click', async (e) => {
        const card = e.target.closest('.trade-card');
        if (!card) return;
        
        const tradeId = card.dataset.id;
        const tradeName = card.dataset.name;
        
        // Check if clicked Edit
        if (e.target.closest('.edit-trade-btn')) {
            e.stopPropagation();
            const cardHeader = card.querySelector('.card-header-flex');
            cardHeader.innerHTML = `
                <input type="text" class="inline-edit-input" value="${tradeName.replace(/"/g, '&quot;')}">
                <div class="card-actions">
                    <button class="btn-icon edit-btn-icon save-trade-btn" title="Save"><i class="fa-solid fa-check"></i></button>
                    <button class="btn-icon cancel-trade-btn" title="Cancel"><i class="fa-solid fa-xmark"></i></button>
                </div>
            `;
            const input = cardHeader.querySelector('input');
            input.focus();
            input.select();
            // Handle press enter to save
            input.addEventListener('keyup', (ev) => {
                if (ev.key === 'Enter') {
                    card.querySelector('.save-trade-btn').click();
                } else if (ev.key === 'Escape') {
                    card.querySelector('.cancel-trade-btn').click();
                }
            });
        }
        
        // Check if clicked Save
        if (e.target.closest('.save-trade-btn')) {
            e.stopPropagation();
            const input = card.querySelector('.inline-edit-input');
            const newName = input.value.trim();
            if (!newName) return alert('Trade name cannot be empty');
            
            try {
                const res = await fetch(API_TRADES, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: tradeId, name: newName })
                });
                const data = await res.json();
                if (data.success) {
                    loadTrades();
                } else {
                    alert('Failed to update trade: ' + data.message);
                }
            } catch (err) {
                console.error(err);
            }
        }
        
        // Check if clicked Cancel
        if (e.target.closest('.cancel-trade-btn')) {
            e.stopPropagation();
            loadTrades();
        }
        
        // Check if clicked Delete
        if (e.target.closest('.delete-trade-btn')) {
            e.stopPropagation();
            showConfirmModal(
                `Are you sure you want to delete the trade "${tradeName}"? This will permanently delete all enrolled students and their assessment photos.`,
                async () => {
                    try {
                        const res = await fetch(`${API_TRADES}?id=${tradeId}`, {
                            method: 'DELETE'
                        });
                        const data = await res.json();
                        if (data.success) {
                            loadTrades();
                            // also refresh students filter dropdown in case it's open
                            loadTradesDropdown('filter-trade');
                            document.getElementById('students-list').innerHTML = '';
                        } else {
                            alert('Failed to delete trade: ' + data.message);
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }
            );
        }
    });

    // --- Students List Event Delegation (Edit & Delete) ---
    document.getElementById('students-list').addEventListener('click', async (e) => {
        const card = e.target.closest('.student-card');
        if (!card) return;
        
        const studentId = card.dataset.id;
        const studentName = card.dataset.name;
        const fatherName = card.dataset.father;
        const tradeId = card.dataset.tradeId;
        
        // Check if clicked Edit
        if (e.target.closest('.edit-student-btn')) {
            e.stopPropagation();
            openEditStudentModal(studentId, studentName, fatherName, tradeId);
        }
        
        // Check if clicked Delete
        if (e.target.closest('.delete-student-btn')) {
            e.stopPropagation();
            showConfirmModal(
                `Are you sure you want to delete the student "${studentName}"? This will permanently delete their profile and captured assessment photos.`,
                async () => {
                    try {
                        const res = await fetch(`${API_STUDENTS}?id=${studentId}`, {
                            method: 'DELETE'
                        });
                        const data = await res.json();
                        if (data.success) {
                            document.getElementById('filter-trade').dispatchEvent(new Event('change'));
                        } else {
                            alert('Failed to delete student: ' + data.message);
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }
            );
        }
    });

    // Initial load
    loadTrades();
});
