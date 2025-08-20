// Initialize datepicker
document.addEventListener('DOMContentLoaded', function() {
    // Initialize date time picker
    flatpickr("#scheduleTime", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        minDate: "today",
        time_24hr: true,
        locale: "id"
    });

    // Toggle recurring options
    document.getElementById('isRecurring').addEventListener('change', function() {
        document.getElementById('recurrenceOptions').style.display = this.checked ? 'block' : 'none';
    });

    // Update fields when routing changes
    const routingSelect = document.getElementById('routingSelect');
    const currentNumberField = document.getElementById('currentNumber');
    const routingStatusField = document.getElementById('routingStatus');

    function updateFields() {
        if (routingSelect.selectedIndex === -1) {
            currentNumberField.value = '';
            routingStatusField.value = '0';
            return;
        }

        const selectedOption = routingSelect.options[routingSelect.selectedIndex];
        currentNumberField.value = selectedOption.dataset.currentNumber || '';
        routingStatusField.value = selectedOption.dataset.locktype || '0';
    }

    routingSelect.addEventListener('change', updateFields);
    updateFields(); // Initialize

    // Tab navigation
    document.querySelectorAll('.tab-btn').forEach(tab => {
        tab.addEventListener('click', function() {
            if (this.classList.contains('active')) return;
            
            document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            
            this.classList.add('active');
            document.getElementById(this.dataset.tab + '-tab').classList.add('active');
        });
    });

    // Form submission
    const form = document.getElementById('scheduleForm');
    let isSubmitting = false;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (isSubmitting) return;
        isSubmitting = true;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

        try {
            // Validate form
            const routingId = form.elements['routing_id'].value;
            const newNumber = form.elements['new_number'].value;
            const scheduleTime = form.elements['schedule_time'].value;
            
            if (!routingId || !newNumber || !scheduleTime) {
                throw new Error('Harap isi semua field yang wajib diisi!');
            }

            const formData = new FormData(form);
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showResult('Jadwal berhasil dibuat!', 'success');
                form.reset();
                setTimeout(() => window.location.reload(), 1500);
            } else {
                throw new Error(data.message || 'Terjadi kesalahan');
            }
        } catch (error) {
            showResult(`Error: ${error.message}`, 'error');
            console.error("Error:", error);
        } finally {
            isSubmitting = false;
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });

    function showResult(message, type) {
        const resultDiv = document.getElementById('result');
        resultDiv.textContent = message;
        resultDiv.className = `result-message ${type}`;
    }

    // Cancel schedule
    document.querySelectorAll('.btn-cancel').forEach(btn => {
        btn.addEventListener('click', async function() {
            if (!confirm('Apakah Anda yakin ingin membatalkan jadwal ini?')) return;
            if (this.disabled) return;

            const scheduleId = this.dataset.id;
            const formData = new FormData();
            formData.append('action', 'cancel_schedule');
            formData.append('id', scheduleId);
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Jadwal berhasil dibatalkan');
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Gagal membatalkan jadwal');
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
                console.error('Error:', error);
            }
        });
    });

    // Routing search
    document.getElementById('routingSearch').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const options = document.getElementById('routingSelect').options;
        
        let firstVisible = null;
        
        for (let i = 0; i < options.length; i++) {
            const optionText = options[i].text.toLowerCase();
            const isVisible = optionText.includes(searchTerm);
            options[i].style.display = isVisible ? '' : 'none';
            
            if (isVisible && firstVisible === null) {
                firstVisible = options[i];
            }
        }
        
        if (firstVisible) {
            firstVisible.selected = true;
            updateFields();
        }
    });

    // Update routing status
    document.getElementById('updateStatusBtn').addEventListener('click', async function() {
        const select = document.getElementById('routingSelect');
        const statusSelect = document.getElementById('routingStatus');
        
        const routingId = select.value;
        const newLockType = parseInt(statusSelect.value);
        const server = document.querySelector('input[name="server"]').value;

        if (!routingId) {
            alert("Pilih routing terlebih dahulu!");
            return;
        }

        if (!confirm(`Yakin ingin ${newLockType === 3 ? 'MENUTUP' : 'MEMBUKA'} routing ini?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'toggle_locktype');
        formData.append('routing_id', routingId);
        formData.append('server', server);
        formData.append('new_locktype', newLockType);

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Status routing berhasil diupdate!');
                window.location.reload();
            } else {
                throw new Error(data.message || 'Gagal update status routing');
            }
        } catch (error) {
            alert(`Error: ${error.message}`);
            console.error('Error:', error);
        }
    });
});
