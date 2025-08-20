<?php

session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['token'])) {
    header('Location: ../login.php');
    exit;
}


$sql = "SELECT * FROM dailywork ORDER BY tanggal_input DESC, waktu_mulai DESC";
$query_site = "SELECT DISTINCT site FROM dailywork WHERE site IS NOT NULL AND site != '' ORDER BY site";
$result = $koneksi->query($sql);
$result_site = $koneksi->query($query_site);

// Mengatur hak akses  
if ($_SESSION['role'] !== 'noc_internet' && $_SESSION['role'] !== 'admin_it' && $_SESSION['role'] !== 'administrator') {
    header('Location: ../unauthorized.php');
    exit;
}


// Tentukan URL back sesuai role
$backUrl = 'dailyworkteknisi.php';
$backLabel = 'Kembali';
if ($_SESSION['role'] == 'admin_it') {
    $backUrl = '/index.php';
    $backLabel = 'Back to Menu';
}


?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>DAILY WORK ACTIVITY TIM TEKNISI AQSAA & RAJAWIFI</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/datetime/1.5.1/css/dataTables.dateTime.min.css">

    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --light-bg: #f8f9fa;
            --aquadark: #006d77;
            --aqualight: #83c5be;
        }
        
        body {
            background-color: #f0f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            margin-top: 60px;
            margin-bottom: 40px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            width: 98%;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .header {
            background: linear-gradient(135deg, var(--aquadark), var(--aqualight));
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            border-bottom: 5px solid rgba(255,255,255,0.2);
        }
        
        .table-responsive {
            max-height: 700px;
            overflow-y: auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .table thead {
            position: sticky;
            top: 0;
            background-color: var(--aquadark);
            color: white;
        }
        
        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .total-time {
            background-color: rgba(46, 204, 113, 0.15);
            color: #16a085;
            font-weight: 700;
            border-radius: 5px;
            padding: 5px 10px;
            text-align: center;
            border-left: 3px solid var(--success-color);
            font-family: 'Courier New', monospace;
        }
        
        .badge-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-open {
            background-color: var(--success-color);
            color: white;
        }
        
        .badge-closed {
            background-color: var(--danger-color);
            color: white;
        }
        
        .badge-pending {
            background-color: var(--warning-color);
            color: white;
        }
        
        .action-btns .btn {
            margin: 2px;
            min-width: 70px;
            transition: all 0.2s ease;
        }
        
        .action-btns .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #bdc3c7;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #95a5a6;
        }
        
        .back-btn {
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 1000;
            display: inline-flex;
            align-items: center;
            padding: 8px 12px;
            background: linear-gradient(to right, var(--aquadark), var(--aqualight));
            color: white;
            font-size: 14px;
            border-radius: 4px;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: none;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }
        
        .btn-primary {
            background-color: var(--aquadark);
            border-color: var(--aquadark);
        }
        
        .btn-primary:hover {
            background-color: #005f69;
            border-color: #005f69;
        }
        
        .btn-dark {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(131, 197, 190, 0.1);
        }
        
        .pagination .page-item.active .page-link {
            background-color: var(--aquadark);
            border-color: var(--aquadark);
        }
        
        .pagination .page-link {
            color: var(--aquadark);
        }
        
        .dataTables_info, .dataTables_length select {
            color: #6c757d !important;
        }
        
        .empty-state {
            background-color: rgba(131, 197, 190, 0.1);
            border-radius: 10px;
            padding: 30px;
        }
        
        /* CSS untuk Filter Section */
.filter-container {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.filter-title {
    font-weight: 600;
    margin-bottom: 15px;
    color: var(--aquadark);
    font-size: 1.1rem;
}

.date-range-container {
    margin-bottom: 15px;
}

.date-range-group {
    display: flex;
    align-items: flex-end;
    gap: 15px;
    flex-wrap: wrap;
}

.date-input-group {
    display: flex;
    gap: 15px;
    flex: 1;
    min-width: 300px;
}

.reset-btn-wrapper {
    display: flex;
    align-items: flex-end;
    height: 100%;
}

.reset-btn {
    height: 38px; /* Sesuaikan dengan tinggi input */
    white-space: nowrap;
}

.form-group {
    flex: 1;
    min-width: 150px;
}

.form-group label {
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
}

.filter-options {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-option {
    flex: 1;
    min-width: 200px;
}

/* Pastikan input dan select memiliki tinggi yang sama */
.form-control, .form-select {
    height: 38px;
}    </style>
</head>
<body>


<!-- Floating Back Button -->
<a href="<?= $backUrl ?>" class="back-btn">
    <i class="bi bi-arrow-left-short"></i> <?=$backLabel ?>
</a>

<div class="container">
    <div class="header">
        <h1><i class="bi bi-clipboard2-pulse"></i> DAILY WORK ACTIVITY TIM TEKNISI AQSAA & RAJAWIFI</h1>
        <p class="mb-0"><i class="bi bi-calendar-check"></i> Rekap seluruh aktivitas teknisi lapangan</p>
    </div>

    <div class="d-flex justify-content-between mb-4">
        <div>
            <a href="../dailywork/dailyworkteknisi.php" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle"></i> Tambah Data
            </a>
            <button class="btn btn-success" id="exportExcel">
                <i class="bi bi-file-excel"></i> Export Excel
            </button>
        </div>
        <div>
            <a href="cetak.php?min=<?= isset($_GET['min']) ? $_GET['min'] : '' ?>&max=<?= isset($_GET['max']) ? $_GET['max'] : '' ?>&site=<?= isset($_GET['site']) ? $_GET['site'] : '' ?>&status=<?= isset($_GET['status']) ? $_GET['status'] : '' ?>&teknisi=<?= isset($_GET['teknisi']) ? $_GET['teknisi'] : '' ?>" class="btn btn-dark me-2">
            <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
	    </a>
            <button class="btn btn-info text-white" id="refreshData">
                <i class="bi bi-arrow-clockwise"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Filter -->
<div class="filter-container mb-4">
    <div class="filter-title"><i class="bi bi-funnel"></i> Filter Data:</div>
    
    <div class="date-range-container">
        <div class="date-range-group">
            <div class="date-input-group">
                <div class="form-group">
                    <label for="min">Dari Tanggal:</label>
                    <input type="date" id="min" name="min" class="form-control">
                </div>
                <div class="form-group">
                    <label for="max">Sampai Tanggal:</label>
                    <input type="date" id="max" name="max" class="form-control">
                </div>
            </div>
            <div class="reset-btn-wrapper">
                <button id="resetDate" class="btn btn-secondary reset-btn">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Tanggal
                </button>
            </div>
        </div>
    </div>
    
    <div class="filter-options">
        <div class="filter-option">
            <label for="filterSite" class="form-label">Site:</label>
            <select id="filterSite" class="form-select">
                <option value="">Semua Site</option>
                <option value="Kebalen">Kebalen</option>
                <option value="Karawang">Karawang</option>
                <?php if ($result_site && $result_site->num_rows > 0): ?>
                    <?php while ($row_site = $result_site->fetch_assoc()): ?>
                        <?php if ($row_site['site'] !== 'Kebalen' && $row_site['site'] !== 'Karawang'): ?>
                            <option value="<?= htmlspecialchars($row_site['site']) ?>"><?= htmlspecialchars($row_site['site']) ?></option>
                        <?php endif; ?>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>
        </div>
        
        <div class="filter-option">
            <label for="filterStatus" class="form-label">Status:</label>
            <select id="filterStatus" class="form-select">
                <option value="">Semua Status</option>
                <option value="Open">Open</option>
                <option value="Closed">Closed</option>
                <option value="Pending">Pending</option>
                <option value="Dalam Proses">Dalam Proses</option>
                <option value="Dalam Monitoring">Dalam Monitoring</option>
                <option value="Cancel">Cancel</option>
            </select>
        </div>
        
        <div class="filter-option">
            <label for="filterTeknisi" class="form-label">Teknisi:</label>
            <input type="text" id="filterTeknisi" class="form-control" placeholder="Cari teknisi...">
        </div>
    </div>
</div>

    <div class="table-responsive">
        <table id="dataTable" class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Jenis Pekerjaan</th>
                    <th>Request By</th>
                    <th>ID Pelanggan</th>
                    <th>Nama Pelanggan</th>
                    <th>Teknisi</th>
                    <th>Site</th>
                    <th>Lokasi</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Total Waktu</th>
                    <th>Status</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php 
                    $no = 1;
                    while($row = $result->fetch_assoc()): 
                        $statusClass = '';
                        $statusText = strtolower($row['status_tiket']);
                        
                        if ($statusText === 'closed') {
                            $statusClass = 'badge-closed';
                        } elseif ($statusText === 'open') {
                            $statusClass = 'badge-open';
                        } else {
                            $statusClass = 'badge-pending';
                        }
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['jenis_pekerjaan']) ?></td>
                            <td><?= htmlspecialchars($row['request_by']) ?></td>
                            <td><?= htmlspecialchars($row['id_pelanggan']) ?></td>                            
                            <td><strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong></td>                           
                            <td><?= htmlspecialchars($row['nama_teknisi']) ?></td>
                            <td><?= htmlspecialchars($row['site']) ?></td>
                            <td><?= nl2br(htmlspecialchars($row['lokasi'])) ?></td>
                            <td data-order="<?= strtotime($row['waktu_mulai']) ?>"><?= date('d M Y H:i', strtotime($row['waktu_mulai'])) ?></td>
                            <td data-order="<?= strtotime($row['waktu_selesai']) ?>"><?= date('d M Y H:i', strtotime($row['waktu_selesai'])) ?></td>
                            <td class="total-time"><strong><?= htmlspecialchars($row['total_waktu']) ?></strong></td>
                            <td><span class="badge-status <?= $statusClass ?>"><?= htmlspecialchars($row['status_tiket']) ?></span></td>
                            <td class="action-btns">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= $row['id'] ?>" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13">
                            <div class="empty-state text-center py-4">
                                <i class="bi bi-exclamation-circle text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3 text-muted">Belum ada data yang tersimpan</h5>
                                <a href="../dailywork/dailyworkteknisi.php" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-circle"></i> Tambah Data
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="delete.php">
      <div class="modal-content">
        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="deleteModalLabel"><i class="bi bi-exclamation-triangle"></i> Konfirmasi Hapus</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.</p>
          <input type="hidden" name="id" id="deleteId" />
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
          <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Hapus</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/datetime/1.5.1/js/dataTables.dateTime.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>

<script>
    // Delete modal handler
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var inputId = deleteModal.querySelector('#deleteId');
        inputId.value = id;
    });

    $(document).ready(function () {
        // Inisialisasi DataTable
        var table = $('#dataTable').DataTable({
            "language": {
                "url": "https://cdn.datatables.net/plug-ins/1.13.6/i18n/id.json"
            },
            "order": [[8, "desc"]], // Default sorting by waktu_mulai (column index 8)
            "dom": '<"top"lf>rt<"bottom"ip>',
            "responsive": true,
            "initComplete": function() {
                $('.dataTables_filter input').addClass('form-control');
                $('.dataTables_length select').addClass('form-select');
            }
        });

        // Setup date range filter
        var minDate, maxDate;
        
        // Create date inputs
        minDate = new DateTime($('#min'), {
            format: 'YYYY-MM-DD'
        });
        
        maxDate = new DateTime($('#max'), {
            format: 'YYYY-MM-DD'
        });
        
        // Refilter the table
        $('#min, #max').on('change', function () {
            table.draw();
        });
        
        // Reset date filter
        $('#resetDate').click(function() {
            $('#min').val('');
            $('#max').val('');
            table.draw();
        });

        // Filter by Site - dengan exact match
        $('#filterSite').on('change', function () {
            var siteValue = this.value;
            console.log('Filter Site:', siteValue); // Debugging
            
            if (siteValue === '') {
                table.column(6).search('').draw();
            } else {
                // Gunakan regex untuk exact match
                table.column(6).search('^' + siteValue + '$', true, false).draw();
            }
        });

        // Filter by Status
        $('#filterStatus').on('change', function () {
            table.column(11).search(this.value).draw();
        });

        // Filter by Teknisi
        $('#filterTeknisi').on('keyup', function () {
            table.column(5).search(this.value).draw();
        });

        // Custom filtering function for date range
        $.fn.dataTable.ext.search.push(
            function (settings, data, dataIndex) {
                var min = $('#min').val();
                var max = $('#max').val();
                var date = new Date(data[8]); // Kolom waktu_mulai (index 8)
                
                if (min === '' && max === '') {
                    return true;
                }
                
                if (min === '' && max !== '') {
                    return date <= new Date(max + 'T23:59:59');
                }
                
                if (min !== '' && max === '') {
                    return date >= new Date(min);
                }
                
                if (min !== '' && max !== '') {
                    return date >= new Date(min) && date <= new Date(max + 'T23:59:59');
                }
                
                return false;
            }
        );

        $('#exportExcel').click(function() {
    // Ambil nilai filter
    var min = $('#min').val();
    var max = $('#max').val();
    var site = $('#filterSite').val();
    var status = $('#filterStatus').val();
    var teknisi = $('#filterTeknisi').val();
    
    // Redirect ke cetak_excel.php dengan parameter
    window.location.href = `cetak_excel.php?min=${min}&max=${max}&site=${site}&status=${status}&teknisi=${teknisi}`;
});
        // Tombol refresh data dengan SweetAlert loading
        $('#refreshData').click(function() {
            Swal.fire({
                title: 'Memperbarui Data',
                timer: 1000,
                timerProgressBar: true,
                didOpen: () => {
                    Swal.showLoading();
                },
                willClose: () => {
                    location.reload();
                }
            });
        });

        // Animasi hover pada baris tabel
        $('#dataTable tbody').on('mouseenter', 'tr', function() {
            $(this).css('transform', 'scale(1.01)');
        }).on('mouseleave', 'tr', function() {
            $(this).css('transform', 'scale(1)');
        });
    });

    // Tampilkan alert sukses jika ada session success
    <?php if(isset($_SESSION['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Sukses',
            text: '<?= $_SESSION['success'] ?>',
            timer: 3000
        });
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

// Simpan nilai filter saat berubah
$('#min, #max, #filterSite, #filterStatus, #filterTeknisi').on('change keyup', function() {
    // Update link cetak PDF dengan parameter filter terkini
    var min = $('#min').val();
    var max = $('#max').val();
    var site = $('#filterSite').val();
    var status = $('#filterStatus').val();
    var teknisi = $('#filterTeknisi').val();
    
    var pdfUrl = `cetak.php?min=${min}&max=${max}&site=${site}&status=${status}&teknisi=${teknisi}`;
    $('a.btn-dark[href*="cetak.php"]').attr('href', pdfUrl);
});
</script>

</body>
</html>